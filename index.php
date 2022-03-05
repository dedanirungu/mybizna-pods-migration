<?php

/**
 * Mybizna Pods Migration
 *
 * @package           MybiznaPodsMigration
 * @author            Dedan Irungu
 * @copyright         2022 Mybizna.com
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Mybizna Pods Migration
 * Plugin URI:        https://wordpress.org/plugins/mybizna-pods-migration/
 * Description:       This pods migration plugin to perform pods migration that are saved as pods.json on themes or plugins.
 * Version:           1.0.2
 * Requires at least: 5.4
 * Requires PHP:      7.2
 * Author:            Dedan Irungu
 * Author URI:        https://mybizna.com
 * Text Domain:       mybizna-pods-migration
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

require_once 'MybiznaPodsMigration.php';

require_once ABSPATH . 'wp-admin/includes/file.php';

function mybizna_pods_migration()
{
    if (isset($_GET['pods-migration'])) {
        
        WP_Filesystem();

        $mybizna_pods_migration = new MybiznaPodsMigration();

        $mybizna_pods_migration->setup();
    }

}

function mybizna_pods_migration_link($wp_admin_bar)
{

    $url = add_query_arg('_wpnonce', wp_create_nonce('pods-migration'), admin_url() . '?pods-migration=1');

    $args = array(
        'id' => 'mybizna-pods-migration',
        'title' => 'Pods Migration',
        'href' => $url,
        'meta' => array(
            'title' => 'Mybizna Pods Migration',
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

        WP_Filesystem();

        $mybizna_pods_migration = new MybiznaPodsMigration();

        $mybizna_pods_migration->setup();

    }

}

register_activation_hook(__FILE__, 'mybizna_pods_migration_activate');

add_action('pods_config_pre_load_configs', 'mybizna_pods_config_pre_load_configs');
add_action('plugins_loaded', 'mybizna_pods_migration');
add_action('admin_bar_menu', 'mybizna_pods_migration_link', 800);
