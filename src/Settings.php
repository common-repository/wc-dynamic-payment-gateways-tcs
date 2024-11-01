<?php

namespace TailcodeStudio\WCDPG;

use WC_Admin_Settings;
use WC_Settings_Page;
use GUMP;

defined('ABSPATH') || exit;

class Settings extends WC_Settings_Page
{
    public function __construct()
    {
        $this->id    = 'wcdpg';
        $this->label = esc_html__('Dynamic Payment Gateways', 'wc-dynamic-payment-gateways-tcs');
        $this->initHooks();
    }

    protected function initHooks(): void
    {
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_page'), 20);
        add_action('woocommerce_settings_'.$this->id, array($this, 'output'));
        add_action('woocommerce_sections_'.$this->id, array($this, 'output_sections'));
        add_action('woocommerce_settings_save_'.$this->id, array($this, 'save'));
    }

    public function output(): void
    {
        $gateways = $this->getEnabledGateways();

        ob_start();

        if (!empty($gateways)) {
            $settings = $this->get_settings();
            WC_Admin_Settings::output_fields($settings);
        } else {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__(
                    'We\'re sorry, but it looks like you haven\'t enabled any payment methods on your website. To enable payment methods, please go to WooCommerce -> Settings -> Payments and activate one or more methods. Once you\'ve done that, come back to this page and configure Dynamic Payment Gateways.',
                    'wc-dynamic-payment-gateways-tcs'
                )
            );
        }

        echo ob_get_clean();
    }

    public function getEnabledGateways(): array
    {
        return array_filter(WC()->payment_gateways()->payment_gateways, function ($gateway) {
            return $gateway->enabled !== 'no' ? $gateway : null;
        });
    }

    public function get_settings(): array
    {
        $gateways = $this->getEnabledGateways();

        $settings = [];
        foreach ($gateways as $gateway) {
            $settings[] = [
                'title' => '',
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'wcdpg_'.$gateway->id.'_options',
            ];
            $settings[] = [
                'title'   => esc_html($gateway->title),
                'desc'    => esc_html__(
                    'Enable',
                    'wc-dynamic-payment-gateways-tcs'
                ),
                'id'      => 'wcdpg_'.$gateway->id.'_enabled',
                'class'   => 'wcdpg-toggle '.$gateway->id,
                'default' => 'no',
                'type'    => 'checkbox',
            ];
            $settings[] = [
                'title'    => esc_html__('Show when', 'wc-dynamic-payment-gateways-tcs'),
                'id'       => 'wcdpg_'.$gateway->id.'_show',
                'type'     => 'select',
                'class'    => 'wc-enhanced-select wcdpg-show-if-'.$gateway->id,
                'required' => true,
                'options'  => [
                    'is-country'   => esc_html__('Country is', 'wc-dynamic-payment-gateways-tcs'),
                    'isnt-country' => esc_html__('Country is NOT', 'wc-dynamic-payment-gateways-tcs'),
                ],
            ];
            $settings[] = [
                'title'    => null,
                'id'       => 'wcdpg_'.$gateway->id.'_country',
                'type'     => 'multiselect',
                'required' => true,
                'class'    => 'chosen_select wcdpg-show-if-'.$gateway->id,
                'options'  => $this->getModifiedCountries()
            ];
            $settings[] = [
                'type' => 'sectionend',
                'id'   => 'wcdpg_'.$gateway->id.'_options',
            ];
        }

        return apply_filters('tailcodestudio/wcdpg/wc_settings', $settings);
    }

    public function getModifiedCountries(): array
    {
        return [
                   'ALL_C' => esc_html__('All Countries', 'wc-dynamic-payment-gateways-tcs')
               ] + WC()->countries->get_countries();
    }

    public function save(): bool
    {
        $settings = $this->get_settings();

        if (empty($_POST)) {
            return false;
        }

        $data = [];

        $gateways  = $this->getEnabledGateways();
        $countries = implode(';', $this->getModifiedCountries());

        if (!$gateways) {
            return false;
        }

        $gump = new GUMP();

        $validationRules = [];
        $filterRules     = [];

        foreach ($gateways as $gateway) {
            $validationRules['wcdpg_'.$gateway->id.'_enabled'] = 'boolean';
            $validationRules['wcdpg_'.$gateway->id.'_show']    = 'alpha_dash|between_len,10;12';
            $validationRules['wcdpg_'.$gateway->id.'_country'] = 'valid_array_size_greater,1';

            $filterRules['wcdpg_'.$gateway->id.'_enabled'] = 'sanitize_string|trim|boolean';
            $filterRules['wcdpg_'.$gateway->id.'_show']    = 'sanitize_string|trim';

            if (isset($_POST['wcdpg_'.$gateway->id.'_enabled']) && !empty($_POST['wcdpg_'.$gateway->id.'_enabled'])) {
                $data['wcdpg_'.$gateway->id.'_enabled'] = intval($_POST['wcdpg_'.$gateway->id.'_enabled']);
                $data['wcdpg_'.$gateway->id.'_show']    = sanitize_text_field($_POST['wcdpg_'.$gateway->id.'_show']);
                $data['wcdpg_'.$gateway->id.'_country'] = array_filter(array_map(
                    'wc_clean',
                    $_POST['wcdpg_'.$gateway->id.'_country']
                ));
            }
        }

        $gump->validation_rules($validationRules);
        $gump->filter_rules($filterRules);
        $data = $gump->run($data);

        if ($gump->errors()) {
            return false;
        }

        foreach ($data as $itemK => $itemV) {
            $paymentGatewayID = str_replace('_enabled', '', $itemK);

            if (strpos($itemK, '_enabled') !== false && $itemV === '1') {
                $paymentGatewayCountries = $data[$paymentGatewayID.'_country'];
                $paymentGatewayCondition = $data[$paymentGatewayID.'_show'];

                if (isset($paymentGatewayCountries) && !empty($paymentGatewayCountries) && is_array($paymentGatewayCountries) && in_array(
                    'ALL_C',
                    $paymentGatewayCountries
                ) && $paymentGatewayCondition !== 'is-country') {
                    return false;
                }
                if (!isset($paymentGatewayCountries)) {
                    return false;
                }
            }
        }

        WC_Admin_Settings::save_fields($settings);

        return true;
    }


}
