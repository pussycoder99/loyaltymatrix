<?php

declare(strict_types=1);

defined("WHMCS") or die("Access Denied");

use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('LOYALTYMATRIX_DIR')) {
    define('LOYALTYMATRIX_DIR', __DIR__);
}

require_once __DIR__ . '/lib/TierEngine.php';
require_once __DIR__ . '/lib/DiscountEngine.php';
require_once __DIR__ . '/lib/AdminController.php';

/**
 * Module configuration for WHMCS.
 *
 * @return array<string, mixed>
 */
function loyaltymatrix_config(): array
{
    return [
        'name' => 'LoyaltyMatrix for WHMCS',
        'description' => 'Fully configurable loyalty tier system with automatic percentage discounts based on account age, total paid, and active services.',
        'version' => '1.0.3',
        'author' => '<a href="https://github.com/torikulislamrijon" target="_blank">Torikul Islam Rijon</a>',
        'language' => 'english',
        'fields' => [
            'enableModule' => [
                'FriendlyName' => 'Enable Module',
                'Type' => 'yesno',
                'Default' => 'on',
                'Description' => 'Enable or disable the loyalty tier system globally.',
            ],
            'excludePromoClients' => [
                'FriendlyName' => 'Exclude Promo Clients',
                'Type' => 'yesno',
                'Default' => 'on',
                'Description' => 'Skip loyalty discount when a promo code is applied to the invoice.',
            ],
            'minimumInvoiceAmount' => [
                'FriendlyName' => 'Minimum Invoice Amount',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '0',
                'Description' => 'Minimum invoice subtotal required for loyalty discount to apply.',
            ],
            'excludeProductGroups' => [
                'FriendlyName' => 'Exclude Product Groups',
                'Type' => 'text',
                'Size' => '40',
                'Default' => '',
                'Description' => 'Comma-separated product group IDs to exclude from loyalty discounts.',
            ],
            'tierExpiryFreeze' => [
                'FriendlyName' => 'Tier Expiry Freeze',
                'Type' => 'yesno',
                'Default' => '',
                'Description' => 'If enabled, clients will never downgrade — their tier freezes at highest achieved.',
            ],
        ],
    ];
}

/**
 * Module activation — creates database tables.
 *
 * @return array{status: string, description: string}
 */
function loyaltymatrix_activate(): array
{
    try {
        // Tiers table
        if (!Capsule::schema()->hasTable('mod_loyaltymatrix_tiers')) {
            Capsule::schema()->create('mod_loyaltymatrix_tiers', function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('id');
                $table->string('name', 100);
                $table->unsignedInteger('min_account_age_months')->default(0);
                // min_total_paid is kept for backwards compatibility / base currency fallback
                $table->decimal('min_total_paid', 12, 2)->default(0.00);
                $table->unsignedInteger('min_active_services')->default(0);
                $table->decimal('discount_percent', 5, 2)->default(0.00);
                $table->unsignedInteger('priority')->default(0);
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
            });
        }

        // Per-Currency Thresholds
        if (!Capsule::schema()->hasTable('mod_loyaltymatrix_tier_currencies')) {
            Capsule::schema()->create('mod_loyaltymatrix_tier_currencies', function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('id');
                $table->unsignedInteger('tier_id');
                $table->unsignedInteger('currency_id');
                $table->decimal('min_total_paid', 12, 2)->default(0.00);
                $table->timestamps();

                $table->unique(['tier_id', 'currency_id']);
                $table->index('tier_id');
            });
        }

        // Client tier assignments
        if (!Capsule::schema()->hasTable('mod_loyaltymatrix_client_tiers')) {
            Capsule::schema()->create('mod_loyaltymatrix_client_tiers', function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('id');
                $table->unsignedInteger('client_id')->unique();
                $table->unsignedInteger('tier_id')->nullable();
                $table->unsignedInteger('highest_tier_id')->nullable();
                $table->integer('account_age_months')->default(0);
                $table->decimal('total_paid', 12, 2)->default(0.00);
                $table->integer('active_services')->default(0);
                $table->dateTime('assigned_at')->nullable();
                $table->dateTime('updated_at')->nullable();

                $table->index('tier_id');
                $table->index('highest_tier_id');
            });
        }

        // Discount log
        if (!Capsule::schema()->hasTable('mod_loyaltymatrix_discount_log')) {
            Capsule::schema()->create('mod_loyaltymatrix_discount_log', function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('id');
                $table->unsignedInteger('client_id');
                $table->unsignedInteger('invoice_id');
                $table->unsignedInteger('tier_id');
                $table->string('tier_name', 100);
                $table->decimal('discount_percent', 5, 2);
                $table->decimal('discount_amount', 12, 2);
                $table->dateTime('created_at')->useCurrent();

                $table->index('client_id');
                $table->index('invoice_id');
            });
        }

        return [
            'status' => 'success',
            'description' => 'LoyaltyMatrix activated successfully. Configure your tiers from the module admin page.',
        ];
    }
    catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Failed to create database tables: ' . $e->getMessage(),
        ];
    }
}

