<?php

declare(strict_types=1);

namespace LoyaltyMatrix;

defined("WHMCS") or die("Access Denied");

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Tier calculation and assignment engine.
 *
 * Handles metric computation, tier matching, and daily cron batch processing.
 */
class TierEngine
{
    /** @var array<string, mixed> */
    private array $moduleVars;

    /**
     * @param array<string, mixed> $moduleVars Module configuration variables
     */
    public function __construct(array $moduleVars = [])
    {
        $this->moduleVars = $moduleVars;
        $this->ensureSchema();
    }

    /**
     * Ensure any recently added tables exist if the upgrade script didn't fire.
     */
    private function ensureSchema(): void
    {
        try {
            if (!Capsule::schema()->hasTable('mod_loyaltymatrix_tier_currencies')) {
                Capsule::schema()->create('mod_loyaltymatrix_tier_currencies', function ($table) {
                    $table->increments('id');
                    $table->unsignedInteger('tier_id');
                    $table->unsignedInteger('currency_id');
                    $table->decimal('min_total_paid', 12, 2)->default(0.00);
                    $table->timestamp('updated_at');

                    $table->index('tier_id');
                    $table->index('currency_id');
                });
            }
        } catch (\Exception $e) {
            // Silently ignore if table already exists or permission issues prevent check
        }
    }

    /**
     * Calculate loyalty metrics for a client.
     *
     * @param int $clientId
     * @return array{account_age_months: int, total_paid: float, active_services: int, currency_id: int}
     */
    public function calculateClientMetrics(int $clientId): array
    {
        // Account age in months from tblclients.datecreated
        $client = Capsule::table('tblclients')
            ->where('id', $clientId)
            ->first(['datecreated', 'currency']);

        $accountAgeMonths = 0;
        $currencyId = 1; // Default to base currency if not found

        if ($client) {
            if (!empty($client->datecreated)) {
                $created = new \DateTime($client->datecreated);
                $now = new \DateTime();
                $diff = $created->diff($now);
                $accountAgeMonths = ($diff->y * 12) + $diff->m;
            }
            if (!empty($client->currency)) {
                $currencyId = (int) $client->currency;
            }
        }

        // Total paid — RAW AMOUNT in client's local currency
        $totalPaid = (float) Capsule::table('tblinvoices')
            ->where('userid', $clientId)
            ->where('status', 'Paid')
            ->sum('total');

        // Active services count
        $activeServices = (int) Capsule::table('tblhosting')
            ->where('userid', $clientId)
            ->whereIn('domainstatus', ['Active', 'Suspended'])
            ->count();

        return [
            'account_age_months' => $accountAgeMonths,
            'total_paid' => round($totalPaid, 2),
            'active_services' => $activeServices,
            'currency_id' => $currencyId,
        ];
    }

    /**
     * Find the highest-priority enabled tier matching the given metrics.
     *
     * @param array{account_age_months: int, total_paid: float, active_services: int} $metrics
     * @return object|null Tier row or null if no match
     */
    /**
     * Find the highest-priority enabled tier matching the given metrics.
     *
     * @param array{account_age_months: int, total_paid: float, active_services: int, currency_id: int} $metrics
     * @return object|null Tier row or null if no match
     */
    public function findMatchingTier(array $metrics): ?object
    {
        $tiers = Capsule::table('mod_loyaltymatrix_tiers')
            ->where('is_enabled', 1)
            ->orderBy('priority', 'desc')
            ->get();

        $currencyId = $metrics['currency_id'] ?? 1;

        foreach ($tiers as $tier) {
            // Fetch currency-specific threshold (fallback to default `min_total_paid` if missing)
            $currencyThresholdMatch = Capsule::table('mod_loyaltymatrix_tier_currencies')
                ->where('tier_id', $tier->id)
                ->where('currency_id', $currencyId)
                ->first(['min_total_paid']);

            $requiredTotalPaid = $currencyThresholdMatch ? (float) $currencyThresholdMatch->min_total_paid : (float) $tier->min_total_paid;

            if (
                $metrics['account_age_months'] >= (int) $tier->min_account_age_months
                && $metrics['total_paid'] >= $requiredTotalPaid
                && $metrics['active_services'] >= (int) $tier->min_active_services
            ) {
                return $tier;
            }
        }

        return null;
    }

