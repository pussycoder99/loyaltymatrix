<?php

declare(strict_types=1);

defined("WHMCS") or die("Access Denied");

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/lib/TierEngine.php';
require_once __DIR__ . '/lib/DiscountEngine.php';

/**
 * Get module configuration variables.
 *
 * @return array<string, mixed>
 */
function loyaltymatrix_getModuleVars(): array
{
    static $vars = null;
    if ($vars !== null) {
        return $vars;
    }

    try {
        $settings = Capsule::table('tbladdonmodules')
            ->where('module', 'loyaltymatrix')
            ->pluck('value', 'setting')
            ->toArray();
        $vars = $settings;
    } catch (\Exception $e) {
        $vars = [];
    }

    return $vars;
}

// ─────────────────────────────────────────────
// Hook: InvoiceCreationPreEmail
// Apply loyalty discount after line items finalized
// ─────────────────────────────────────────────
\add_hook('InvoiceCreationPreEmail', 1, function (array $vars) {
    $invoiceId = (int) ($vars['invoiceid'] ?? 0);
    if ($invoiceId <= 0) {
        return;
    }

    try {
        $moduleVars = loyaltymatrix_getModuleVars();

        if (($moduleVars['enableModule'] ?? '') !== 'on') {
            return;
        }

        $engine = new \LoyaltyMatrix\DiscountEngine($moduleVars);
        $engine->applyDiscount($invoiceId);
    } catch (\Exception $e) {
        \logActivity('LoyaltyMatrix Hook Error (InvoiceCreationPreEmail): ' . $e->getMessage());
    }
});

// ─────────────────────────────────────────────
// Hook: InvoiceCreated (backup trigger for cart orders)
// Fires on all invoice creations including cart checkout
// ─────────────────────────────────────────────
\add_hook('InvoiceCreated', 1, function (array $vars) {
    $invoiceId = (int) ($vars['invoiceid'] ?? 0);
    if ($invoiceId <= 0) {
        return;
    }

    try {
        $moduleVars = loyaltymatrix_getModuleVars();

        if (($moduleVars['enableModule'] ?? '') !== 'on') {
            return;
        }

        // The duplicate check in DiscountEngine prevents double-applying
        $engine = new \LoyaltyMatrix\DiscountEngine($moduleVars);
        $engine->applyDiscount($invoiceId);
    } catch (\Exception $e) {
        \logActivity('LoyaltyMatrix Hook Error (InvoiceCreated): ' . $e->getMessage());
    }
});

// ─────────────────────────────────────────────
// Hook: AddInvoicePayment
// Event-driven: recalculate tier for the paying client
// ─────────────────────────────────────────────
\add_hook('AddInvoicePayment', 5, function (array $vars) {
    $invoiceId = (int) ($vars['invoiceid'] ?? 0);
    if ($invoiceId <= 0) {
        return;
    }

    try {
        $moduleVars = loyaltymatrix_getModuleVars();
        if (($moduleVars['enableModule'] ?? '') !== 'on') {
            return;
        }

        $invoice = Capsule::table('tblinvoices')
            ->where('id', $invoiceId)
            ->first(['userid']);

        if ($invoice) {
            $engine = new \LoyaltyMatrix\TierEngine($moduleVars);
            $engine->assignTier((int) $invoice->userid);
        }
    } catch (\Exception $e) {
        \logActivity('LoyaltyMatrix Hook Error (AddInvoicePayment): ' . $e->getMessage());
    }
});

