<?php

/**
 * Class to handle registration of Pods configs and loading/saving of config files.
 *
 * @package Pods
 */
class MybiznaPodsMigration
{

    /**
     * List of registered config types.
     *
     * @since TBD
     *
     * @var array
     */
    protected $registered_config_types = [
        'json' => 'json',
        'yml' => 'yml',
    ];

    /**
     * List of registered config item types.
     *
     * @since TBD
     *
     * @var array
     */
    protected $registered_config_item_types = [
        'pods' => 'pods',
        'fields' => 'fields',
        'templates' => 'templates',
        'pages' => 'pages',
        'helpers' => 'helpers',
    ];

    /**
     * List of registered paths.
     *
     * @since TBD
     *
     * @var array
     */
    protected $registered_paths = [];

    /**
     * List of registered Pods configs.
     *
     * @since TBD
     *
     * @var array
     */
    protected $pods = [];

    /**
     * List of registered Pods Template configs.
     *
     * @since TBD
     *
     * @var array
     */
    protected $templates = [];

    /**
     * List of registered Pods Page configs.
     *
     * @since TBD
     *
     * @var array
     */
    protected $pages = [];

    /**
     * List of registered Pods Helper configs.
     *
     * @since TBD
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Associative array list of other registered configs.
     *
     * @since TBD
     *
     * @var array
     */
    protected $custom_configs = [];

    /**
     * List of config names for each file path.
     *
     * @since TBD
     *
     * @var array
     */
    protected $file_path_configs = [];

    /**
     * wp_filesystem global variable.
     *
     * @since TBD
     *
     * @var array
     */
    protected $wp_filesystem = '';

    /**
     * Config constructor.
     *
     * @since TBD
     */
    public function __construct()
    {
        // Nothing to see here.
    }

    /**
     * Setup initial registered paths and load configs.
     *
     * @since TBD
     */
    public function setup()
    {

        // Register theme.
        $this->register_path(get_template_directory());

        if (get_template_directory() !== get_stylesheet_directory()) {
            // Register child theme.
            $this->register_path(get_stylesheet_directory());
        }

        if ($this->check_migration_table()) {
            $this->load_configs();
            $this->save_pods();
        }

    }

    /**
     * Register a config type.
     *
     * @since TBD
     *
     * @param string $config_type Config type.
     */
    public function register_config_type($config_type)
    {
        $config_type = sanitize_title($config_type);
        $config_type = str_replace(['/', DIRECTORY_SEPARATOR], '-', $config_type);

        $this->registered_config_types[$config_type] = $config_type;
    }

    /**
     * Unregister a config type.
     *
     * @since TBD
     *
     * @param string $config_type Config type.
     */
    public function unregister_config_type($config_type)
    {
        $config_type = sanitize_title($config_type);
        $config_type = str_replace(['/', DIRECTORY_SEPARATOR], '-', $config_type);

        if (isset($this->registered_config_types[$config_type])) {
            unset($this->registered_config_types[$config_type]);
        }
    }

    /**
     * Register a config item type.
     *
     * @since TBD
     *
     * @param string $item_type Config item type.
     */
    public function register_config_item_type($item_type)
    {
        $item_type = sanitize_title($item_type);
        $item_type = str_replace(['/', DIRECTORY_SEPARATOR], '-', $item_type);

        $this->registered_config_item_types[$item_type] = $item_type;
    }

    /**
     * Unregister a config item type.
     *
     * @since TBD
     *
     * @param string $item_type Config item type.
     */
    public function unregister_config_item_type($item_type)
    {
        $item_type = sanitize_title($item_type);
        $item_type = str_replace(['/', DIRECTORY_SEPARATOR], '-', $item_type);

        if (isset($this->registered_config_item_types[$item_type])) {
            unset($this->registered_config_item_types[$item_type]);
        }
    }

    /**
     * Register a config file path.
     *
     * @since TBD
     *
     * @param string $path Config file path.
     */
    public function register_path($path)
    {
        $path = trailingslashit($path);

        if (0 !== strpos($path, ABSPATH)) {
            $path = ABSPATH . $path;
        }

        $this->registered_paths[$path] = $path;
    }

