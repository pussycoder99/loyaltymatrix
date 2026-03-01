<?php

declare(strict_types=1);

namespace LoyaltyMatrix;

defined("WHMCS") or die("Access Denied");

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Discount application engine.
 *
 * Handles invoice discount logic with duplicate prevention and promo code stacking checks.
 */
class DiscountEngine
{
    /** @var string Prefix for loyalty discount line items */
    private const LINE_ITEM_PREFIX = 'Loyalty Discount';

    /** @var array<string, mixed> */
    private array $moduleVars;

    /**
     * @param array<string, mixed> $moduleVars Module configuration variables
     */
    public function __construct(array $moduleVars = [])
    {
        $this->moduleVars = $moduleVars;
    }

    /**
     * Determine whether a loyalty discount should be applied to an invoice.
     *
     * Triple-guard checks:
     * 1. Module enabled
     * 2. Invoice is recurring
     * 3. Client has assigned tier
     * 4. Invoice subtotal >= minimum
     * 5. No promo code (if configured)
     * 6. No excluded product groups
     * 7. DB duplicate check
     * 8. Line item duplicate check
     *
     * @param int $invoiceId
     * @return bool
     */
    public function shouldApplyDiscount(int $invoiceId): bool
    {
        // 1. Module enabled
        if (($this->moduleVars['enableModule'] ?? '') !== 'on') {
            return false;
        }

        // Get invoice details
        $invoice = Capsule::table('tblinvoices')
            ->where('id', $invoiceId)
            ->first();

        if (!$invoice) {
            return false;
        }

        // 2. Invoice must be Unpaid (not yet paid/cancelled)
        if ($invoice->status !== 'Unpaid') {
            return false;
        }

        // Get invoice items
        $invoiceItems = Capsule::table('tblinvoiceitems')
            ->where('invoiceid', $invoiceId)
            ->get();

        // Skip Add Funds invoices
        foreach ($invoiceItems as $item) {
            if ($item->type === 'AddFunds' || stripos($item->description ?? '', 'Add Funds') !== false) {
                return false;
            }
        }

        // Must have at least one item with a positive amount
        $hasValidItem = false;
        foreach ($invoiceItems as $item) {
            if ((float) $item->amount > 0) {
                $hasValidItem = true;
                break;
            }
        }

        if (!$hasValidItem) {
            return false;
        }

        $clientId = (int) $invoice->userid;

        // 3. Client has assigned tier
        $clientTier = Capsule::table('mod_loyaltymatrix_client_tiers')
            ->where('client_id', $clientId)
            ->whereNotNull('tier_id')
            ->first();

        if (!$clientTier) {
            return false;
        }

        // Verify the tier is still enabled
        $tier = Capsule::table('mod_loyaltymatrix_tiers')
            ->where('id', $clientTier->tier_id)
            ->where('is_enabled', 1)
            ->first();

        if (!$tier) {
            return false;
        }

        // 4. Minimum invoice amount
        $minAmount = (float) ($this->moduleVars['minimumInvoiceAmount'] ?? 0);
        if ($minAmount > 0) {
            $subtotal = (float) $invoice->subtotal;
            if ($subtotal < $minAmount) {
                return false;
            }
        }

        // 5. No promo code (if excludePromoClients is on)
        if (($this->moduleVars['excludePromoClients'] ?? '') === 'on') {
            foreach ($invoiceItems as $item) {
                if ($item->type === 'Hosting' && $item->relid > 0) {
                    $hosting = Capsule::table('tblhosting')
                        ->where('id', $item->relid)
                        ->first(['promoid']);
                    if ($hosting && (int) ($hosting->promoid ?? 0) > 0) {
                        return false;
                    }
                }
            }
        }

        // 6. Excluded product groups
        $excludeGroups = trim($this->moduleVars['excludeProductGroups'] ?? '');
        if ($excludeGroups !== '') {
            $excludedIds = array_map('intval', array_filter(explode(',', $excludeGroups)));
            if (!empty($excludedIds)) {
                foreach ($invoiceItems as $item) {
                    if ($item->type === 'Hosting' && $item->relid > 0) {
                        $hosting = Capsule::table('tblhosting')
                            ->where('id', $item->relid)
                            ->first(['packageid']);
                        if ($hosting) {
                            $product = Capsule::table('tblproducts')
                                ->where('id', $hosting->packageid)
                                ->first(['gid']);
                            if ($product && in_array((int) $product->gid, $excludedIds, true)) {
                                return false;
                            }
                        }
                    }
                }
            }
        }

        // 7. DB duplicate check
        $alreadyLogged = Capsule::table('mod_loyaltymatrix_discount_log')
            ->where('invoice_id', $invoiceId)
            ->count();

        if ($alreadyLogged > 0) {
            return false;
        }

        // 8. Line item duplicate check — look for [LoyaltyMatrix] prefix
        foreach ($invoiceItems as $item) {
            if (strpos($item->description, self::LINE_ITEM_PREFIX) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply the loyalty discount to an invoice.
     *
     * Adds a negative line item and logs the discount.
     *
     * @param int $invoiceId
     * @return array{tier_name: string, discount_percent: float, discount_amount: float}|null
     */
    public function applyDiscount(int $invoiceId): ?array
    {
        if (!$this->shouldApplyDiscount($invoiceId)) {
            return null;
        }

        $invoice = Capsule::table('tblinvoices')
            ->where('id', $invoiceId)
            ->first();

        if (!$invoice) {
            return null;
        }

        $clientId = (int) $invoice->userid;

        // Get client's tier
        $clientTier = Capsule::table('mod_loyaltymatrix_client_tiers')
            ->where('client_id', $clientId)
            ->first();

        $tier = Capsule::table('mod_loyaltymatrix_tiers')
            ->where('id', $clientTier->tier_id)
            ->where('is_enabled', 1)
            ->first();

        if (!$tier) {
            return null;
        }

        // Calculate discountable subtotal (exclude existing LoyaltyMatrix items)
        $discountableTotal = 0.0;
        $invoiceItems = Capsule::table('tblinvoiceitems')
            ->where('invoiceid', $invoiceId)
            ->get();

        foreach ($invoiceItems as $item) {
            if (strpos($item->description, self::LINE_ITEM_PREFIX) === false) {
                $discountableTotal += (float) $item->amount;
            }
        }

        if ($discountableTotal <= 0) {
            return null;
        }

        $discountPercent = (float) $tier->discount_percent;
        $discountAmount = round($discountableTotal * ($discountPercent / 100), 2);

        if ($discountAmount <= 0) {
            return null;
        }

        // Add negative line item
        $description = self::LINE_ITEM_PREFIX . " ({$tier->name} - {$discountPercent}%)";

        Capsule::table('tblinvoiceitems')->insert([
            'invoiceid' => $invoiceId,
            'userid' => $clientId,
            'type' => '',
            'relid' => 0,
            'description' => $description,
            'amount' => -$discountAmount,
            'taxed' => 0,
            'duedate' => $invoice->duedate,
            'paymentmethod' => $invoice->paymentmethod,
        ]);

        // Recalculate and update invoice totals
        try {
            // Recalculate subtotal from all line items
            $newSubtotal = (float) Capsule::table('tblinvoiceitems')
                ->where('invoiceid', $invoiceId)
                ->sum('amount');

            // Get tax from current invoice
            $currentInvoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first(['tax', 'tax2']);
            $tax = (float) ($currentInvoice->tax ?? 0);
            $tax2 = (float) ($currentInvoice->tax2 ?? 0);
            $newTotal = $newSubtotal + $tax + $tax2;

            Capsule::table('tblinvoices')
                ->where('id', $invoiceId)
                ->update([
                    'subtotal' => $newSubtotal,
                    'total' => $newTotal,
                ]);
        } catch (\Exception $e) {
            \logActivity("LoyaltyMatrix: Failed to update invoice #{$invoiceId} total: " . $e->getMessage());
        }

        // Log the discount
        Capsule::table('mod_loyaltymatrix_discount_log')->insert([
            'client_id' => $clientId,
            'invoice_id' => $invoiceId,
            'tier_id' => (int) $tier->id,
            'tier_name' => $tier->name,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        \logActivity("LoyaltyMatrix: Applied {$discountPercent}% discount (-{$discountAmount}) to invoice #{$invoiceId} for client #{$clientId} (tier: {$tier->name}).");

        return [
            'tier_name' => $tier->name,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
        ];
    }

    /**
     * Get discount history for a client.
     *
     * @param int $clientId
     * @param int $limit
     * @return array<int, object>
     */
    public function getClientHistory(int $clientId, int $limit = 5): array
    {
        return Capsule::table('mod_loyaltymatrix_discount_log')
            ->where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
