<?php

/**
 * Mybizna Pods Migration
 *
 * @package           MybiznaPodMigration
 * @author            Dedan Irungu
 * @copyright         2022 Mybizna.com
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Mybizna Pods Migration
 * Plugin URI:        https://wordpress.org/plugins/mybizna-pod-Migration/
 * Description:       Mybizna Pods Migration.
 * Version:           1.0.0
 * Requires at least: 5.4
 * Requires PHP:      7.2
 * Author:            Dedan Irungu
 * Author URI:        https://mybizna.com
 * Text Domain:       mybizna-pod-migration
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

require_once 'Config.php';

function mybizna_pods_migration()
{

    if (is_admin() && isset($_GET['pods-migration'])) {

        $config = new Config();

        $config->setup();
    }

}

function mybizna_pods_migration_link($wp_admin_bar)
{

    $url = add_query_arg('_wpnonce', wp_create_nonce('pods-migration'), admin_url() . '?pods-migration=1');

    $args = array(
        'id' => 'pods-migration',
        'title' => 'Pods Migration',
        'href' => $url,
        'meta' => array(
            'title' => 'Pods Migration',
            'class' => 'mybizna_pods_migration_link',
        ),
    );

    $wp_admin_bar->add_node($args);

    $custom_css = '.mybizna_pods_migration_link { background: #EF5B24 !important; } ' .
        '.mybizna_pods_migration_link a{ background: transparent  !important; }';

    wp_register_style('mybizna-pods-migration-style', false);
    wp_enqueue_style('mybizna-pods-migration-style');
    wp_add_inline_style('mybizna-pods-migration-style', $custom_css);

}

function mybizna_pods_config_pre_load_configs($pod_class)
{
    $pod_class->register_path(dirname(__FILE__));
}

function mybizna_pods_migration_activate()
{

    if (!class_exists('PodsAPI')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Please install and Activate Wordpress Pods.', 'https://wordpress.org/plugins/pods/'), 'Plugin dependency check', array('back_link' => true));
    } else {

        $config = new Config();

        $config->setup();

    }

}

register_activation_hook(__FILE__, 'mybizna_pods_migration_activate');

add_action('pods_config_pre_load_configs', 'mybizna_pods_config_pre_load_configs');
add_action('plugins_loaded', 'mybizna_pods_migration');
add_action('admin_bar_menu', 'mybizna_pods_migration_link', 800);
