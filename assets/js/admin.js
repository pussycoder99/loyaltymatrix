/**
 * LoyaltyMatrix for WHMCS — Admin JavaScript
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initAutoAlerts();
    });

    /**
     * Auto-dismiss alert messages after 5 seconds.
     */
    function initAutoAlerts() {
        var alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function (alert) {
            setTimeout(function () {
                if (typeof jQuery !== 'undefined') {
                    jQuery(alert).fadeOut(400, function () {
                        jQuery(this).remove();
                    });
                } else {
                    alert.style.transition = 'opacity 0.4s';
                    alert.style.opacity = '0';
                    setTimeout(function () {
                        alert.remove();
                    }, 400);
                }
            }, 5000);
        });
    }
})();
