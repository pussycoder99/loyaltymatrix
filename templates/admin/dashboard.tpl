{* Admin Dashboard Template *}

{if isset($smarty.get.msg)}
    <div class="alert alert-{$smarty.get.msg_type|escape:'html'} alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        {$smarty.get.msg|escape:'html'}
    </div>
{/if}

<div class="loyaltymatrix-dashboard">

    {* Navigation Tabs *}
    <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 20px;">
        <li role="presentation" class="active">
            <a href="{$moduleLink}"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        </li>
        <li role="presentation">
            <a href="{$moduleLink}&action=tiers"><i class="fas fa-layer-group"></i> Manage Tiers</a>
        </li>
        <li role="presentation">
            <a href="{$moduleLink}&action=clients"><i class="fas fa-users"></i> Client Tiers</a>
        </li>
    </ul>

    {if isset($update) && $update.update_available}
        <div class="alert alert-warning" style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong><i class="fas fa-exclamation-circle"></i> Update Available!</strong> A new version (<strong>{$update.version|escape:'html'}</strong>) of LoyaltyMatrix is available.
                    <div style="margin-top: 10px; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 4px;">
                        <em>Release Notes:</em><br>
                        {$update.changelog|nl2br}
                    </div>
                </div>
                <div>
                    <form method="post" action="{$moduleLink}&action=runupdate">
                        <input type="hidden" name="download_url" value="{$update.download_url|escape:'html'}">
                        <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('WARNING: This will overwrite your module files with the latest version from GitHub. It is strongly recommended to backup your WHMCS files first. Continue?')">
                            <i class="fas fa-cloud-download-alt"></i> 1-Click Upgrade
                        </button>
                    </form>
                </div>
            </div>
        </div>
    {/if}

    {* Stats Cards *}
    <div class="row">
        <div class="col-sm-3">
            <div class="panel panel-default lm-stat-card">
                <div class="panel-body text-center">
                    <i class="fas fa-layer-group fa-2x text-primary"></i>
                    <h2>{$totalTiers}</h2>
                    <p class="text-muted">Total Tiers</p>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="panel panel-default lm-stat-card">
                <div class="panel-body text-center">
                    <i class="fas fa-users fa-2x text-success"></i>
                    <h2>{$totalAssigned}</h2>
                    <p class="text-muted">Clients with Tiers</p>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="panel panel-default lm-stat-card">
                <div class="panel-body text-center">
                    <i class="fas fa-tags fa-2x text-warning"></i>
                    <h2>{$totalDiscounts->total_count|default:0}</h2>
                    <p class="text-muted">Discounts Applied</p>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="panel panel-default lm-stat-card">
                <div class="panel-body text-center">
                    <i class="fas fa-dollar-sign fa-2x text-danger"></i>
                    <h2>{$currencyPrefix}{$totalDiscounts->total_amount|number_format:2|default:'0.00'}{$currencySuffix}</h2>
                    <p class="text-muted">Total Discounted</p>
                </div>
            </div>
        </div>
    </div>

    {* Quick Actions *}
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-sm-12">
            <form method="post" action="{$moduleLink}&action=runcron" style="display: inline;">
                <button type="submit" class="btn btn-primary" onclick="return confirm('Run tier recalculation for all clients?')">
                    <i class="fas fa-sync-alt"></i> Run Recalculation Now
                </button>
            </form>
            <a href="{$moduleLink}&action=addtier" class="btn btn-success">
                <i class="fas fa-plus"></i> Create New Tier
            </a>
        </div>
    </div>

    {* Clients Per Tier Table *}
    <div class="row">
        <div class="col-sm-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fas fa-chart-pie"></i> Clients Per Tier</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tier</th>
                                <th>Discount</th>
                                <th>Clients</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$clientsPerTier item=row}
                            <tr>
                                <td>
                                    <span class="label label-{if $row->tier_name == 'No Tier'}default{else}success{/if}">
                                        {$row->tier_name|escape:'html'}
                                    </span>
                                </td>
                                <td>{$row->discount_percent}%</td>
                                <td><strong>{$row->client_count}</strong></td>
                            </tr>
                            {foreachelse}
                            <tr>
                                <td colspan="3" class="text-center text-muted">No tier data yet. Run recalculation or wait for daily cron.</td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {* Revenue Per Tier *}
        <div class="col-sm-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fas fa-dollar-sign"></i> Revenue Per Tier</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tier</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$revenuePerTier item=row}
                            <tr>
                                <td>
                                    <span class="label label-{if $row->tier_name == 'No Tier'}default{else}info{/if}">
                                        {$row->tier_name|escape:'html'}
                                    </span>
                                </td>
                                <td><strong>{$currencyPrefix}{$row->total_revenue|number_format:2}{$currencySuffix}</strong></td>
                            </tr>
                            {foreachelse}
                            <tr>
                                <td colspan="2" class="text-center text-muted">No revenue data available.</td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {* Recent Discount Activity *}
    <div class="row">
        <div class="col-sm-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fas fa-history"></i> Recent Discount Activity</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Invoice</th>
                                <th>Tier</th>
                                <th>Discount</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$recentDiscounts item=row}
                            <tr>
                                <td>{$row->created_at}</td>
                                <td>
                                    <a href="clientssummary.php?userid={$row->client_id}">
                                        {$row->client_name|escape:'html'}
                                    </a>
                                </td>
                                <td>
                                    <a href="invoices.php?action=edit&id={$row->invoice_id}">
                                        #{$row->invoice_id}
                                    </a>
                                </td>
                                <td><span class="label label-info">{$row->tier_name|escape:'html'}</span></td>
                                <td>{$row->discount_percent}%</td>
                                <td class="text-danger">-{$currencyPrefix}{$row->discount_amount|number_format:2}{$currencySuffix}</td>
                            </tr>
                            {foreachelse}
                            <tr>
                                <td colspan="6" class="text-center text-muted">No discounts applied yet.</td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
