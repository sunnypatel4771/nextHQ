<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| NexHQ — Custom Functions & Branding
|--------------------------------------------------------------------------
| This file overrides Perfex CRM defaults with NexHQ branding.
| All product-level customizations go here.
*/

// -----------------------------------------------------------------------
// APP NAME CONSTANT
// -----------------------------------------------------------------------
if (!defined('NEXHQ_APP_NAME')) {
    define('NEXHQ_APP_NAME', 'NexHQ');
}

if (!defined('NEXHQ_APP_TAGLINE')) {
    define('NEXHQ_APP_TAGLINE', 'Your All-in-One Business Automation Platform');
}

if (!defined('NEXHQ_APP_VERSION')) {
    define('NEXHQ_APP_VERSION', '1.0.0');
}

// -----------------------------------------------------------------------
// HIDE PERFEX BRANDING — Filter the help link away
// -----------------------------------------------------------------------
hooks()->add_filter('help_menu_item_link', function () {
    return admin_url('settings');
});

hooks()->add_filter('help_menu_item_text', function () {
    return 'Help & Settings';
});

// -----------------------------------------------------------------------
// ADMIN LOGIN — Override page title to NexHQ
// -----------------------------------------------------------------------
hooks()->add_action('app_init', 'nexhq_set_page_titles');

function nexhq_set_page_titles()
{
    // Override browser tab title globally if company name not set
    $CI = &get_instance();
    if (isset($CI->db)) {
        $companyname = get_option('companyname');
        if (empty($companyname)) {
            update_option('companyname', NEXHQ_APP_NAME);
        }
    }
}

// -----------------------------------------------------------------------
// REMOVE PERFEX FOOTER BRANDING in admin area
// -----------------------------------------------------------------------
hooks()->add_action('app_admin_footer', 'nexhq_admin_footer_branding');

function nexhq_admin_footer_branding()
{
    echo '<script>
        // Remove any Perfex CRM references from the DOM
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll("a[href*=\"perfexcrm.com\"]").forEach(function(el) {
                el.style.display = "none";
            });
        });
    </script>';
}