    /**
     * Assign (or reassign) a tier to a specific client.
     *
     * @param int $clientId
     * @return array{previous_tier: string|null, new_tier: string|null, metrics: array}
     */
    public function assignTier(int $clientId): array
    {
        $metrics = $this->calculateClientMetrics($clientId);
        $newTier = $this->findMatchingTier($metrics);
        $newTierId = $newTier ? (int) $newTier->id : null;

        // Get existing assignment
        $existing = Capsule::table('mod_loyaltymatrix_client_tiers')
            ->where('client_id', $clientId)
            ->first();

        $previousTierName = null;
        $highestTierId = null;

        if ($existing) {
            // Get previous tier name
            if ($existing->tier_id) {
                $prevTier = Capsule::table('mod_loyaltymatrix_tiers')
                    ->where('id', $existing->tier_id)
                    ->first(['name']);
                $previousTierName = $prevTier ? $prevTier->name : null;
            }
            $highestTierId = $existing->highest_tier_id;
        }

        // Tier Expiry Freeze: use highest achieved tier if freeze is on
        $freezeEnabled = ($this->moduleVars['tierExpiryFreeze'] ?? '') === 'on';
        $effectiveTierId = $newTierId;

        if ($freezeEnabled && $newTierId !== null) {
            // Track highest tier by priority
            if ($highestTierId) {
                $highestTier = Capsule::table('mod_loyaltymatrix_tiers')
                    ->where('id', $highestTierId)
                    ->first(['priority']);
                if ($highestTier && $newTier && (int) $highestTier->priority > (int) $newTier->priority) {
                    $effectiveTierId = (int) $highestTierId;
                }
            }
        } elseif ($freezeEnabled && $newTierId === null && $highestTierId) {
            // No tier matched but freeze is on — keep highest
            $effectiveTierId = (int) $highestTierId;
        }

        // Update highest tier ID
        $newHighestTierId = $highestTierId;
        if ($newTierId !== null) {
            if ($highestTierId === null) {
                $newHighestTierId = $newTierId;
            } else {
                $currentHighest = Capsule::table('mod_loyaltymatrix_tiers')
                    ->where('id', $highestTierId)
                    ->first(['priority']);
                if ($newTier && $currentHighest && (int) $newTier->priority > (int) $currentHighest->priority) {
                    $newHighestTierId = $newTierId;
                }
            }
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'tier_id' => $effectiveTierId,
            'highest_tier_id' => $newHighestTierId,
            'account_age_months' => $metrics['account_age_months'],
            'total_paid' => $metrics['total_paid'],
            'active_services' => $metrics['active_services'],
            'updated_at' => $now,
        ];

        if ($existing) {
            // Only update assigned_at if tier actually changed
            if ((int) ($existing->tier_id ?? 0) !== (int) ($effectiveTierId ?? 0)) {
                $data['assigned_at'] = $now;
            }
            Capsule::table('mod_loyaltymatrix_client_tiers')
                ->where('client_id', $clientId)
                ->update($data);
        } else {
            $data['client_id'] = $clientId;
            $data['assigned_at'] = $now;
            Capsule::table('mod_loyaltymatrix_client_tiers')->insert($data);
        }

        // Get new tier name
        $newTierName = null;
        if ($effectiveTierId) {
            $effTier = Capsule::table('mod_loyaltymatrix_tiers')
                ->where('id', $effectiveTierId)
                ->first(['name']);
            $newTierName = $effTier ? $effTier->name : null;
        }

        // Log tier changes
        if ($previousTierName !== $newTierName) {
            $direction = ($newTierName === null) ? 'removed from tier'
                : ($previousTierName === null ? "assigned to {$newTierName}"
                    : "changed from {$previousTierName} to {$newTierName}");
            \logActivity("LoyaltyMatrix: Client #{$clientId} {$direction}.");
        }

        return [
            'previous_tier' => $previousTierName,
            'new_tier' => $newTierName,
            'metrics' => $metrics,
        ];
    }

