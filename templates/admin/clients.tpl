{* Client Tier Assignments List Template *}

{if isset($smarty.get.msg)}
    <div class="alert alert-{$smarty.get.msg_type|escape:'html'} alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        {$smarty.get.msg|escape:'html'}
    </div>
{/if}

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

{* Search & Filter *}
<div class="panel panel-default">
    <div class="panel-body">
        <form method="get" class="form-inline">
            <input type="hidden" name="module" value="loyaltymatrix">
            <input type="hidden" name="action" value="clients">

            <div class="form-group" style="margin-right: 10px;">
                <label for="search" class="sr-only">Search</label>
                <input type="text" class="form-control" id="search" name="search"
                       value="{$search|escape:'html'}" placeholder="Search by name or email...">
            </div>

            <div class="form-group" style="margin-right: 10px;">
                <label for="filter_tier" class="sr-only">Filter by Tier</label>
                <select class="form-control" id="filter_tier" name="filter_tier">
                    <option value="">All Tiers</option>
                    <option value="none" {if $filterTier === 'none'}selected{/if}>No Tier</option>
                    {foreach from=$tiers item=t}
                        <option value="{$t->id}" {if $filterTier == $t->id}selected{/if}>
                            {$t->name|escape:'html'}
                        </option>
                    {/foreach}
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
            <a href="{$moduleLink}&action=clients" class="btn btn-default">
                <i class="fas fa-undo"></i> Reset
            </a>
        </form>
    </div>
</div>

{* Client List *}
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="fas fa-users"></i> Client Tier Assignments
            <span class="badge">{$totalCount}</span>
        </h3>
    </div>
    <div class="panel-body">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Current Tier</th>
                    <th>Account Age</th>
                    <th>Total Paid</th>
                    <th>Active Services</th>
                    <th>Discount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$clients item=client}
                <tr>
                    <td>
                        <a href="clientssummary.php?userid={$client->client_id}">
                            {$client->client_name|escape:'html'}
                        </a>
                        {if $client->companyname}
                            <br><small class="text-muted">{$client->companyname|escape:'html'}</small>
                        {/if}
                    </td>
                    <td>{$client->email|escape:'html'}</td>
                    <td>
                        <span class="label label-{if $client->tier_name == 'No Tier'}default{else}success{/if}">
                            {$client->tier_name|escape:'html'}
                        </span>
                    </td>
                    <td>{$client->account_age_months} mo</td>
                    <td>{$client->currency_prefix|default:$currencyPrefix}{$client->total_paid|number_format:2}{$client->currency_suffix|default:$currencySuffix}</td>
                    <td>{$client->active_services}</td>
                    <td>
                        {if $client->discount_percent > 0}
                            <span class="label label-primary">{$client->discount_percent}%</span>
                        {else}
                            <span class="text-muted">—</span>
                        {/if}
                    </td>
                    <td>
                        <a href="{$moduleLink}&action=clientdetail&id={$client->client_id}"
                           class="btn btn-xs btn-default" title="View Details">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                {foreachelse}
                <tr>
                    <td colspan="8" class="text-center text-muted" style="padding: 30px;">
                        No client tier assignments found. Run a tier recalculation from the Dashboard.
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>

        {* Pagination *}
        {if $totalPages > 1}
        <nav aria-label="Page navigation" class="text-center">
            <ul class="pagination">
                {if $currentPage > 1}
                    <li>
                        <a href="{$moduleLink}&action=clients&page={$currentPage - 1}&search={$search|escape:'url'}&filter_tier={$filterTier|escape:'url'}">
                            &laquo;
                        </a>
                    </li>
                {/if}

                {section name=p start=1 loop=$totalPages+1}
                    <li class="{if $smarty.section.p.index == $currentPage}active{/if}">
                        <a href="{$moduleLink}&action=clients&page={$smarty.section.p.index}&search={$search|escape:'url'}&filter_tier={$filterTier|escape:'url'}">
                            {$smarty.section.p.index}
                        </a>
                    </li>
                {/section}

                {if $currentPage < $totalPages}
                    <li>
                        <a href="{$moduleLink}&action=clients&page={$currentPage + 1}&search={$search|escape:'url'}&filter_tier={$filterTier|escape:'url'}">
                            &raquo;
                        </a>
                    </li>
                {/if}
            </ul>
        </nav>
        {/if}
    </div>
</div>
