<?php
/**
 * Plugin Name: Dynamic Payment Gateways for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/wc-dynamic-payment-gateways-tcs
 * Description: WC Dynamic Payment Gateways allows you to easily restrict payment options for your customers based on their billing country.
 * Version: 1.1.3
 * Author: Tailcode Studio
 * Author URI: https://tailcode.studio/
 * Text Domain: wc-dynamic-payment-gateways-tcs
 * Domain Path: /i18n/languages/
 * Requires at least: 5.9
 * Tested up to: 6.5
 * WC tested up to: 8.7
 * Requires PHP: 8.1
 */

defined('ABSPATH') || exit;

define('WCDPG_FILE', __FILE__);

$loader = include_once dirname(__FILE__).'/vendor/autoload.php';
if (!$loader) {
    wp_die(
        wp_kses_post(__(
            'We couldn\'t find <pre>autoload.php</pre> file.',
            'wc-dynamic-payment-gateways-tcs'
        )),
        esc_html__('We\'re sorry, but it looks like the Composer autoloader is missing. This is required to run the plugin properly. Please check that you\'ve installed Composer and that the autoloader is included in your project, then try again.', 'wc-dynamic-payment-gateways-tcs')
    );
}

use TailcodeStudio\WCDPG\Plugin;
use TailcodeStudio\WCDPG\Activation;
use TailcodeStudio\WCDPG\Deactivation;

register_activation_hook(__FILE__, function () {
});

register_deactivation_hook(__FILE__, function () {
    Deactivation::deleteSettings();
});

add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) {
        Activation::showWooCommerceErrorNotice();

        return false;
    }
    Plugin::instance();
}, 11);