// ─────────────────────────────────────────────
// Hook: AfterModuleCreate
// Event-driven: new service = possible tier upgrade
// ─────────────────────────────────────────────
\add_hook('AfterModuleCreate', 5, function (array $vars) {
    try {
        $moduleVars = loyaltymatrix_getModuleVars();
        if (($moduleVars['enableModule'] ?? '') !== 'on') {
            return;
        }

        $clientId = (int) ($vars['params']['clientsdetails']['userid'] ?? $vars['params']['userid'] ?? 0);
        if ($clientId > 0) {
            $engine = new \LoyaltyMatrix\TierEngine($moduleVars);
            $engine->assignTier($clientId);
        }
    } catch (\Exception $e) {
        \logActivity('LoyaltyMatrix Hook Error (AfterModuleCreate): ' . $e->getMessage());
    }
});

// ─────────────────────────────────────────────
// Hook: AfterModuleTerminate
// Event-driven: lost service = possible tier downgrade
// ─────────────────────────────────────────────
\add_hook('AfterModuleTerminate', 5, function (array $vars) {
    try {
        $moduleVars = loyaltymatrix_getModuleVars();
        if (($moduleVars['enableModule'] ?? '') !== 'on') {
            return;
        }

        $clientId = (int) ($vars['params']['clientsdetails']['userid'] ?? $vars['params']['userid'] ?? 0);
        if ($clientId > 0) {
            $engine = new \LoyaltyMatrix\TierEngine($moduleVars);
            $engine->assignTier($clientId);
        }
    } catch (\Exception $e) {
        \logActivity('LoyaltyMatrix Hook Error (AfterModuleTerminate): ' . $e->getMessage());
    }
});

// ─────────────────────────────────────────────
// Hook: DailyCronJob
// Safety-net: batch recalculate all client tiers
// ─────────────────────────────────────────────
\add_hook('DailyCronJob', 1, function () {
    try {
        $moduleVars = loyaltymatrix_getModuleVars();
        if (($moduleVars['enableModule'] ?? '') !== 'on') {
            return;
        }

        $engine = new \LoyaltyMatrix\TierEngine($moduleVars);
        $engine->runCronBatch();
    } catch (\Exception $e) {
        \logActivity('LoyaltyMatrix Hook Error (DailyCronJob): ' . $e->getMessage());
    }
});

// ─────────────────────────────────────────────
// Hook: ClientAreaPrimarySidebar
// Add "Loyalty Program" link to client area nav
// Theme-compatible: tries multiple possible parent names
// ─────────────────────────────────────────────
\add_hook('ClientAreaPrimarySidebar', 10, function ($sidebar) {
    try {
        $moduleVars = loyaltymatrix_getModuleVars();
        if (($moduleVars['enableModule'] ?? '') !== 'on') {
            return;
        }

        /** @var \WHMCS\View\Menu\Item $sidebar */

        // Remove WHMCS auto-generated addon module sidebar entry (has icon)
        $parentNames = ['My Account', 'Account', 'Shortcuts', 'Service Details'];
        foreach ($parentNames as $parentName) {
            $parent = $sidebar->getChild($parentName);
            if (!is_null($parent)) {
                // Remove auto-generated entry by various possible names
                $autoNames = ['Loyalty Program', 'LoyaltyMatrix for WHMCS', 'loyaltymatrix', 'Loyaltymatrix'];
                foreach ($autoNames as $autoName) {
                    $existing = $parent->getChild($autoName);
                    if (!is_null($existing)) {
                        $parent->removeChild($autoName);
                    }
                }

                // Add our clean entry without icon
                $parent->addChild('loyaltyProgram', [
                    'label' => 'Loyalty Program',
                    'uri' => 'index.php?m=loyaltymatrix',
                    'order' => 99,
                ]);
                break;
            }
        }
    } catch (\Exception $e) {
        // Silently fail for sidebar — non-critical
    }
});