    /**
     * Unregister a config file path.
     *
     * @since TBD
     *
     * @param string $path Config file path.
     */
    public function unregister_path($path)
    {
        $path = trailingslashit($path);

        if (0 !== strpos($path, ABSPATH)) {
            $path = ABSPATH . $path;
        }

        if (isset($this->registered_paths[$path])) {
            unset($this->registered_paths[$path]);
        }
    }

    /**
     * Get file configs based on registered config types and config item types.
     *
     * @since TBD
     *
     * @return array File configs.
     */
    protected function get_file_configs()
    {
        $file_configs = [];

        // Flesh out the config types.
        foreach ($this->registered_config_types as $config_type) {
            foreach ($this->registered_config_item_types as $config_item_type) {
                $theme_support = false;

                // Themes get pods.json / pods.yml support at root.
                if ('pods' === $config_item_type) {
                    $theme_support = true;
                }

                $path = sprintf('%s.%s', $config_item_type, $config_type);

                $file_configs[] = [
                    'type' => $config_type,
                    'file' => $path,
                    'item_type' => $config_item_type,
                    'theme_support' => $theme_support,
                ];

                // Prepend pods/ to path for theme paths.
                $path = sprintf('pods%s%s', DIRECTORY_SEPARATOR, $path);

                $file_configs[] = [
                    'type' => $config_type,
                    'file' => $path,
                    'item_type' => $config_item_type,
                    'theme_support' => true,
                ];
            } //end foreach
        } //end foreach

        return $file_configs;
    }

    /**
     * Load configs from registered file paths.
     *
     * @since TBD
     */
    protected function load_configs()
    {

        /**
         * Allow plugins/themes to hook into config loading.
         *
         * @since 2.7.2
         *
         * @param Config $pods_config Pods config object.
         *
         */

        global $wp_filesystem;

        do_action('pods_config_pre_load_configs', $this);

        $file_configs = $this->get_file_configs();

        $theme_dirs = [
            trailingslashit(get_template_directory()),
            trailingslashit(get_stylesheet_directory()),
        ];


        foreach ($this->registered_paths as $config_path) {
            foreach ($file_configs as $file_config) {
                if (empty($file_config['theme_support']) && in_array($config_path, $theme_dirs, true)) {
                    continue;
                }

                $file_path = $config_path . $file_config['file'];

                if (!$wp_filesystem->exists($file_path) || !$wp_filesystem->is_readable($file_path)) {
                    continue;
                }

                $raw_config = $wp_filesystem->get_contents($file_path);

                if (empty($raw_config)) {
                    continue;
                }

                $this->load_config($file_config['type'], $raw_config, $file_path, $file_config);
            } //end foreach
        } //end foreach

    }

    /**
     * Load config from registered file path.
     *
     * @since TBD
     *
     * @param string $config_type Config type.
     * @param string $raw_config  Raw config content.
     * @param string $file_path   Config file path.
     * @param array  $file_config File config.
     */
    protected function load_config($config_type, $raw_config, $file_path, array $file_config)
    {
        $config = null;

        if ('yml' === $config_type) {
            require_once PODS_DIR . 'vendor/mustangostang/spyc/Spyc.php';

            $config = \Spyc::YAMLLoadString($raw_config);
        } elseif ('json' === $config_type) {
            $config = json_decode($raw_config, true);
        } else {
            /**
             * Parse Pods config from a custom config type.
             *
             * @since 2.7.2
             *
             * @param string $config_type Config type.
             * @param string $raw_config  Raw config content.
             *
             * @param array  $config      Config data.
             */
            $config = apply_filters('pods_config_parse', [], $config_type, $raw_config);
        }

        if ($config && is_array($config)) {
            $this->register_config($config, $file_path, $file_config);
        }
    }

