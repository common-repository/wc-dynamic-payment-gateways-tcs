<?php

namespace TailcodeStudio\WCDPG;

defined('ABSPATH') || exit;

class Deactivation
{
    public static function deleteSettings(): void
    {
        if (class_exists('WooCommerce')) {
            foreach (WC()->payment_gateways()->payment_gateways as $gateway) {
                $gatewayID = $gateway->id;

                delete_option('wcdpg_'.$gatewayID.'_enabled');
                delete_option('wcdpg_'.$gatewayID.'_show');
                delete_option('wcdpg_'.$gatewayID.'_country');
            }
        }
    }
}