// ─────────────────────────────────────────────
// Hook: ClientAreaSecondaryNavbar
// Add "Loyalty Program" link to top-right user dropdown
// ─────────────────────────────────────────────
\add_hook('ClientAreaSecondaryNavbar', 10, function ($navbar) {
    try {
        $moduleVars = loyaltymatrix_getModuleVars();
        if (($moduleVars['enableModule'] ?? '') !== 'on') {
            return;
        }

        /** @var \WHMCS\View\Menu\Item $navbar */
        $accountMenu = $navbar->getChild('Account');
        if (!is_null($accountMenu)) {
            $accountMenu->addChild('Loyalty Program', [
                'label' => 'Loyalty Program',
                'uri' => 'index.php?m=loyaltymatrix',
                'order' => 65,
            ]);
        }
    } catch (\Exception $e) {
        // Silently fail — non-critical
    }
});

// ─────────────────────────────────────────────
// Hook: ClientAreaPage
// Inject loyalty tier status on client area homepage
// ─────────────────────────────────────────────
\add_hook('ClientAreaPage', 1, function (array $vars) {
    try {
        // Only inject on the main client area homepage
        $filename = $vars['filename'] ?? '';
        if ($filename !== 'clientarea') {
            return [];
        }

        $moduleVars = loyaltymatrix_getModuleVars();
        if (($moduleVars['enableModule'] ?? '') !== 'on') {
            return [];
        }

        $clientId = (int) ($_SESSION['uid'] ?? 0);
        if ($clientId <= 0) {
            return [];
        }

        $assignment = Capsule::table('mod_loyaltymatrix_client_tiers as ct')
            ->leftJoin('mod_loyaltymatrix_tiers as t', 'ct.tier_id', '=', 't.id')
            ->where('ct.client_id', $clientId)
            ->first(['t.name', 't.discount_percent', 'ct.account_age_months', 'ct.total_paid', 'ct.active_services', 'ct.tier_id']);

        return [
            'loyaltyTierName' => $assignment->name ?? null,
            'loyaltyDiscountPct' => $assignment->discount_percent ?? 0,
            'loyaltyAccountAge' => $assignment->account_age_months ?? 0,
            'loyaltyTotalPaid' => $assignment->total_paid ?? 0,
            'loyaltyActiveServices' => $assignment->active_services ?? 0,
            'loyaltyHasTier' => ($assignment && $assignment->tier_id) ? true : false,
        ];
    } catch (\Exception $e) {
        return [];
    }
});

// ─────────────────────────────────────────────
// Hook: ClientAreaHeadOutput
// Inject loyalty badge CSS + HTML into client area homepage
// ─────────────────────────────────────────────
\add_hook('ClientAreaHeadOutput', 1, function (array $vars) {
    try {
        $templateFile = $vars['templatefile'] ?? '';
        $filename = $vars['filename'] ?? '';
        $isHomepage = (
            $filename === 'clientarea'
            || $filename === 'clientareahome'
            || $templateFile === 'clientareahome'
            || strpos($_SERVER['REQUEST_URI'] ?? '', 'clientarea.php') !== false
        );
        if (isset($_GET['m']) || isset($_GET['action'])) {
            $isHomepage = false;
        }
        if (!$isHomepage) {
            return '';
        }

        $moduleVars = loyaltymatrix_getModuleVars();
        if (($moduleVars['enableModule'] ?? '') !== 'on') {
            return '';
        }

        $clientId = (int) ($_SESSION['uid'] ?? 0);
        if ($clientId <= 0) {
            return '';
        }

        // Inject CSS for the loyalty widget
        return '<link rel="stylesheet" href="modules/addons/loyaltymatrix/assets/css/loyaltymatrix.css">';
    } catch (\Exception $e) {
        return '';
    }
});