    /**
     * Register config for different item types.
     *
     * @since TBD
     *
     * @param array  $config      Config data.
     * @param string $file_path   Config file path.
     * @param array  $file_config File config.
     */
    protected function register_config(array $config, $file_path, array $file_config = [])
    {
        if (!isset($this->file_path_configs[$file_path])) {
            $this->file_path_configs[$file_path] = [];
        }

        foreach ($config as $item_type => $items) {
            if (empty($items) || !is_array($items)) {
                continue;
            }

            $supported_item_types = [
                $item_type,
                // We support all item types for pods configs.
                'pods',
            ];

            // Skip if the item type is not supported for this config file.
            if (!empty($file_config['item_type']) && !in_array($file_config['item_type'], $supported_item_types, true)) {
                continue;
            }

            if (!isset($this->file_path_configs[$file_path][$item_type])) {
                $this->file_path_configs[$file_path][$item_type] = [];
            }

            if ('pods' === $item_type) {
                $this->register_config_pods($items, $file_path);
            } elseif ('fields' === $item_type) {
                $this->register_config_fields($items, $file_path);
            } elseif ('templates' === $item_type) {
                $this->register_config_templates($items, $file_path);
            } elseif ('pages' === $item_type) {
                $this->register_config_pages($items, $file_path);
            } elseif ('helpers' === $item_type) {
                $this->register_config_helpers($items, $file_path);
            } else {
                $this->register_config_custom_item_type($item_type, $items, $file_path);
            }
        } //end foreach

    }

    /**
     * Register pod configs.
     *
     * @since TBD
     *
     * @param array  $items     Config items.
     * @param string $file_path Config file path.
     */
    protected function register_config_pods(array $items, $file_path = '')
    {
        foreach ($items as $item) {
            // Check if the item type and name exists.
            if (empty($item['type']) || empty($item['name'])) {
                continue;
            }

            if (!isset($this->pods[$item['type']])) {
                $this->pods[$item['type']] = [];
            }

            if (isset($item['id'])) {
                unset($item['id']);
            }

            if (empty($item['fields'])) {
                $item['fields'] = [];
            }

            $this->pods[$item['type']][$item['name']] = $item;

            $this->file_path_configs[$file_path]['pods'] = $item['type'] . ':' . $item['name'];
        } //end foreach

    }

    /**
     * Register pod field configs.
     *
     * @since TBD
     *
     * @param array  $items     Config items.
     * @param string $file_path Config file path.
     */
    protected function register_config_fields(array $items, $file_path = '')
    {
        foreach ($items as $item) {
            // Check if the pod name, pod type, item type, and item name exists.
            if (empty($item['type']) || empty($item['name']) || empty($item['pod']['name']) || empty($item['pod']['type'])) {
                continue;
            }

            if (!isset($this->pods[$item['pod']['type']])) {
                $this->pods[$item['pod']['type']] = [];
            }

            if (isset($item['pod']['id'])) {
                unset($item['pod']['id']);
            }

            // Check if pod has been registered yet.
            if (!isset($this->pods[$item['pod']['type'][$item['pod']['name']]])) {
                $this->pods[$item['pod']['type']][$item['pod']['name']] = $item['pod'];
            }

            // Check if pod has fields that have been registered yet.
            if (!isset($this->pods[$item['pod']['type'][$item['pod']['name']]]['fields'])) {
                $this->pods[$item['pod']['type']][$item['pod']['name']]['fields'] = [];
            }

            if (isset($item['id'])) {
                unset($item['id']);
            }

            $this->pods[$item['pod']['type']][$item['pod']['name']]['fields'][$item['name']] = $item;

            $this->file_path_configs[$file_path]['pods'] = $item['pod']['type'] . ':' . $item['pod']['name'] . ':' . $item['name'];
        } //end foreach

    }

    /**
     * Register template configs.
     *
     * @since TBD
     *
     * @param array  $items     Config items.
     * @param string $file_path Config file path.
     */
    protected function register_config_templates(array $items, $file_path = '')
    {
        foreach ($items as $item) {
            // Check if the item name exists.
            if (empty($item['name'])) {
                continue;
            }

            if (isset($item['id'])) {
                unset($item['id']);
            }

            $this->templates[$item['name']] = $item;

            $this->file_path_configs[$file_path]['templates'] = $item['name'];
        } //end foreach

    }

    /**
     * Register page configs.
     *
     * @since TBD
     *
     * @param array  $items     Config items.
     * @param string $file_path Config file path.
     */
    protected function register_config_pages(array $items, $file_path = '')
    {
        foreach ($items as $item) {
            // Check if the item name exists.
            if (empty($item['name'])) {
                continue;
            }

            if (isset($item['id'])) {
                unset($item['id']);
            }

            $this->pages[$item['name']] = $item;

            $this->file_path_configs[$file_path]['pages'] = $item['name'];
        } //end foreach

    }

