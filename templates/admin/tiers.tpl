{* Tier Management List Template *}

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
    <li role="presentation" class="active">
        <a href="{$moduleLink}&action=tiers"><i class="fas fa-layer-group"></i> Manage Tiers</a>
    </li>
    <li role="presentation">
        <a href="{$moduleLink}&action=clients"><i class="fas fa-users"></i> Client Tiers</a>
    </li>
</ul>

<div class="row" style="margin-bottom: 15px;">
    <div class="col-sm-12">
        <a href="{$moduleLink}&action=addtier" class="btn btn-success">
            <i class="fas fa-plus"></i> Create New Tier
        </a>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><i class="fas fa-layer-group"></i> Loyalty Tiers</h3>
    </div>
    <div class="panel-body">
        <table class="table table-striped table-hover" id="tiersTable">
            <thead>
                <tr>
                    <th width="60">Priority</th>
                    <th>Name</th>
                    <th>Min Account Age</th>
                    <th>Min Total Paid</th>
                    <th>Min Active Services</th>
                    <th>Discount %</th>
                    <th>Clients</th>
                    <th width="80">Status</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$tiers item=tier}
                <tr class="{if !$tier->is_enabled}text-muted{/if}">
                    <td>
                        <span class="badge">{$tier->priority}</span>
                    </td>
                    <td>
                        <strong>{$tier->name|escape:'html'}</strong>
                    </td>
                    <td>{$tier->min_account_age_months} months</td>
                    <td>${$tier->min_total_paid|number_format:2}</td>
                    <td>{$tier->min_active_services}</td>
                    <td>
                        <span class="label label-primary">{$tier->discount_percent}%</span>
                    </td>
                    <td>
                        <span class="badge">{$tier->client_count}</span>
                    </td>
                    <td>
                        {if $tier->is_enabled}
                            <span class="label label-success">Active</span>
                        {else}
                            <span class="label label-danger">Disabled</span>
                        {/if}
                    </td>
                    <td>
                        <a href="{$moduleLink}&action=edittier&id={$tier->id}" class="btn btn-xs btn-default" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="post" action="{$moduleLink}&action=toggletier" style="display: inline;">
                            <input type="hidden" name="tier_id" value="{$tier->id}">
                            <button type="submit" class="btn btn-xs btn-{if $tier->is_enabled}warning{else}success{/if}" title="{if $tier->is_enabled}Disable{else}Enable{/if}">
                                <i class="fas fa-{if $tier->is_enabled}pause{else}play{/if}"></i>
                            </button>
                        </form>
                        <form method="post" action="{$moduleLink}&action=deletetier" style="display: inline;" onsubmit="return confirm('Delete tier \'{$tier->name|escape:'javascript'}\'? Clients will be unassigned.')">
                            <input type="hidden" name="tier_id" value="{$tier->id}">
                            <button type="submit" class="btn btn-xs btn-danger" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                {foreachelse}
                <tr>
                    <td colspan="9" class="text-center">
                        <p class="text-muted" style="padding: 20px;">
                            No tiers configured yet.
                            <a href="{$moduleLink}&action=addtier">Create your first tier</a>.
                        </p>
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>

<div class="panel panel-info">
    <div class="panel-body">
        <i class="fas fa-info-circle"></i>
        <strong>Tier Priority:</strong> Higher priority number = stronger tier. If a client qualifies for multiple tiers,
        the highest priority tier is assigned. Each tier requires ALL three minimums to be met (Account Age AND Total Paid AND Active Services).
    </div>
</div>
