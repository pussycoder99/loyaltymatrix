{* Client Detail View Template *}

{* Navigation Tabs *}
<ul class="nav nav-tabs" role="tablist" style="margin-bottom: 20px;">
    <li role="presentation">
        <a href="{$moduleLink}"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    </li>
    <li role="presentation">
        <a href="{$moduleLink}&action=tiers"><i class="fas fa-layer-group"></i> Manage Tiers</a>
    </li>
    <li role="presentation" class="active">
        <a href="{$moduleLink}&action=clients"><i class="fas fa-users"></i> Client Tiers</a>
    </li>
</ul>

<div class="row">
    {* Client Info Card *}
    <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fas fa-user"></i> Client Info</h3>
            </div>
            <div class="panel-body text-center">
                <h4>
                    <a href="clientssummary.php?userid={$client->id}">
                        {$client->firstname|escape:'html'} {$client->lastname|escape:'html'}
                    </a>
                </h4>
                {if $client->companyname}
                    <p class="text-muted">{$client->companyname|escape:'html'}</p>
                {/if}
                <p>{$client->email|escape:'html'}</p>
                <p><small>Member since: {$client->datecreated}</small></p>

                <hr>

                {* Current Tier Badge *}
                {if $clientTier && $clientTier->tier_name}
                    <div class="lm-tier-badge lm-tier-active">
                        <i class="fas fa-trophy fa-3x"></i>
                        <h3>{$clientTier->tier_name|escape:'html'}</h3>
                        <p class="lead">{$clientTier->discount_percent}% Discount</p>
                    </div>
                {else}
                    <div class="lm-tier-badge lm-tier-none">
                        <i class="fas fa-minus-circle fa-3x text-muted"></i>
                        <h3 class="text-muted">No Tier Assigned</h3>
                    </div>
                {/if}

                <hr>
                <p><strong>Total Discounts Given:</strong></p>
                <h3 class="text-danger">-{$currencyPrefix}{$totalDiscountAmount|number_format:2}{$currencySuffix}</h3>
            </div>
        </div>
    </div>

    {* Metrics & Progress *}
    <div class="col-sm-8">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fas fa-chart-line"></i> Loyalty Metrics</h3>
            </div>
            <div class="panel-body">
                {* Current Metrics *}
                <div class="row">
                    <div class="col-sm-4">
                        <div class="panel panel-info">
                            <div class="panel-body text-center">
                                <i class="fas fa-calendar fa-2x"></i>
                                <h3>{$tierProgress.metrics.account_age_months}</h3>
                                <p>Months Old</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="panel panel-info">
                            <div class="panel-body text-center">
                                <i class="fas fa-dollar-sign fa-2x"></i>
                                <h3>{$currencyPrefix}{$tierProgress.metrics.total_paid|number_format:2}{$currencySuffix}</h3>
                                <p>Total Paid</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="panel panel-info">
                            <div class="panel-body text-center">
                                <i class="fas fa-server fa-2x"></i>
                                <h3>{$tierProgress.metrics.active_services}</h3>
                                <p>Active Services</p>
                            </div>
                        </div>
                    </div>
                </div>

                {* Progress Toward Next Tier *}
                {if $tierProgress.next_tier}
                    <h4><i class="fas fa-arrow-up"></i> Progress Toward: <strong>{$tierProgress.next_tier->name|escape:'html'}</strong></h4>

                    <div class="lm-progress-section">
                        <label>Account Age ({$tierProgress.progress.account_age.current} / {$tierProgress.progress.account_age.required} months)</label>
                        <div class="progress">
                            <div class="progress-bar progress-bar-info" role="progressbar"
                                 style="width: {$tierProgress.progress.account_age.percent}%;">
                                {$tierProgress.progress.account_age.percent}%
                            </div>
                        </div>

                        <label>Total Paid ({$currencyPrefix}{$tierProgress.progress.total_paid.current|number_format:2}{$currencySuffix} / {$currencyPrefix}{$tierProgress.progress.total_paid.required|number_format:2}{$currencySuffix})</label>
                        <div class="progress">
                            <div class="progress-bar progress-bar-success" role="progressbar"
                                 style="width: {$tierProgress.progress.total_paid.percent}%;">
                                {$tierProgress.progress.total_paid.percent}%
                            </div>
                        </div>

                        <label>Active Services ({$tierProgress.progress.active_services.current} / {$tierProgress.progress.active_services.required})</label>
                        <div class="progress">
                            <div class="progress-bar progress-bar-warning" role="progressbar"
                                 style="width: {$tierProgress.progress.active_services.percent}%;">
                                {$tierProgress.progress.active_services.percent}%
                            </div>
                        </div>
                    </div>
                {else}
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> This client is at the highest available tier!
                    </div>
                {/if}
            </div>
        </div>

        {* Discount History *}
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fas fa-history"></i> Discount History</h3>
            </div>
            <div class="panel-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice</th>
                            <th>Tier</th>
                            <th>Discount %</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$discountLog item=log}
                        <tr>
                            <td>{$log->created_at}</td>
                            <td>
                                <a href="invoices.php?action=edit&id={$log->invoice_id}">
                                    #{$log->invoice_id}
                                </a>
                            </td>
                            <td><span class="label label-info">{$log->tier_name|escape:'html'}</span></td>
                            <td>{$log->discount_percent}%</td>
                            <td class="text-danger">-{$currencyPrefix}{$log->discount_amount|number_format:2}{$currencySuffix}</td>
                        </tr>
                        {foreachelse}
                        <tr>
                            <td colspan="5" class="text-center text-muted">No discount history for this client.</td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <a href="{$moduleLink}&action=clients" class="btn btn-default">
            <i class="fas fa-arrow-left"></i> Back to Client List
        </a>
    </div>
</div>