    /**
     * Register helper configs.
     *
     * @since TBD
     *
     * @param array  $items     Config items.
     * @param string $file_path Config file path.
     */
    protected function register_config_helpers(array $items, $file_path = '')
    {
        foreach ($items as $item) {
            // Check if the item name exists.
            if (empty($item['name'])) {
                continue;
            }

            if (isset($item['id'])) {
                unset($item['id']);
            }

            $this->helpers[$item['name']] = $item;

            $this->file_path_configs[$file_path]['helpers'] = $item['name'];
        } //end foreach

    }

    /**
     * Register config items for custom config item type.
     *
     * @since TBD
     *
     * @param string $item_type Config Item type.
     * @param array  $items     Config items.
     * @param string $file_path Config file path.
     */
    protected function register_config_custom_item_type($item_type, array $items, $file_path = '')
    {
        if (!isset($this->custom_configs[$item_type])) {
            $this->custom_configs[$item_type] = [];
        }

        foreach ($items as $item) {
            /**
             * Pre-process the item to be saved for a custom item type.
             *
             * @since 2.7.2
             *
             * @param string $item_type Item type.
             * @param string $file_path Config file path.
             *
             * @param array  $item      Item to pre-process.
             */
            $item = apply_filters('pods_config_register_custom_item', $item, $item_type, $file_path);

            // Check if the item name exists.
            if (empty($item['name'])) {
                continue;
            }

            if (isset($item['id'])) {
                unset($item['id']);
            }

            $this->custom_configs[$item_type][$item['name']] = $item;

            $this->file_path_configs[$file_path][$item_type] = $item['name'];
        } //end foreach

    }

    /**
     * Function for checking if pods_migration table exists and create's it if not.
     *
     * @return void
     */
    protected function check_migration_table()
    {
        /**
         * Get Global variable $wpdb
         */
        global $wpdb;
        /**
         * Get Global variable $wp_filesystem
         */
        global $wp_filesystem;

        // Prepare function variables
        $file = dirname(__FILE__) . '/pods.json';
        $table_name = $wpdb->prefix . "pods_migration";

        //Check if table pods_migration exist and if not add it
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

            $raw_config = $wp_filesystem->get_contents($file);

            $config = json_decode($raw_config, true);

            foreach ($config['pods'] as $pod_data) {

                $pod_data['create_menu_location'] = 'settings';
                $pod_data['create_rest_api'] = 0;

                if ($this->save_pod($pod_data)) {

                    $pods = pods('migration');

                    $data = array(
                        'md5str' => md5(json_encode($pod_data)),
                        'item_name' => 'migration',
                        'item_type' => 'post_type',
                    );

                    $pods->add($data);

                    return true;
                }

            }

            return false;

        }

