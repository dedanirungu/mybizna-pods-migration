=== Plugin Name ===
Contributors: @dedanirungu
Donate link: https://mybizna.com/
Tags: pods, pods-migration, pods.json
Requires at least: 5.0
Tested up to: 5.9
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This pods migrartion plugin to perform pods migration that are saved as pods.json on themes or plugins.

== Description ==

This pods migration plugin to perform pods migration that are saved as pods.json on themes or plugins.

I have added new functions to Config.php that has been in development.

https://github.com/pods-framework/pods/pull/4856

The plugin functionality work's as follows;

* Autodiscover pods.json in themes and those add via pods_config_pre_load_configs.
* prepare an array by converting JSON to an array.
* MD5 the array and compare previously saved MD5 string for previous migrations so as to determine if migration is required.If MD5 is the same the pod is ignored.
* Call add_pod at PodsAPI to save the pod
* Save new MD5 from the array for reference during the next migration.

I have only tested with pods with fields and groups only and it is working both for adding, and updating.

NOTE: The plugin can not work if wordpress pods is not installed and activated https://wordpress.org/plugins/pods/

== Changelog ==

= 1.0 =
* Initial development of pods migration plugin.

