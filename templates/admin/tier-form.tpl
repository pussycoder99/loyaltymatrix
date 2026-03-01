{* Tier Create/Edit Form Template *}

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

{if isset($smarty.get.msg)}
    <div class="alert alert-{$smarty.get.msg_type|escape:'html'} alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        {$smarty.get.msg|escape:'html'}
    </div>
{/if}

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="fas fa-{if $isEdit}edit{else}plus{/if}"></i>
            {if $isEdit}Edit Tier: {$tier->name|escape:'html'}{else}Create New Tier{/if}
        </h3>
    </div>
    <div class="panel-body">
        <form method="post" action="{$moduleLink}&action=savetier" class="form-horizontal" id="tierForm">
            {if $isEdit}
                <input type="hidden" name="tier_id" value="{$tier->id}">
            {/if}

            <div class="form-group">
                <label for="name" class="col-sm-3 control-label">Tier Name <span class="text-danger">*</span></label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" id="name" name="name"
                           value="{if $isEdit}{$tier->name|escape:'html'}{/if}"
                           placeholder="e.g. Gold, Platinum, VIP" required>
                    <span class="help-block">The display name for this loyalty tier.</span>
                </div>
            </div>

            <hr>
            <h4 style="margin-left: 15px;"><i class="fas fa-sliders-h"></i> Qualification Criteria</h4>
            <p class="text-muted" style="margin-left: 15px;">Client must meet ALL three criteria below to qualify for this tier.</p>

            <div class="form-group">
                <label for="min_account_age_months" class="col-sm-3 control-label">Min Account Age (Months)</label>
                <div class="col-sm-4">
                    <input type="number" class="form-control" id="min_account_age_months" name="min_account_age_months"
                           value="{if $isEdit}{$tier->min_account_age_months}{else}0{/if}" min="0">
                    <span class="help-block">Months since client registered.</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Min Total Paid Requirements</label>
                <div class="col-sm-6">
                    <p class="text-muted" style="margin-top: 5px; margin-bottom: 15px;">Set the minimum spend required for each active currency.</p>
                    
                    {foreach from=$currencies item=c}
                        {assign var="val" value="0"}
                        {if isset($tierCurrencies[$c->id])}
                            {assign var="val" value=$tierCurrencies[$c->id]}
                        {/if}
                        <div class="input-group" style="margin-bottom: 10px;">
                            <span class="input-group-addon" style="min-width: 60px;">{$c->code} {$c->prefix}</span>
                            <input type="number" class="form-control" name="currency_thresholds[{$c->id}]"
                                   value="{$val}" min="0" step="0.01" placeholder="0.00">
                            {if $c->suffix}
                                <span class="input-group-addon">{$c->suffix}</span>
                            {/if}
                        </div>
                    {foreachelse}
                        <div class="alert alert-warning">No active currencies found in WHMCS.</div>
                    {/foreach}
                    <span class="help-block">Sum of all fully paid invoices in the client's local currency.</span>
                </div>
            </div>

            <div class="form-group">
                <label for="min_active_services" class="col-sm-3 control-label">Min Active Services</label>
                <div class="col-sm-4">
                    <input type="number" class="form-control" id="min_active_services" name="min_active_services"
                           value="{if $isEdit}{$tier->min_active_services}{else}0{/if}" min="0">
                    <span class="help-block">Number of services with "Active" status.</span>
                </div>
            </div>

            <hr>
            <h4 style="margin-left: 15px;"><i class="fas fa-percentage"></i> Discount & Priority</h4>

            <div class="form-group">
                <label for="discount_percent" class="col-sm-3 control-label">Discount Percentage</label>
                <div class="col-sm-4">
                    <div class="input-group">
                        <input type="number" class="form-control" id="discount_percent" name="discount_percent"
                               value="{if $isEdit}{$tier->discount_percent}{else}0{/if}" min="0" max="100" step="0.01">
                        <span class="input-group-addon">%</span>
                    </div>
                    <span class="help-block">Applied to recurring invoices as a negative line item.</span>
                </div>
            </div>

            <div class="form-group">
                <label for="priority" class="col-sm-3 control-label">Priority</label>
                <div class="col-sm-4">
                    <input type="number" class="form-control" id="priority" name="priority"
                           value="{if $isEdit}{$tier->priority}{else}0{/if}" min="0">
                    <span class="help-block">Higher number = higher tier. If client qualifies for multiple tiers, highest priority wins.</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Enabled</label>
                <div class="col-sm-6">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="is_enabled" value="1"
                               {if !$isEdit || $tier->is_enabled}checked{/if}>
                        Active and available for assignment
                    </label>
                </div>
            </div>

            <hr>

            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-6">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> {if $isEdit}Save Changes{else}Create Tier{/if}
                    </button>
                    <a href="{$moduleLink}&action=tiers" class="btn btn-default">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