// ─────────────────────────────────────────────
// Hook: ClientAreaFooterOutput
// Inject loyalty status widget HTML via JavaScript on client homepage
// ─────────────────────────────────────────────
\add_hook('ClientAreaFooterOutput', 1, function (array $vars) {
    try {
        // Only show on client area homepage
        $templateFile = $vars['templatefile'] ?? '';
        $filename = $vars['filename'] ?? '';
        $isHomepage = (
            $filename === 'clientarea'
            || $filename === 'clientareahome'
            || $templateFile === 'clientareahome'
            || strpos($_SERVER['REQUEST_URI'] ?? '', 'clientarea.php') !== false
        );
        // Also skip if on a module page
        if (isset($_GET['m']) || isset($_GET['action'])) {
            $isHomepage = false;
        }
        if (!$isHomepage) {
            return '';
        }

        $moduleVars = loyaltymatrix_getModuleVars();
        if (($moduleVars['enableModule'] ?? '') !== 'on') {
            return '';
        }

        $clientId = (int) ($_SESSION['uid'] ?? 0);
        if ($clientId <= 0) {
            return '';
        }

        $assignment = Capsule::table('mod_loyaltymatrix_client_tiers as ct')
            ->leftJoin('mod_loyaltymatrix_tiers as t', 'ct.tier_id', '=', 't.id')
            ->where('ct.client_id', $clientId)
            ->first(['t.name', 't.discount_percent', 'ct.account_age_months', 'ct.total_paid', 'ct.active_services', 'ct.tier_id']);

        $tierName = $assignment->name ?? null;
        $discountPct = (float) ($assignment->discount_percent ?? 0);
        $ageMonths = (int) ($assignment->account_age_months ?? 0);
        $totalPaid = number_format((float) ($assignment->total_paid ?? 0), 2);
        $activeServs = (int) ($assignment->active_services ?? 0);
        $hasTier = ($assignment && $assignment->tier_id) ? true : false;

        if ($hasTier) {
            $badgeHtml = '<span class="badge badge-success" style="font-size:0.85rem;padding:5px 10px;">'
                . '<i class="fas fa-trophy"></i> '
                . htmlspecialchars($tierName) . ' &mdash; ' . $discountPct . '% Discount</span>';
        } else {
            $badgeHtml = '<span class="badge badge-secondary" style="font-size:0.85rem;padding:5px 10px;">'
                . '<i class="fas fa-star"></i> No Tier Yet</span>';
        }

        $panelHtml = '<div class="mb-3 card card-sidebar" id="lm-loyalty-widget">'
            . '<div class="card-header">'
            . '<h3 class="card-title m-0" style="font-size:1rem;">'
            . '<i class="fas fa-award"></i>&nbsp; Loyalty Status'
            . '<i class="fas fa-chevron-up card-minimise float-right"></i>'
            . '</h3>'
            . '</div>'
            . '<div class="collapsable-card-body">'
            . '<div class="card-body text-center" style="padding:15px;">'
            . $badgeHtml
            . '<br>'
            . '<a href="index.php?m=loyaltymatrix" class="btn btn-sm btn-warning" style="margin-top:10px;">'
            . '<i class="fas fa-chart-line"></i> View Full Details</a>'
            . '</div>'
            . '</div>'
            . '</div>';

        // Escape for JS
        $panelJs = str_replace(["'", "\n", "\r"], ["\\'", '', ''], $panelHtml);

        // Inject via JS — target the sidebar column, after Shortcuts
        $js = '<script>
document.addEventListener("DOMContentLoaded", function(){
    if(document.getElementById("lm-loyalty-widget")) return;
    var html = \'' . $panelJs . '\';
    var div = document.createElement("div");
    div.innerHTML = html;

    // Find sidebar column (left side) — insert at top
    var sidebar = document.querySelector(".col-lg-4.col-xl-3") || document.querySelector(".col-md-3");
    if(sidebar){
        sidebar.insertBefore(div, sidebar.firstChild);
    }
});
</script>';

        return $js;
    } catch (\Exception $e) {
        return '';
    }
});

