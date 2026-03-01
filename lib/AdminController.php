<?php

declare(strict_types=1);

namespace LoyaltyMatrix;

defined("WHMCS") or die("Access Denied");

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Admin area controller — handles page routing and AJAX actions.
 */
class AdminController
{
    /** @var array<string, mixed> */
    private array $vars;

    /** @var string */
    private string $moduleLink;

    /** @var array<string, string> */
    private array $lang;

    public function __construct(array $vars)
    {
        $this->vars = $vars;
        $this->moduleLink = $vars['modulelink'] ?? '';
        $this->lang = $vars['_lang'] ?? [];
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
        }
        catch (\Exception $e) {
        // Silently ignore if table already exists or permission issues prevent check
        }
    }

    /**
     * Dispatch the request to the appropriate method.
     */
    public function dispatch(): void
    {
        $action = $_GET['action'] ?? 'index';

        // Handle POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            switch ($action) {
                case 'savetier':
                    $this->saveTier();
                    return;
                case 'deletetier':
                    $this->deleteTier();
                    return;
                case 'toggletier':
                    $this->toggleTier();
                    return;
                case 'runcron':
                    $this->runCronManual();
                    return;
                case 'runupdate':
                    $this->executeUpgrade();
                    return;
            }
        }

        // Handle GET actions
        switch ($action) {
            case 'tiers':
                $this->tierList();
                break;
            case 'addtier':
                $this->tierForm();
                break;
            case 'edittier':
                $this->tierForm((int)($_GET['id'] ?? 0));
                break;
            case 'clients':
                $this->clientList();
                break;
            case 'clientdetail':
                $this->clientDetail((int)($_GET['id'] ?? 0));
                break;
            default:
                $this->dashboard();
                break;
        }
    }

    /**
     * Dashboard — overview statistics.
     */
    private function dashboard(): void
    {
        // Total clients per tier
        $clientsPerTier = Capsule::table('mod_loyaltymatrix_client_tiers as ct')
            ->leftJoin('mod_loyaltymatrix_tiers as t', 'ct.tier_id', '=', 't.id')
            ->select(
            Capsule::raw('COALESCE(t.name, "No Tier") as tier_name'),
            Capsule::raw('COUNT(ct.id) as client_count'),
            Capsule::raw('COALESCE(t.discount_percent, 0) as discount_percent'),
            Capsule::raw('COALESCE(t.priority, 0) as priority')
        )
            ->groupBy('ct.tier_id', 't.name', 't.discount_percent', 't.priority')
            ->orderBy('priority', 'desc')
            ->get()
            ->toArray();

        // Revenue per tier (total paid grouped by current tier)
        $revenuePerTier = Capsule::table('mod_loyaltymatrix_client_tiers as ct')
            ->leftJoin('mod_loyaltymatrix_tiers as t', 'ct.tier_id', '=', 't.id')
            ->select(
            Capsule::raw('COALESCE(t.name, "No Tier") as tier_name'),
            Capsule::raw('SUM(ct.total_paid) as total_revenue')
        )
            ->groupBy('ct.tier_id', 't.name')
            ->get()
            ->toArray();

        // Total discounts applied stats
        $totalDiscounts = Capsule::table('mod_loyaltymatrix_discount_log')
            ->select(
            Capsule::raw('COUNT(*) as total_count'),
            Capsule::raw('COALESCE(SUM(discount_amount), 0) as total_amount')
        )
            ->first();

        // Total tiers
        $totalTiers = Capsule::table('mod_loyaltymatrix_tiers')->count();

        // Total assigned clients
        $totalAssigned = Capsule::table('mod_loyaltymatrix_client_tiers')
            ->whereNotNull('tier_id')
            ->count();

        // Recent discount activity
        $recentDiscounts = Capsule::table('mod_loyaltymatrix_discount_log as dl')
            ->leftJoin('tblclients as c', 'dl.client_id', '=', 'c.id')
            ->select('dl.*', Capsule::raw('CONCAT(c.firstname, " ", c.lastname) as client_name'))
            ->orderBy('dl.created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        // Check for updates
        $updateData = $this->checkGitHubUpdate();

        $this->renderTemplate('dashboard', [
            'moduleLink' => $this->moduleLink,
            'clientsPerTier' => $clientsPerTier,
            'revenuePerTier' => $revenuePerTier,
            'totalDiscounts' => $totalDiscounts,
            'totalTiers' => $totalTiers,
            'totalAssigned' => $totalAssigned,
            'recentDiscounts' => $recentDiscounts,
            'update' => $updateData,
            'LANG' => $this->lang,
        ]);
    }

    /**
     * Tier listing page.
     */
    private function tierList(): void
    {
        $tiers = Capsule::table('mod_loyaltymatrix_tiers')
            ->orderBy('priority', 'desc')
            ->get()
            ->toArray();

        // Count clients per tier
        foreach ($tiers as &$tier) {
            $tier->client_count = Capsule::table('mod_loyaltymatrix_client_tiers')
                ->where('tier_id', $tier->id)
                ->count();
        }

        $this->renderTemplate('tiers', [
            'moduleLink' => $this->moduleLink,
            'tiers' => $tiers,
            'LANG' => $this->lang,
        ]);
    }

    /**
     * Tier create/edit form.
     */
    private function tierForm(int $tierId = 0): void
    {
        $tier = null;
        $tierCurrencies = [];

        if ($tierId > 0) {
            $tier = Capsule::table('mod_loyaltymatrix_tiers')
                ->where('id', $tierId)
                ->first();

            // Fetch existing currency thresholds
            $thresholds = Capsule::table('mod_loyaltymatrix_tier_currencies')
                ->where('tier_id', $tierId)
                ->get();
            foreach ($thresholds as $t) {
                $tierCurrencies[$t->currency_id] = $t->min_total_paid;
            }
        }

        // Fetch all active WHMCS currencies
        $currencies = Capsule::table('tblcurrencies')->orderBy('code', 'asc')->get();

        $this->renderTemplate('tier-form', [
            'moduleLink' => $this->moduleLink,
            'tier' => $tier,
            'isEdit' => $tier !== null,
            'currencies' => $currencies,
            'tierCurrencies' => $tierCurrencies,
            'LANG' => $this->lang,
        ]);
    }

    /**
     * Save (create or update) a tier.
     */
    private function saveTier(): void
    {
        $tierId = (int)($_POST['tier_id'] ?? 0);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'min_account_age_months' => max(0, (int)($_POST['min_account_age_months'] ?? 0)),
            // We still save the fallback `min_total_paid` (e.g. from the base currency input if available, or 0)
            'min_total_paid' => max(0, (float)($_POST['min_total_paid_fallback'] ?? 0)),
            'min_active_services' => max(0, (int)($_POST['min_active_services'] ?? 0)),
            'discount_percent' => max(0, min(100, (float)($_POST['discount_percent'] ?? 0))),
            'priority' => max(0, (int)($_POST['priority'] ?? 0)),
            'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Ensure we grab the base currency as the fallback 'min_total_paid' just in case.
        $baseCurId = Capsule::table('tblcurrencies')->where('default', 1)->value('id');
        if (isset($_POST['currency_thresholds'][$baseCurId])) {
            $data['min_total_paid'] = max(0, (float)$_POST['currency_thresholds'][$baseCurId]);
        }

        // Validation
        if (empty($data['name'])) {
            $this->redirectWithMessage('addtier', 'error', 'Tier name is required.');
            return;
        }

        try {
            if ($tierId > 0) {
                Capsule::table('mod_loyaltymatrix_tiers')
                    ->where('id', $tierId)
                    ->update($data);
                \logActivity("LoyaltyMatrix: Tier '{$data['name']}' (ID: {$tierId}) updated.");
            }
            else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $tierId = Capsule::table('mod_loyaltymatrix_tiers')->insertGetId($data);
                \logActivity("LoyaltyMatrix: Tier '{$data['name']}' (ID: {$tierId}) created.");
            }

            // Save currency-specific thresholds
            if (isset($_POST['currency_thresholds']) && is_array($_POST['currency_thresholds'])) {
                foreach ($_POST['currency_thresholds'] as $curId => $amt) {
                    $cId = (int)$curId;
                    $val = max(0, (float)$amt);

                    $exists = Capsule::table('mod_loyaltymatrix_tier_currencies')
                        ->where('tier_id', $tierId)
                        ->where('currency_id', $cId)
                        ->first();

                    if ($exists) {
                        Capsule::table('mod_loyaltymatrix_tier_currencies')
                            ->where('id', $exists->id)
                            ->update(['min_total_paid' => $val, 'updated_at' => date('Y-m-d H:i:s')]);
                    }
                    else {
                        Capsule::table('mod_loyaltymatrix_tier_currencies')->insert([
                            'tier_id' => $tierId,
                            'currency_id' => $cId,
                            'min_total_paid' => $val,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }

            $this->redirectWithMessage('tiers', 'success', "Tier '{$data['name']}' saved successfully.");
        }
        catch (\Exception $e) {
            \logActivity("LoyaltyMatrix Error: Failed to save tier — " . $e->getMessage());
            $this->redirectWithMessage('tiers', 'error', 'Failed to save tier: ' . $e->getMessage());
        }
    }

    /**
     * Delete a tier.
     */
    private function deleteTier(): void
    {
        $tierId = (int)($_POST['tier_id'] ?? 0);

        if ($tierId <= 0) {
            $this->redirectWithMessage('tiers', 'error', 'Invalid tier ID.');
            return;
        }

        try {
            $tier = Capsule::table('mod_loyaltymatrix_tiers')
                ->where('id', $tierId)
                ->first();

            // Unassign clients from this tier
            Capsule::table('mod_loyaltymatrix_client_tiers')
                ->where('tier_id', $tierId)
                ->update(['tier_id' => null, 'updated_at' => date('Y-m-d H:i:s')]);

            // Clear highest tier references
            Capsule::table('mod_loyaltymatrix_client_tiers')
                ->where('highest_tier_id', $tierId)
                ->update(['highest_tier_id' => null]);

            Capsule::table('mod_loyaltymatrix_tiers')
                ->where('id', $tierId)
                ->delete();

            $tierName = $tier ? $tier->name : "#{$tierId}";
            \logActivity("LoyaltyMatrix: Tier '{$tierName}' deleted.");
            $this->redirectWithMessage('tiers', 'success', "Tier '{$tierName}' deleted.");
        }
        catch (\Exception $e) {
            \logActivity("LoyaltyMatrix Error: Failed to delete tier — " . $e->getMessage());
            $this->redirectWithMessage('tiers', 'error', 'Failed to delete tier.');
        }
    }

    /**
     * Toggle a tier's enabled status.
     */
    private function toggleTier(): void
    {
        $tierId = (int)($_POST['tier_id'] ?? 0);

        if ($tierId <= 0) {
            $this->redirectWithMessage('tiers', 'error', 'Invalid tier ID.');
            return;
        }

        try {
            $tier = Capsule::table('mod_loyaltymatrix_tiers')
                ->where('id', $tierId)
                ->first();

            if ($tier) {
                $newStatus = $tier->is_enabled ? 0 : 1;
                Capsule::table('mod_loyaltymatrix_tiers')
                    ->where('id', $tierId)
                    ->update(['is_enabled' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')]);

                $statusText = $newStatus ? 'enabled' : 'disabled';
                \logActivity("LoyaltyMatrix: Tier '{$tier->name}' {$statusText}.");
                $this->redirectWithMessage('tiers', 'success', "Tier '{$tier->name}' {$statusText}.");
            }
        }
        catch (\Exception $e) {
            $this->redirectWithMessage('tiers', 'error', 'Failed to toggle tier.');
        }
    }

    /**
     * Client listing with tier assignments.
     */
    private function clientList(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $search = trim($_GET['search'] ?? '');
        $filterTier = $_GET['filter_tier'] ?? '';

        $query = Capsule::table('mod_loyaltymatrix_client_tiers as ct')
            ->join('tblclients as c', 'ct.client_id', '=', 'c.id')
            ->leftJoin('mod_loyaltymatrix_tiers as t', 'ct.tier_id', '=', 't.id')
            ->leftJoin('tblcurrencies as cur', 'c.currency', '=', 'cur.id')
            ->select(
            'ct.*',
            Capsule::raw('CONCAT(c.firstname, " ", c.lastname) as client_name'),
            'c.email',
            'c.companyname',
            Capsule::raw('COALESCE(t.name, "No Tier") as tier_name'),
            Capsule::raw('COALESCE(t.discount_percent, 0) as discount_percent'),
            'cur.prefix as currency_prefix',
            'cur.suffix as currency_suffix'
        );

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('c.firstname', 'LIKE', "%{$search}%")
                    ->orWhere('c.lastname', 'LIKE', "%{$search}%")
                    ->orWhere('c.email', 'LIKE', "%{$search}%")
                    ->orWhere('c.companyname', 'LIKE', "%{$search}%");
            });
        }

        if ($filterTier !== '') {
            if ($filterTier === 'none') {
                $query->whereNull('ct.tier_id');
            }
            else {
                $query->where('ct.tier_id', (int)$filterTier);
            }
        }

        $totalCount = $query->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));

        $clients = $query->orderBy('ct.total_paid', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->toArray();

        // Get tiers for filter dropdown
        $tiers = Capsule::table('mod_loyaltymatrix_tiers')
            ->orderBy('priority', 'desc')
            ->get()
            ->toArray();

        $this->renderTemplate('clients', [
            'moduleLink' => $this->moduleLink,
            'clients' => $clients,
            'tiers' => $tiers,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'search' => $search,
            'filterTier' => $filterTier,
            'LANG' => $this->lang,
        ]);
    }

    /**
     * Single client detail view.
     */
    private function clientDetail(int $clientId): void
    {
        if ($clientId <= 0) {
            $this->redirectWithMessage('clients', 'error', 'Invalid client ID.');
            return;
        }

        $client = Capsule::table('tblclients')
            ->leftJoin('tblcurrencies as cur', 'tblclients.currency', '=', 'cur.id')
            ->where('tblclients.id', $clientId)
            ->first([
            'tblclients.id',
            'tblclients.firstname',
            'tblclients.lastname',
            'tblclients.email',
            'tblclients.companyname',
            'tblclients.datecreated',
            'tblclients.status',
            'cur.prefix as currency_prefix',
            'cur.suffix as currency_suffix'
        ]);

        if (!$client) {
            $this->redirectWithMessage('clients', 'error', 'Client not found.');
            return;
        }

        $tierEngine = new TierEngine($this->vars);
        $discountEngine = new DiscountEngine($this->vars);

        $clientTier = $tierEngine->getClientTier($clientId);
        $tierProgress = $tierEngine->getTierProgress($clientId);
        $discountLog = $discountEngine->getDiscountLog($clientId, 50);

        // Total discount amount for this client
        $totalDiscountAmount = Capsule::table('mod_loyaltymatrix_discount_log')
            ->where('client_id', $clientId)
            ->sum('discount_amount');

        $this->renderTemplate('client-detail', [
            'moduleLink' => $this->moduleLink,
            'client' => $client,
            'clientTier' => $clientTier,
            'tierProgress' => $tierProgress,
            'discountLog' => $discountLog,
            'totalDiscountAmount' => (float)$totalDiscountAmount,
            'currencyPrefix' => $client->currency_prefix ?? '$',
            'currencySuffix' => $client->currency_suffix ?? '',
            'LANG' => $this->lang,
        ]);
    }

    /**
     * Manually trigger cron recalculation.
     */
    private function runCronManual(): void
    {
        try {
            $tierEngine = new TierEngine($this->vars);
            $stats = $tierEngine->runCronBatch();

            $msg = "Recalculation complete: {$stats['total']} clients processed. "
                . "Upgraded: {$stats['upgraded']}, Downgraded: {$stats['downgraded']}, "
                . "Unchanged: {$stats['unchanged']}.";

            $this->redirectWithMessage('index', 'success', $msg);
        }
        catch (\Exception $e) {
            \logActivity("LoyaltyMatrix: Manual cron failed — " . $e->getMessage());
            $this->redirectWithMessage('index', 'error', 'Recalculation failed: ' . $e->getMessage());
        }
    }

    /**
     * Render a Smarty template.
     */
    private function renderTemplate(string $template, array $vars): void
    {
        $templateDir = LOYALTYMATRIX_DIR . '/templates/admin/';
        $templateFile = $templateDir . $template . '.tpl';

        if (!file_exists($templateFile)) {
            echo '<div class="alert alert-danger">Template not found: ' . htmlspecialchars($template) . '</div>';
            return;
        }

        // Include module CSS and JS
        echo '<link rel="stylesheet" href="../modules/addons/loyaltymatrix/assets/css/loyaltymatrix.css">';
        echo '<script src="../modules/addons/loyaltymatrix/assets/js/admin.js" defer></script>';

        $smarty = new \Smarty();
        $smarty->setTemplateDir($templateDir);
        $smarty->setCompileDir($GLOBALS['templates_compiledir'] ?? sys_get_temp_dir());
        $smarty->setCaching(\Smarty::CACHING_OFF);

        // Fetch Base Currency for formatting
        $baseCurrency = Capsule::table('tblcurrencies')->where('default', 1)->first(['prefix', 'suffix']);
        if (!isset($vars['currencyPrefix'])) {
            $vars['currencyPrefix'] = $baseCurrency ? $baseCurrency->prefix : '$';
        }
        if (!isset($vars['currencySuffix'])) {
            $vars['currencySuffix'] = $baseCurrency ? $baseCurrency->suffix : '';
        }

        foreach ($vars as $key => $value) {
            $smarty->assign($key, $value);
        }

        try {
            $smarty->display($template . '.tpl');
        }
        catch (\Exception $e) {
            echo '<div class="alert alert-danger">Template error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    /**
     * Redirect with a flash message.
     */
    private function redirectWithMessage(string $action, string $type, string $message): void
    {
        $url = $this->moduleLink;
        if ($action !== 'index') {
            $url .= '&action=' . $action;
        }
        $url .= '&msg_type=' . urlencode($type) . '&msg=' . urlencode($message);

        header('Location: ' . $url);
        exit;
    }

    /**
     * Check GitHub for updates.
     */
    private function checkGitHubUpdate(): array
    {
        $githubApiUrl = 'https://api.github.com/repos/pussycoder99/loyaltymatrix/releases/latest';

        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: WHMCS-LoyaltyMatrix-Updater'
                ]
            ]
        ];

        $context = stream_context_create($options);
        // Suppress errors on fetch so we don't break module if Github is down
        $response = @file_get_contents($githubApiUrl, false, $context);

        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['tag_name'])) {
                $latestVersion = ltrim($data['tag_name'], 'v');
                $currentVersion = $this->vars['version'] ?? '1.0.0';

                if (version_compare($currentVersion, $latestVersion, '<')) {
                    return [
                        'update_available' => true,
                        'version' => $latestVersion,
                        'download_url' => $data['zipball_url'] ?? '',
                        'changelog' => $data['body'] ?? 'No changelog provided.'
                    ];
                }
            }
        }

        return ['update_available' => false];
    }

    /**
     * Download and extract update from GitHub.
     */
    private function executeUpgrade(): void
    {
        $downloadUrl = $_POST['download_url'] ?? '';
        if (empty($downloadUrl)) {
            $this->redirectWithMessage('index', 'error', 'Invalid download URL.');
            return;
        }

        $tempZipPath = LOYALTYMATRIX_DIR . '/updates.zip';
        $extractPath = LOYALTYMATRIX_DIR . '/temp_extract';

        try {
            $options = ['http' => ['header' => 'User-Agent: WHMCS-LoyaltyMatrix-Updater']];
            $context = stream_context_create($options);
            $zipContent = @file_get_contents($downloadUrl, false, $context);
            if ($zipContent === false) {
                throw new \Exception('Failed to download update from GitHub.');
            }
            file_put_contents($tempZipPath, $zipContent);

            $zip = new \ZipArchive();
            if ($zip->open($tempZipPath) === true) {
                if (!is_dir($extractPath)) {
                    mkdir($extractPath, 0755, true);
                }
                $zip->extractTo($extractPath);
                $zip->close();

                $extractedFolders = array_diff(scandir($extractPath), ['.', '..']);
                $githubWrapperFolder = reset($extractedFolders);
                if (!$githubWrapperFolder) {
                    throw new \Exception('Invalid extraction content.');
                }

                $sourcePath = $extractPath . '/' . $githubWrapperFolder;

                $this->copyDirectory($sourcePath, LOYALTYMATRIX_DIR);

                $this->deleteDirectory($extractPath);
                @unlink($tempZipPath);

                $this->redirectWithMessage('index', 'success', 'LoyaltyMatrix has been updated successfully!');
            }
            else {
                throw new \Exception('Failed to open the downloaded ZIP file.');
            }
        }
        catch (\Exception $e) {
            \logActivity("LoyaltyMatrix Update Error: " . $e->getMessage());
            // Cleanup on error if possible
            if (file_exists($tempZipPath))
                @unlink($tempZipPath);
            if (is_dir($extractPath))
                $this->deleteDirectory($extractPath);
            $this->redirectWithMessage('index', 'error', 'Update failed: ' . $e->getMessage());
        }
    }

    /**
     * Recursively copy directory.
     */
    private function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        $dir = opendir($src);
        if ($dir) {
            while (false !== ($file = readdir($dir))) {
                if (($file != '.') && ($file != '..')) {
                    if (is_dir($src . '/' . $file)) {
                        $this->copyDirectory($src . '/' . $file, $dst . '/' . $file);
                    }
                    else {
                        copy($src . '/' . $file, $dst . '/' . $file);
                    }
                }
            }
            closedir($dir);
        }
    }

    /**
     * Recursively delete directory.
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir))
            return false;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            is_dir("$dir/$file") ? $this->deleteDirectory("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}