        return true;
    }

    protected function save_pods()
    {

        global $wpdb;

        if ($this->pods) {

            foreach ($this->pods as $key => $pod_type) {

                foreach ($pod_type as $pod_data) {

                    $pod_name = $pod_data['name'];
                    $pod_type = $pod_data['type'];

                    $md5str = md5(json_encode($pod_data));

                    $qry = "item_name=\"" . $pod_name . '"';

                    $myrows = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}pods_migration WHERE " . $qry);

                    if (!is_object($myrows) || $myrows->md5str != $md5str) {

                        $this->save_pod($pod_data);

                        $pods = pods('migration', $myrows->id);

                        $data = array(
                            'md5str' => $md5str,
                            'item_name' => $pod_name,
                            'item_type' => $pod_type,
                        );

                        if ($pods->exists()) {
                            $pods->save($data);
                        } else {
                            $pods = pods('migration');
                            $pods->add($data);
                        }

                    }

                }
            }
        }

    }

    protected function save_pod($pod_data)
    {

        $pods_api = new PodsAPI();

        $pod = $pods_api->load_pod($pod_data['name']);
        
        if ($pod) {

            $pod_data['id'] = $pod->id;

            $pod_data = $this->pod_reshaping($pod_data, 'extend');

            $cp_pod_data = $pod_data;
            unset($cp_pod_data['fields']);
            unset($cp_pod_data['groups']);

            $pod_id = $pods_api->save_pod($cp_pod_data);
        } else {
            $pod_data = $this->pod_reshaping($pod_data);
            
            $pod_id = $pods_api->add_pod($pod_data);

        }

        if ($pod_id) {

            if (isset($pod_data['groups'])) {
                foreach ($pod_data['groups'] as $group) {

                    $group_id = $this->save_group($group, $pod_id);

                    if ($group_id) {

                        foreach ($group['fields'] as $field) {

                            $field_id = $this->save_field($field, $pod_id, $group_id);

                        }

                    }
                }
            }

            if ($pod_data['fields']) {

                foreach ($pod_data['fields'] as $field) {

                    $field_id = $this->save_field($field, $pod_id);

                }
            }

            return $pod_id;

        }

        return false;
    }

    protected function save_group($group, $pod_id)
    {

        $pods_api = new PodsAPI();

        $search_params = [
            'pod_id' => $pod_id,
            'name' => $group['name'],
        ];

        $group_obj = $pods_api->load_group($search_params);

        if ($group_obj) {
            $group['id'] = $group_obj->id;
        }

        $group['pod_id'] = $pod_id;

        return $pods_api->save_group($group);
    }

    protected function save_field($field, $pod_id, $group_id = '')
    {

        $pods_api = new PodsAPI();

        $search_params = [
            'pod_id' => $pod_id,
            'name' => $field['name'],
        ];

        if ($group_id != '') {
            $search_params['group_id'] = $group_id;
        }

        $field_obj = $pods_api->load_field($search_params);

        if ($field_obj) {
            $field['id'] = $field_obj->id;
        }

        $field['pod_id'] = $pod_id;

        return $pods_api->save_field($field);

    }

    /**
     * Reshape the pods array to match the new pod Api and to add missing parameters
     *  to the function
     *
     * @param array  $pod         Pod data in array
     * @param string $action_type Pod Action type
     *
     * @return array $pod
     */
    protected function pod_reshaping($pod_data, $action_name = 'create')
    {
        $pods_api = new PodsAPI();

        $action_type = $action_name;

        $type = (isset($pod_data['type'])) ? $pod_data['type'] : 'post_type';
        $name = (isset($pod_data['name'])) ? $pod_data['name'] : '';
        $post_type = (isset($pod_data['post_type'])) ? $pod_data['post_type'] : 'post';
        $label_singular = (isset($pod_data['label_singular'])) ? $pod_data['label_singular'] : '';
        $label = (isset($pod_data['label'])) ? $pod_data['label'] : '';
        $taxonomy = (isset($pod_data['taxonomy'])) ? $pod_data['taxonomy'] : 'category';
        $table = (isset($pod_data['table'])) ? $pod_data['table'] : '';
        $storage = (isset($pod_data['storage'])) ? $pod_data['storage'] : 'meta';
        $storage_taxonomy = (isset($pod_data['storage_taxonomy'])) ? $pod_data['storage_taxonomy'] : '';

        $pod_data['create_extend'] = $action_type;

        $pod_data['create_pod_type'] = (isset($pod_data['create_pod_type'])) ?: $type;
        $pod_data['create_name'] = (isset($pod_data['create_name'])) ?: $name;
        $pod_data['create_label_singular'] = (isset($pod_data['create_label_singular'])) ?: $label_singular;
        $pod_data['create_label_plural'] = (isset($pod_data['create_label_plural'])) ?: $label;
        $pod_data['create_storage'] = (isset($pod_data['create_storage'])) ?: $storage;

        $pod_data['extend_pod_type'] = (isset($pod_data['extend_pod_type'])) ?: $type;
        $pod_data['extend_post_type'] = (isset($pod_data['extend_post_type'])) ?: $post_type;
        $pod_data['extend_taxonomy'] = (isset($pod_data['extend_taxonomy'])) ?: $taxonomy;
        $pod_data['extend_table'] = (isset($pod_data['extend_table'])) ?: $table;
        $pod_data['extend_storage'] = (isset($pod_data['extend_storage'])) ?: $storage;
        $pod_data['extend_storage_taxonomy'] = (isset($pod_data['extend_storage_taxonomy'])) ?: $storage_taxonomy;

        return $pod_data;

    }

    protected function relative_path($file)
    {
        $newfile = str_replace(ABSPATH, '', $file);

        return $newfile;

    }

    /**
     * @todo Get list of configs that do not match DB.
     * @todo Handle syncing changed configs to DB.
     * @todo Handle syncing configs from DB to file.
     */

}