    /**
     * Run cron batch — recalculate all active clients in chunks.
     *
     * @return array{upgraded: int, downgraded: int, unchanged: int, total: int}
     */
    public function runCronBatch(): array
    {
        $stats = ['upgraded' => 0, 'downgraded' => 0, 'unchanged' => 0, 'total' => 0];

        // Get all active clients in chunks
        Capsule::table('tblclients')
            ->where('status', 'Active')
            ->orderBy('id')
            ->chunk(100, function ($clients) use (&$stats) {
                foreach ($clients as $client) {
                    try {
                        $result = $this->assignTier((int) $client->id);
                        $stats['total']++;

                        if ($result['previous_tier'] === $result['new_tier']) {
                            $stats['unchanged']++;
                        } elseif (
                            $result['new_tier'] === null || (
                                $result['previous_tier'] !== null
                                && $result['new_tier'] !== null
                            )
                        ) {
                            // Determine direction by comparing tier priorities
                            $prevPriority = $this->getTierPriority($result['previous_tier']);
                            $newPriority = $this->getTierPriority($result['new_tier']);
                            if ($newPriority > $prevPriority) {
                                $stats['upgraded']++;
                            } else {
                                $stats['downgraded']++;
                            }
                        } elseif ($result['previous_tier'] === null && $result['new_tier'] !== null) {
                            $stats['upgraded']++;
                        } else {
                            $stats['downgraded']++;
                        }
                    } catch (\Exception $e) {
                        \logActivity("LoyaltyMatrix Cron Error for client #{$client->id}: " . $e->getMessage());
                    }
                }
            });

        \logActivity("LoyaltyMatrix Cron: Processed {$stats['total']} clients. "
            . "Upgraded: {$stats['upgraded']}, Downgraded: {$stats['downgraded']}, "
            . "Unchanged: {$stats['unchanged']}.");

        return $stats;
    }

    /**
     * Get a tier's priority by name.
     *
     * @param string|null $tierName
     * @return int
     */
    private function getTierPriority(?string $tierName): int
    {
        if ($tierName === null) {
            return 0;
        }
        $tier = Capsule::table('mod_loyaltymatrix_tiers')
            ->where('name', $tierName)
            ->first(['priority']);
        return $tier ? (int) $tier->priority : 0;
    }

    /**
     * Get the current tier assignment for a client (with tier details).
     *
     * @param int $clientId
     * @return object|null
     */
    public function getClientTier(int $clientId): ?object
    {
        $assignment = Capsule::table('mod_loyaltymatrix_client_tiers')
            ->where('client_id', $clientId)
            ->first();

        if (!$assignment || !$assignment->tier_id) {
            return $assignment;
        }

        $tier = Capsule::table('mod_loyaltymatrix_tiers')
            ->where('id', $assignment->tier_id)
            ->first();

        if ($tier) {
            $assignment->tier_name = $tier->name;
            $assignment->discount_percent = $tier->discount_percent;
            $assignment->tier_priority = $tier->priority;
        }

        return $assignment;
    }

    /**
     * Get progress toward the next tier for a client.
     *
     * @param int $clientId
     * @return array{current_tier: object|null, next_tier: object|null, progress: array}
     */
    public function getTierProgress(int $clientId): array
    {
        $clientTier = $this->getClientTier($clientId);
        $metrics = $this->calculateClientMetrics($clientId);

        $currentPriority = 0;
        if ($clientTier && $clientTier->tier_id) {
            $currentPriority = (int) ($clientTier->tier_priority ?? 0);
        }

        // Find next tier (next highest priority above current)
        $nextTier = Capsule::table('mod_loyaltymatrix_tiers')
            ->where('is_enabled', 1)
            ->where('priority', '>', $currentPriority)
            ->orderBy('priority', 'asc')
            ->first();

        $progress = [
            'account_age' => ['current' => $metrics['account_age_months'], 'required' => 0, 'percent' => 100],
            'total_paid' => ['current' => $metrics['total_paid'], 'required' => 0, 'percent' => 100],
            'active_services' => ['current' => $metrics['active_services'], 'required' => 0, 'percent' => 100],
        ];

        if ($nextTier) {
            $progress['account_age']['required'] = (int) $nextTier->min_account_age_months;
            $progress['active_services']['required'] = (int) $nextTier->min_active_services;

            // Get currency-specific threshold
            $currencyId = $metrics['currency_id'] ?? 1;
            $currencyThresholdMatch = Capsule::table('mod_loyaltymatrix_tier_currencies')
                ->where('tier_id', $nextTier->id)
                ->where('currency_id', $currencyId)
                ->first(['min_total_paid']);

            $progress['total_paid']['required'] = $currencyThresholdMatch ? (float) $currencyThresholdMatch->min_total_paid : (float) $nextTier->min_total_paid;

            // Calculate percentages
            foreach (['account_age' => 'account_age_months', 'total_paid' => 'total_paid', 'active_services' => 'active_services'] as $key => $metricKey) {
                $req = $progress[$key]['required'];
                $cur = $metrics[$metricKey];
                if ($req > 0) {
                    $progress[$key]['percent'] = min(100, round(($cur / $req) * 100));
                } else {
                    $progress[$key]['percent'] = 100;
                }
            }
        }

        return [
            'current_tier' => $clientTier,
            'next_tier' => $nextTier,
            'progress' => $progress,
            'metrics' => $metrics,
        ];
    }
}