// ─────────────────────────────────────────────
// Hook: ClientAreaFooterOutput (Cart Checkout Notification)
// Inject a discount notification on the checkout/viewcart page
// ─────────────────────────────────────────────
\add_hook('ClientAreaFooterOutput', 2, function (array $vars) {
    try {
        $templateFile = $vars['templatefile'] ?? '';
        
        // Target orderform viewcart and checkout templates
        if (!in_array(strtolower((string)$templateFile), ['viewcart', 'checkout'])) {
            return '';
        }

        $moduleVars = loyaltymatrix_getModuleVars();
        if (($moduleVars['enableModule'] ?? '') !== 'on') {
            return '';
        }

        $clientId = (int) ($_SESSION['uid'] ?? 0);
        if ($clientId <= 0) {
            return ''; // Only show for logged in users
        }

        $assignment = Capsule::table('mod_loyaltymatrix_client_tiers as ct')
            ->leftJoin('mod_loyaltymatrix_tiers as t', 'ct.tier_id', '=', 't.id')
            ->where('ct.client_id', $clientId)
            ->first(['t.name', 't.discount_percent', 'ct.tier_id']);

        if (!$assignment || !$assignment->tier_id || $assignment->discount_percent <= 0) {
            return '';
        }

        $discountPct = (float) $assignment->discount_percent;
        $tierName = htmlspecialchars($assignment->name ?? 'Tier');

        $js = '<script>
document.addEventListener("DOMContentLoaded", function(){
    if(document.getElementById("lm-cart-banner")) return;
    
    // Attempt to find the total sum from the page
    var totalText = "";
    var totalElement = document.querySelector("#totalDueToday") || document.querySelector(".total-due-today .amt");
    if (!totalElement) {
        // Fallback for older themes or different layouts
        var summaryElems = document.querySelectorAll(".order-summary .amt, .summary-container .total");
        if (summaryElems.length > 0) {
            totalElement = summaryElems[summaryElems.length - 1];
        }
    }
    
    var discountAmountFormatted = "' . $discountPct . '%";
    var discountValueStr = "";
    var discountAmount = 0;
    
    if (totalElement) {
        totalText = totalElement.innerText || totalElement.textContent;
        // Basic regex to extract numbers
        var match = totalText.match(/[\d,\.]+/);
        if (match) {
            var rawTotal = parseFloat(match[0].replace(/,/g, ""));
            if (!isNaN(rawTotal) && rawTotal > 0) {
                discountAmount = rawTotal * (' . $discountPct . ' / 100);
                
                // Keep the currency symbols by replacing the number
                discountValueStr = totalText.replace(match[0], discountAmount.toFixed(2));
                discountAmountFormatted = "<strong>" + discountValueStr + "</strong> (' . $discountPct . '%)";
            }
        }
    }

    var message = "<strong>🎉 Loyalty Reward!</strong> As a <strong>' . $tierName . '</strong> member, you will receive a <strong>" + discountAmountFormatted + " discount</strong> on this order!<br><small><em>The discounted price will be applied to the main invoice after you complete checkout. Please go ahead and place your order!</em></small>";

    var html = \'<div class="alert alert-success mt-3 mb-3" style="border-left: 5px solid #28a745; display: flex; align-items: center; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">\'
             + \'<div style="font-size: 2em; color: #28a745; margin-right: 15px;"><i class="fas fa-gift"></i></div>\'
             + \'<div>\' + message + \'</div>\'
             + \'</div>\';
             
    var wrapper = document.createElement("div");
    wrapper.id = "lm-cart-banner";
    wrapper.style.width = "100%";
    wrapper.innerHTML = html;
    
    // Find common container for standard_cart
    var container = document.querySelector("#order-standard_cart");
    if (container) {
        container.insertBefore(wrapper, container.firstChild);
    } else {
        container = document.querySelector("#PremiumComparison") || document.querySelector(".main-content");
        if (container) {
            container.parentNode.insertBefore(wrapper, container);
        }
    }
    
    // If the cart updates via ajax, we might want to re-run or let WHMCS handle it.
});
</script>';

        return $js;
    } catch (\Exception $e) {
        return '';
    }
});
