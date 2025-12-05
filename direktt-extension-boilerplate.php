<?php

/**
 * Plugin Name: Direktt Extension BoilerPlate
 * Plugin URI: https://direktt.com
 * Description: Minimal Direktt Extension Boilerplate.
 * Version: 1.0.0
 * Author: Direktt
 * Author URI: https://direktt.com
 * License: GPL2
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'direktt_extension_boilerplate_init');
add_action('plugins_loaded', 'direktt_extension_boilerplate_activation_check', -20);

// Plugin into Direktt Settings in wp-admin
add_action('direktt_setup_settings_pages', 'direktt_extension_boilerplate_setup_settings_pages');

// Plugin into User Profile in Direktt mobile app
add_action('direktt_setup_profile_tools', 'direktt_extension_boilerplate_setup_profile_tool');

function direktt_extension_boilerplate_init()
{
    add_action('direktt_enqueue_public_scripts', 'direktt_extension_boilerplate_enqueue_public_assets');
}

function direktt_extension_boilerplate_activation_check()
{

    if (! function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $required_plugin = 'direktt/direktt.php';
    $is_required_active = is_plugin_active($required_plugin)
        || (is_multisite() && is_plugin_active_for_network($required_plugin));

    if (! $is_required_active) {
        // Deactivate this plugin
        deactivate_plugins(plugin_basename(__FILE__));

        // Prevent the “Plugin activated.” notice
        if (isset($_GET['activate'])) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Justification: not a form processing, just removing a query var.
            unset($_GET['activate']);
        }

        // Show an error notice for this request
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error is-dismissible"><p>'
                . esc_html__('Boilerplate activation failed: The Direktt WordPress Plugin must be active first.', 'direktt-extension-boilerplate')
                . '</p></div>';
        });

        // Optionally also show the inline row message in the plugins list
        add_action(
            'after_plugin_row_direktt-extension-boilerplate/direktt-extension-boilerplate.php',
            function () {
                echo '<tr class="plugin-update-tr"><td colspan="3" style="box-shadow:none;">'
                    . '<div style="color:#b32d2e;font-weight:bold;">'
                    . esc_html__('Boilerplate requires the Direktt WordPress Plugin to be active. Please activate it first.', 'direktt-extension-boilerplate')
                    . '</div></td></tr>';
            },
            10,
            0
        );
    }
}

function direktt_extension_boilerplate_enqueue_public_assets()
{

    $direktt_user = Direktt_User::direktt_get_current_user();

    if ($direktt_user) {
        wp_enqueue_script(
            'direktt-extension-boilerplate-public',
            plugin_dir_url(__FILE__) . 'js/direktt-extension-boilerplate-public.js',
            array('jquery', 'direktt_public'),
            '',
            [
                'in_footer' => true,
            ]
        );
    }

}

function direktt_extension_boilerplate_setup_settings_pages()
{
    Direktt::add_settings_page(
        array(
            "id" => "extension-boilerplate",
            "label" => __('Extension Boilerplate Settings', 'direktt-extension-boilerplate'),
            "callback" => 'direktt_extension_boilerplate_render_settings',
            "priority" => 1,
            "cssEnqueueArray" => [
                array(
                    "handle" => "direktt-extension-boilerplate",
                    "src" => plugin_dir_url(__FILE__) . 'css/direktt-extension-boilerplate.css'
                ),
            ],
            "jsEnqueueArray" => [
                array(
                    "handle" => "direktt-extension-boilerplate-settings",
                    "src" => plugin_dir_url(__FILE__) . 'js/direktt-extension-boilerplate-settings.js',
                    "deps" => array('jquery'),
                )
            ]
        )
    );
}

function direktt_extension_boilerplate_render_settings()
{
    echo( __('<h3 class="direktt-extension-boilerplate">Direktt Extension Boilerplate Settings Go Here.</h3>', 'direktt-extension-boilerplate') );
}

function direktt_extension_boilerplate_setup_profile_tool()
{
    Direktt_Profile::add_profile_tool(
        array(
            "id" => "direktt-boilerplate-profile-tool",
            "label" => __('Direktt Extension Tool', 'direktt-extension-boilerplate'),
            "callback" => 'direktt_extension_boilerplate_render_profile_tool',
            "categories" => [],
            "tags" => [],
            "priority" => 1,
            "cssEnqueueArray" => [
                array(
                    "handle" => "direktt-extension-boilerplate",
                    "src" => plugin_dir_url(__FILE__) . 'css/direktt-extension-boilerplate.css'
                ),
            ],
            "jsEnqueueArray" => [
                array(
                    "handle" => "direktt-extension-boilerplate-profile",
                    "src" => plugin_dir_url(__FILE__) . 'js/direktt-extension-boilerplate-profile.js',
                    "deps" => array('jquery', 'direktt-extension-boilerplate-public'),
                )
            ]
        )
    );
}

function direktt_extension_boilerplate_render_profile_tool()
{
    echo( __('<h3 class="direktt-extension-boilerplate">Direktt Extension Boilerplate Profile Interface Goes Here.</h3>', 'direktt-extension-boilerplate') );
}