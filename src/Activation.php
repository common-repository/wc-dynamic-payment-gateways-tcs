<?php

namespace TailcodeStudio\WCDPG;

defined('ABSPATH') || exit;

class Activation
{
    public static function showWooCommerceErrorNotice(): void
    {
        add_action('admin_notices', [__CLASS__, 'getWooCommerceErrorNotice']);
    }

    public static function getWooCommerceErrorNotice(): string
    {
        $class   = 'notice notice-error';
        $message = esc_html__(
            'To use this plugin, you need to have WooCommerce installed and activated on your website. Please install and activate WooCommerce, then try again.',
            'wc-dynamic-payment-gateways-tcs'
        );

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
    }
}
