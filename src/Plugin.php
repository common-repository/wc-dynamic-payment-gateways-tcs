<?php

namespace TailcodeStudio\WCDPG;

use WC_Customer;
use WC_Geolocation;

defined('ABSPATH') || exit;

class Plugin
{
    protected static $_instance = null;

    protected function __construct()
    {
        add_filter('woocommerce_get_settings_pages', [$this, 'wcGetSettingsPages']);
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
        add_filter('woocommerce_available_payment_gateways', [$this, 'wcAvailablePaymentGateways']);
    }

    public static function instance(): ?Plugin
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __clone()
    {
        wc_doing_it_wrong(
            __FUNCTION__,
            esc_html__(
                'We\'re sorry, but the `__clone` method is not supported by this class. Duplicating this object is not allowed.',
                'wc-dynamic-payment-gateways-tcs'
            ),
            '1.0.0'
        );
    }

    public function __wakeup()
    {
        wc_doing_it_wrong(
            __FUNCTION__,
            esc_html__(
                'We\'re sorry, but the `__wakeup` method is not supported by this class. Unserializing this object is not allowed.',
                'wc-dynamic-payment-gateways-tcs'
            ),
            '1.0.0'
        );
    }

    public function wcGetSettingsPages(): array
    {
        $settings[] = new Settings();

        return $settings;
    }

    public function adminEnqueueScripts(): void
    {
        wp_enqueue_script(
            'wcdpg-admin',
            plugin_dir_url(WCDPG_FILE).'assets/js/admin.min.js',
            ['jquery'],
            '1.1.0',
            true
        );
    }

    public function wcAvailablePaymentGateways(array $gateways): array
    {
        if (is_admin()) {
            return $gateways;
        }

        $gatewaysF = $this->filterEnabledGateways($this->getUserLocation(), $this->getEnabledGateways());
        foreach ($gatewaysF as $gateway) {
            unset($gateways[$gateway['id']]);
        }

        return $gateways;
    }

    public function filterEnabledGateways(string $location, array $gateways): array
    {
        $filteredGateways = [];
        foreach ($gateways as $gateway) {
            if ($gateway['show'] === 'is-country' && (in_array($location, $gateway['when']) || in_array(
                'ALL_C',
                $gateway['when']
            ))) {
                $filteredGateways[] = $gateway['id'];
            }
            if ($gateway['show'] === 'isnt-country' && !in_array($location, $gateway['when'])) {
                $filteredGateways[] = $gateway['id'];
            }
        }

        return array_filter($gateways, function ($gateway) use ($filteredGateways) {
            return !in_array($gateway['id'], $filteredGateways);
        });
    }

    public function getUserLocation(): string
    {
        if (is_user_logged_in()) {
            $customer = new WC_Customer(get_current_user_id());

            return $customer->get_billing_country();
        }

        if (isset($_POST, $_POST['country']) && !empty($_POST['country'])) {
            return sanitize_text_field($_POST['country']);
        }

        $geolocation     = new WC_Geolocation();
        $geolocationData = $geolocation::geolocate_ip();

        return $geolocationData['country'] ?: '';
    }

    public function getEnabledGateways(): array
    {
        $gateways = array_filter(WC()->payment_gateways()->payment_gateways, function ($gateway) {
            return $gateway->enabled !== 'no' ? $gateway : null;
        });

        $enabledGateways = [];

        foreach ($gateways as $gateway) {
            $isEnabled = get_option('wcdpg_'.$gateway->id.'_enabled');
            if (isset($isEnabled) && !empty($isEnabled) && $isEnabled === 'yes') {
                $enabledGateways[] = [
                    'id'   => $gateway->id,
                    'show' => get_option('wcdpg_'.$gateway->id.'_show'),
                    'when' => get_option('wcdpg_'.$gateway->id.'_country')
                ];
            }
        }

        return $enabledGateways;
    }

}
