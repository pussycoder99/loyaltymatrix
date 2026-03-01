{* Client Area — Loyalty Program Dashboard *}

<link rel="stylesheet" href="modules/addons/loyaltymatrix/assets/css/loyaltymatrix.css">

<style>
/* Modern Timeline Stepper Styles */
.lm-timeline-wrapper {
    position: relative;
    padding: 30px 0;
    margin-bottom: 30px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    overflow: hidden;
}

.lm-timeline {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    padding: 0 40px;
}

.lm-timeline::before {
    content: '';
    position: absolute;
    top: 24px;
    left: 40px;
    right: 40px;
    height: 4px;
    background: #e9ecef;
    z-index: 1;
}

.lm-timeline-progress {
    position: absolute;
    top: 24px;
    left: 40px;
    height: 4px;
    background: linear-gradient(90deg, #28a745, #20c997);
    z-index: 2;
    transition: width 0.5s ease;
}

.lm-timeline-step {
    position: relative;
    z-index: 3;
    text-align: center;
    flex: 1;
}

.lm-step-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #fff;
    border: 4px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-size: 18px;
    color: #adb5bd;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.lm-timeline-step.completed .lm-step-icon {
    border-color: #28a745;
    background: #28a745;
    color: #fff;
}

.lm-timeline-step.current .lm-step-icon {
    border-color: #20c997;
    background: #fff;
    color: #20c997;
    transform: scale(1.2);
    box-shadow: 0 0 0 4px rgba(32, 201, 151, 0.2);
}

.lm-timeline-step.locked .lm-step-icon {
    border-color: #e9ecef;
    background: #f8f9fa;
}

.lm-step-label {
    font-weight: 700;
    color: #495057;
    font-size: 14px;
    margin-bottom: 2px;
}

.lm-step-discount {
    font-size: 12px;
    color: #6c757d;
    font-weight: 600;
}

.lm-step-current-badge {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    background: #20c997;
    color: white;
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: bold;
    white-space: nowrap;
}

/* Enhanced Facility Cards */
.lm-facility-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 20px;
}

.lm-facility-card {
    flex: 1;
    min-width: 250px;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    transition: transform 0.3s, box-shadow 0.3s;
    border: 1px solid #f1f3f5;
    position: relative;
    display: flex;
    flex-direction: column;
}

.lm-facility-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.lm-facility-card.is-current {
    border: 2px solid #20c997;
}

.lm-fc-header {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 20px;
    text-center;
    border-bottom: 1px solid #f1f3f5;
    position: relative;
}