/**
 * Module deactivation — drops database tables.
 *
 * @return array{status: string, description: string}
 */
function loyaltymatrix_deactivate(): array
{
    try {
        Capsule::schema()->dropIfExists('mod_loyaltymatrix_discount_log');
        Capsule::schema()->dropIfExists('mod_loyaltymatrix_client_tiers');
        Capsule::schema()->dropIfExists('mod_loyaltymatrix_tier_currencies');
        Capsule::schema()->dropIfExists('mod_loyaltymatrix_tiers');

        return [
            'status' => 'success',
            'description' => 'LoyaltyMatrix deactivated. All loyalty data has been removed.',
        ];
    }
    catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Failed to remove database tables: ' . $e->getMessage(),
        ];
    }
}

/**
 * Module upgrade handler for schema migrations.
 *
 * @param array<string, mixed> $vars
 */
function loyaltymatrix_upgrade(array $vars): void
{
    $currentVersion = $vars['version'];

    try {
        if (version_compare($currentVersion, '1.0.3', '<')) {
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
        }
    }
    catch (\Exception $e) {
        \logActivity("LoyaltyMatrix Upgrade Error: " . $e->getMessage());
    }
}

/**
 * Admin area output handler.
 *
 * @param array<string, mixed> $vars
 */
function loyaltymatrix_output(array $vars): void
{
    $controller = new \LoyaltyMatrix\AdminController($vars);
    $controller->dispatch();
}

/**
 * Client area output handler.
 *
 * @param array<string, mixed> $vars
 * @return array<string, mixed>
 */
function loyaltymatrix_clientarea(array $vars): array
{
    $LANG = $vars['_lang'];
    $clientId = (int)($_SESSION['uid'] ?? 0);

    if ($clientId <= 0) {
        return [
            'pagetitle' => $LANG['module_title'] ?? 'Loyalty Program',
            'breadcrumb' => ['index.php?m=loyaltymatrix' => 'Loyalty Program'],
            'templatefile' => 'templates/client/dashboard',
            'requirelogin' => true,
            'forcessl' => true,
            'vars' => ['error' => 'Not logged in'],
        ];
    }

    try {
        $tierEngine = new \LoyaltyMatrix\TierEngine($vars);
        $discountEngine = new \LoyaltyMatrix\DiscountEngine($vars);

        $clientTier = $tierEngine->getClientTier($clientId);
        $tierProgress = $tierEngine->getTierProgress($clientId);
        $discountLog = $discountEngine->getClientHistory($clientId, 20);

        // Get all enabled tiers for benefits display
        $allTiers = Capsule::table('mod_loyaltymatrix_tiers')
            ->where('is_enabled', 1)
            ->orderBy('priority', 'asc')
            ->get()
            ->toArray();

        // Fetch client's specific currency for formatting
        $clientCurrency = Capsule::table('tblclients')
            ->join('tblcurrencies', 'tblclients.currency', '=', 'tblcurrencies.id')
            ->where('tblclients.id', $clientId)
            ->first(['tblcurrencies.prefix', 'tblcurrencies.suffix']);

        $currencyPrefix = $clientCurrency ? $clientCurrency->prefix : '$';
        $currencySuffix = $clientCurrency ? $clientCurrency->suffix : '';

        return [
            'pagetitle' => $LANG['module_title'] ?? 'Loyalty Program',
            'breadcrumb' => ['index.php?m=loyaltymatrix' => 'Loyalty Program'],
            'templatefile' => 'templates/client/dashboard',
            'requirelogin' => true,
            'forcessl' => true,
            'vars' => [
                'LANG' => $LANG,
                'clientTier' => $clientTier,
                'tierProgress' => $tierProgress,
                'discountLog' => $discountLog,
                'allTiers' => $allTiers,
                'currencyPrefix' => $currencyPrefix,
                'currencySuffix' => $currencySuffix,
            ],
        ];
    }
    catch (\Exception $e) {
        \logActivity('LoyaltyMatrix Error (clientarea): ' . $e->getMessage());
        return [
            'pagetitle' => $LANG['module_title'] ?? 'Loyalty Program',
            'breadcrumb' => ['index.php?m=loyaltymatrix' => 'Loyalty Program'],
            'templatefile' => 'templates/client/dashboard',
            'requirelogin' => true,
            'forcessl' => true,
            'vars' => [
                'LANG' => $LANG,
                'error' => $LANG['error_generic'] ?? 'An error occurred.',
            ],
        ];
    }
}