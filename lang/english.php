<?php

defined("WHMCS") or die("Access Denied");

$_ADDONLANG['module_title'] = 'Loyalty Program';
$_ADDONLANG['module_description'] = 'LoyaltyMatrix — Your Loyalty Tier System';

// Dashboard
$_ADDONLANG['dashboard_title'] = 'Dashboard';
$_ADDONLANG['total_tiers'] = 'Total Tiers';
$_ADDONLANG['total_clients_with_tiers'] = 'Clients with Tiers';
$_ADDONLANG['total_discounts_applied'] = 'Discounts Applied';
$_ADDONLANG['total_discounted'] = 'Total Discounted';
$_ADDONLANG['clients_per_tier'] = 'Clients Per Tier';
$_ADDONLANG['revenue_per_tier'] = 'Revenue Per Tier';
$_ADDONLANG['recent_discount_activity'] = 'Recent Discount Activity';
$_ADDONLANG['run_recalculation'] = 'Run Recalculation Now';
$_ADDONLANG['create_new_tier'] = 'Create New Tier';

// Tier Management
$_ADDONLANG['manage_tiers'] = 'Manage Tiers';
$_ADDONLANG['tier_name'] = 'Tier Name';
$_ADDONLANG['min_account_age'] = 'Min Account Age (Months)';
$_ADDONLANG['min_total_paid'] = 'Min Total Paid';
$_ADDONLANG['min_active_services'] = 'Min Active Services';
$_ADDONLANG['discount_percent'] = 'Discount %';
$_ADDONLANG['priority'] = 'Priority';
$_ADDONLANG['status'] = 'Status';
$_ADDONLANG['actions'] = 'Actions';
$_ADDONLANG['active'] = 'Active';
$_ADDONLANG['disabled'] = 'Disabled';
$_ADDONLANG['edit'] = 'Edit';
$_ADDONLANG['delete'] = 'Delete';
$_ADDONLANG['enable'] = 'Enable';
$_ADDONLANG['disable'] = 'Disable';
$_ADDONLANG['no_tiers_found'] = 'No tiers configured yet.';
$_ADDONLANG['create_first_tier'] = 'Create your first tier';
$_ADDONLANG['tier_priority_info'] = 'Higher priority number = stronger tier. If a client qualifies for multiple tiers, the highest priority tier is assigned.';

// Tier Form
$_ADDONLANG['create_tier'] = 'Create New Tier';
$_ADDONLANG['edit_tier'] = 'Edit Tier';
$_ADDONLANG['tier_name_placeholder'] = 'e.g. Gold, Platinum, VIP';
$_ADDONLANG['tier_name_help'] = 'The display name for this loyalty tier.';
$_ADDONLANG['qualification_criteria'] = 'Qualification Criteria';
$_ADDONLANG['criteria_help'] = 'Client must meet ALL three criteria below to qualify for this tier.';
$_ADDONLANG['account_age_help'] = 'Months since client registered.';
$_ADDONLANG['total_paid_help'] = 'Sum of all fully paid invoices.';
$_ADDONLANG['active_services_help'] = 'Number of services with Active status.';
$_ADDONLANG['discount_priority'] = 'Discount & Priority';
$_ADDONLANG['discount_help'] = 'Applied to recurring invoices as a negative line item.';
$_ADDONLANG['priority_help'] = 'Higher number = higher tier. If client qualifies for multiple tiers, highest priority wins.';
$_ADDONLANG['enabled_label'] = 'Active and available for assignment';
$_ADDONLANG['save_changes'] = 'Save Changes';
$_ADDONLANG['cancel'] = 'Cancel';
$_ADDONLANG['tier_saved'] = 'Tier saved successfully.';
$_ADDONLANG['tier_deleted'] = 'Tier deleted.';
$_ADDONLANG['tier_name_required'] = 'Tier name is required.';

// Client List
$_ADDONLANG['client_tiers'] = 'Client Tiers';
$_ADDONLANG['search_placeholder'] = 'Search by name or email...';
$_ADDONLANG['all_tiers'] = 'All Tiers';
$_ADDONLANG['no_tier'] = 'No Tier';
$_ADDONLANG['search'] = 'Search';
$_ADDONLANG['reset'] = 'Reset';
$_ADDONLANG['client'] = 'Client';
$_ADDONLANG['email'] = 'Email';
$_ADDONLANG['current_tier'] = 'Current Tier';
$_ADDONLANG['account_age'] = 'Account Age';
$_ADDONLANG['total_paid'] = 'Total Paid';
$_ADDONLANG['active_services'] = 'Active Services';
$_ADDONLANG['discount'] = 'Discount';
$_ADDONLANG['view'] = 'View';

// Client Detail
$_ADDONLANG['client_info'] = 'Client Info';
$_ADDONLANG['member_since'] = 'Member since';
$_ADDONLANG['total_discounts_given'] = 'Total Discounts Given';
$_ADDONLANG['loyalty_metrics'] = 'Loyalty Metrics';
$_ADDONLANG['months_old'] = 'Months Old';
$_ADDONLANG['progress_toward'] = 'Progress Toward';
$_ADDONLANG['highest_tier_reached'] = 'Congratulations! You\'ve reached the highest loyalty tier.';
$_ADDONLANG['discount_history'] = 'Discount History';
$_ADDONLANG['no_discount_history'] = 'No discount history for this client.';
$_ADDONLANG['back_to_clients'] = 'Back to Client List';

// Client Area
$_ADDONLANG['loyalty_program'] = 'Loyalty Program';
$_ADDONLANG['no_tier_yet'] = 'No Tier Yet';
$_ADDONLANG['no_tier_message'] = 'Keep using our services to unlock loyalty rewards!';
$_ADDONLANG['your_loyalty_stats'] = 'Your Loyalty Stats';
$_ADDONLANG['tiers_and_benefits'] = 'Loyalty Tiers & Benefits';
$_ADDONLANG['current_label'] = 'Current';
$_ADDONLANG['your_discount_history'] = 'Your Discount History';
$_ADDONLANG['months'] = 'months';
$_ADDONLANG['months_account_age'] = 'months account age';
$_ADDONLANG['total_paid_label'] = 'total paid';
$_ADDONLANG['active_services_label'] = 'active services';
$_ADDONLANG['meet_all_criteria'] = 'Meet all three criteria to reach the next tier!';

// Misc
$_ADDONLANG['error_generic'] = 'An error occurred. Please try again.';
$_ADDONLANG['confirm_delete_tier'] = 'Delete this tier? Clients will be unassigned.';
$_ADDONLANG['confirm_recalculation'] = 'Run tier recalculation for all clients?';
$_ADDONLANG['recalculation_complete'] = 'Tier recalculation complete.';
$_ADDONLANG['date'] = 'Date';
$_ADDONLANG['invoice'] = 'Invoice';
$_ADDONLANG['tier'] = 'Tier';
$_ADDONLANG['amount'] = 'Amount';
$_ADDONLANG['saved'] = 'Saved';