.lm-facility-card.is-current .lm-fc-header {
    background: linear-gradient(135deg, #e6fcf5, #c3fae8);
}

.lm-fc-icon {
    font-size: 32px;
    color: #ffd43b;
    margin-bottom: 10px;
}

.lm-fc-title {
    font-size: 20px;
    font-weight: 800;
    color: #212529;
    margin: 0;
}

.lm-fc-discount {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #FF4136;
    color: white;
    font-weight: bold;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 14px;
    box-shadow: 0 2px 8px rgba(255, 65, 54, 0.3);
}

.lm-fc-body {
    padding: 20px;
    flex-grow: 1;
}

.lm-fc-req-title {
    font-size: 12px;
    text-transform: uppercase;
    color: #adb5bd;
    font-weight: 700;
    margin-bottom: 10px;
    letter-spacing: 0.5px;
}

.lm-fc-req-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.lm-fc-req-list li {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    font-size: 14px;
    color: #495057;
}

.lm-fc-req-list li i {
    width: 24px;
    color: #20c997;
    font-size: 16px;
}

.lm-fc-footer {
    padding: 15px 20px;
    background: #f8f9fa;
    border-top: 1px solid #f1f3f5;
    text-align: center;
}

/* Next Tier Progress Indicator */
.lm-next-tier-progress {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    margin-bottom: 30px;
    border-left: 5px solid #339af0;
}

.lm-ntp-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.lm-ntp-icon {
    font-size: 24px;
    color: #339af0;
    margin-right: 15px;
}

.lm-ntp-title {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
}

.lm-ntp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.lm-ntp-stat {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.lm-ntp-stat-labels {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin-bottom: 8px;
    font-weight: 600;
}

.lm-ntp-stat-labels .current { color: #212529; }
.lm-ntp-stat-labels .target { color: #adb5bd; }

.lm-ntp-progress-bar {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.lm-ntp-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #339af0, #4dabf7);
    border-radius: 4px;
}
</style>

<div class="loyalty-program-page">

    {if !empty($error)}
        <div class="alert alert-danger">{$error|escape:'html'}</div>
    {else}

        {* 1. Unified Visual Stepper Timeline *}
        {if !empty($allTiers)}
        <div class="lm-timeline-wrapper">
            <h3 style="text-align: center; margin-top: 0; margin-bottom: 25px; font-weight: 700; color: #212529;">Your Loyalty Journey</h3>
            
            {* Calculate Progress Percentage based on active tier priority *}
            {assign var="totalTiers" value=$allTiers|@count}
            {assign var="activePriority" value=0}
            
            {foreach from=$allTiers item=t name=tierloop}
                {if !empty($clientTier) and $clientTier->tier_id == $t->id}
                    {assign var="activePriority" value=$smarty.foreach.tierloop.iteration}
                {/if}
            {/foreach}
            
            {assign var="progressPercent" value=0}
            {if $totalTiers > 1}
                {if $activePriority > 0}
                    {math equation="(a - 1) / (b - 1) * 100" a=$activePriority b=$totalTiers assign="progressPercent"}
                {/if}
            {/if}

            <div class="lm-timeline">
                <div class="lm-timeline-progress" style="width: {$progressPercent}%;"></div>
                
                {foreach from=$allTiers item=t name=tloop}
                    {assign var="stepStatus" value="locked"}
                    {if !empty($clientTier) and $clientTier->tier_id == $t->id}
                        {assign var="stepStatus" value="current"}
                    {elseif !empty($clientTier) and $t->priority < $clientTier->tier_priority}
                        {assign var="stepStatus" value="completed"}
                    {/if}
                    
                    <div class="lm-timeline-step {$stepStatus}">
                        {if $stepStatus eq 'current'}
                            <div class="lm-step-current-badge">YOU ARE HERE</div>
                        {/if}
                        <div class="lm-step-icon">
                            {if $stepStatus eq 'completed'}
                                <i class="fas fa-check"></i>
                            {elseif $stepStatus eq 'current'}
                                <i class="fas fa-star"></i>
                            {else}
                                <i class="fas fa-lock"></i>
                            {/if}
                        </div>
                        <div class="lm-step-label">{$t->name|escape:'html'}</div>
                        <div class="lm-step-discount">{$t->discount_percent}% OFF</div>
                    </div>
                {/foreach}
            </div>
        </div>
        {/if}

        {* 2. Detailed Progress to Next Tier *}
        {if !empty($tierProgress) and !empty($tierProgress.next_tier)}
        <div class="lm-next-tier-progress">
            <div class="lm-ntp-header">
                <i class="fas fa-rocket lm-ntp-icon"></i>
                <h4 class="lm-ntp-title">Unlock {$tierProgress.next_tier->name|escape:'html'} to get {$tierProgress.next_tier->discount_percent}% Discount</h4>
            </div>
            <p class="text-muted" style="margin-bottom: 20px; font-size: 14px;">Reach all targets below to upgrade your loyalty tier automatically.</p>
            
            <div class="lm-ntp-grid">
                <div class="lm-ntp-stat">
                    <div style="font-size: 12px; color: #868e96; margin-bottom: 5px; text-transform: uppercase;">Account Age</div>
                    <div class="lm-ntp-stat-labels">
                        <span class="current">{$tierProgress.progress.account_age.current} months</span>
                        <span class="target">Goal: {$tierProgress.progress.account_age.required}</span>
                    </div>
                    <div class="lm-ntp-progress-bar">
                        <div class="lm-ntp-progress-fill" style="width: {$tierProgress.progress.account_age.percent}%"></div>
                    </div>
                </div>

                <div class="lm-ntp-stat">
                    <div style="font-size: 12px; color: #868e96; margin-bottom: 5px; text-transform: uppercase;">Total Paid</div>
                    <div class="lm-ntp-stat-labels">
                        <span class="current">{$currencyPrefix}{$tierProgress.progress.total_paid.current|number_format:2}{$currencySuffix}</span>
                        <span class="target">Goal: {$currencyPrefix}{$tierProgress.progress.total_paid.required|number_format:2}{$currencySuffix}</span>
                    </div>
                    <div class="lm-ntp-progress-bar">
                        <div class="lm-ntp-progress-fill" style="width: {$tierProgress.progress.total_paid.percent}%"></div>
                    </div>
                </div>

                <div class="lm-ntp-stat">
                    <div style="font-size: 12px; color: #868e96; margin-bottom: 5px; text-transform: uppercase;">Active Services</div>
                    <div class="lm-ntp-stat-labels">
                        <span class="current">{$tierProgress.progress.active_services.current}</span>
                        <span class="target">Goal: {$tierProgress.progress.active_services.required}</span>
                    </div>
                    <div class="lm-ntp-progress-bar">
                        <div class="lm-ntp-progress-fill" style="width: {$tierProgress.progress.active_services.percent}%"></div>
                    </div>
                </div>
            </div>
        </div>
        {elseif empty($clientTier)}
            <div class="alert alert-info" style="border-radius: 8px;">
                <i class="fas fa-info-circle"></i> You are not currently assigned to a loyalty tier. Keep using our services to unlock your first reward tier!
            </div>
        {else}
            <div class="alert alert-success" style="border-radius: 8px; background-color: #d4edda; border-color: #c3e6cb; color: #155724;">
                <i class="fas fa-trophy fa-lg" style="margin-right: 10px;"></i>
                <strong>Maximum Tier Ahiceved!</strong> You've reached the highest loyalty tier. Thank you for your continued support!
            </div>
        {/if}


        {* 3. All Available Tiers Facility Cards *}
        {if !empty($allTiers)}
        <h3 style="margin-top: 40px; margin-bottom: 20px; font-weight: 700; color: #212529;"><i class="fas fa-gift" style="color:#ffc107;"></i> Tier Facilities & Discounts</h3>
        
        <div class="lm-facility-cards">
            {foreach from=$allTiers item=t}
            <div class="lm-facility-card {if !empty($clientTier) and $clientTier->tier_id == $t->id}is-current{/if}">
                <div class="lm-fc-header text-center">
                    {if !empty($clientTier) and $clientTier->tier_id == $t->id}
                        <div style="position:absolute; top: -1px; left: -1px; background: #20c997; color: white; padding: 4px 12px; font-size: 11px; font-weight: bold; border-radius: 12px 0 12px 0; z-index: 10;">YOUR TIER</div>
                    {/if}
                    <div class="lm-fc-discount">{$t->discount_percent}% OFF</div>
                    <div class="lm-fc-icon">
                        <i class="fas fa-medal"></i>
                    </div>
                    <h3 class="lm-fc-title">{$t->name|escape:'html'}</h3>
                </div>
                
                <div class="lm-fc-body">
                    <div class="lm-fc-req-title">Requirements to Unlock</div>
                    <ul class="lm-fc-req-list">
                        <li>
                            <i class="fas fa-clock"></i> 
                            <span><strong>{$t->min_account_age_months}</strong> Months Old Account</span>
                        </li>
                        <li>
                            <i class="fas fa-wallet"></i> 
                            <span><strong>{$currencyPrefix}{$t->min_total_paid|number_format:2}{$currencySuffix}</strong> Total Spent</span>
                        </li>
                        <li>
                            <i class="fas fa-box-open"></i> 
                            <span><strong>{$t->min_active_services}</strong> Active Services</span>
                        </li>
                    </ul>
                </div>
                
                <div class="lm-fc-footer">
                    {if !empty($clientTier) and $clientTier->tier_id == $t->id}
                        <span style="color: #20c997; font-weight: bold;"><i class="fas fa-check-circle"></i> Active</span>
                    {elseif !empty($clientTier) and $t->priority < $clientTier->tier_priority}
                        <span style="color: #6c757d; font-weight: bold;"><i class="fas fa-lock-open"></i> Unlocked</span>
                    {else}
                        <span style="color: #adb5bd; font-weight: bold;"><i class="fas fa-lock"></i> Locked</span>
                    {/if}
                </div>
            </div>
            {/foreach}
        </div>
        {/if}

        {* 4. Discount History *}
        {if !empty($discountLog)}
        <div class="panel panel-default" style="margin-top: 40px; border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <div class="panel-heading" style="background: #f8f9fa; padding: 20px; border-bottom: 1px solid #e9ecef;">
                <h3 class="panel-title" style="font-weight: 700; color: #495057;"><i class="fas fa-history" style="color: #6c757d;"></i> Your Savings History</h3>
            </div>
            <div class="panel-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table table-hover" style="margin: 0;">
                        <thead style="background: #fff;">
                            <tr>
                                <th style="border-top: none; padding: 15px 20px; color: #868e96; font-size: 12px; text-transform: uppercase;">Date</th>
                                <th style="border-top: none; padding: 15px 20px; color: #868e96; font-size: 12px; text-transform: uppercase;">Invoice</th>
                                <th style="border-top: none; padding: 15px 20px; color: #868e96; font-size: 12px; text-transform: uppercase;">Tier Applied</th>
                                <th style="border-top: none; padding: 15px 20px; color: #868e96; font-size: 12px; text-transform: uppercase; text-align: right;">Amount Saved</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$discountLog item=log}
                            <tr>
                                <td style="padding: 15px 20px; vertical-align: middle;">{$log->created_at|date_format:"%b %d, %Y"}</td>
                                <td style="padding: 15px 20px; vertical-align: middle;">
                                    <a href="viewinvoice.php?id={$log->invoice_id}" style="font-weight: 600;">
                                        #{$log->invoice_id}
                                    </a>
                                </td>
                                <td style="padding: 15px 20px; vertical-align: middle;">
                                    <span style="background: #e9ecef; color: #495057; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                        {$log->tier_name|escape:'html'} ({$log->discount_percent}%)
                                    </span>
                                </td>
                                <td class="text-success" style="padding: 15px 20px; vertical-align: middle; text-align: right; font-weight: 700;">
                                    -{$currencyPrefix}{$log->discount_amount|number_format:2}{$currencySuffix}
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        {/if}

    {/if}
</div>
