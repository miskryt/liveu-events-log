<?php

namespace LiveuEventsLog;

/**
 * Plugin Name: LiveuEventsLog
 * Plugin URI: https://example.com/
 * Description: Log events.
 * Version: 1.0.0
 * Author: Solvd Inc
 * Author URI: https://solvd.com
 * Text Domain: LiveuEventsLog
 * Domain Path: /i18n/languages/
 */


if ( ! defined('ABSPATH' ) ) {
	die;
}

require __DIR__ . '/vendor/autoload.php';
global $wpdb;

if ( ! defined( 'LEVLOG_VERSION' ) ){
	define('LEVLOG_VERSION', '1.0.0');
}

if ( ! defined( 'LEVLOG_PATH' ) ){
	define('LEVLOG_PATH', plugin_dir_path(__FILE__));
}

if ( ! defined( 'LEVLOG_PLUGIN_URL' ) ) {
	define( 'LEVLOG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'EVENTS_DATABASE_TABLE' ) ){
	define('EVENTS_DATABASE_TABLE', $wpdb->prefix . 'events_log_events');
}

if ( ! defined( 'EVENTS_CONTEXT_TABLE' ) ){
	define('EVENTS_CONTEXT_TABLE', $wpdb->prefix . 'events_log_context');
}


register_activation_hook( __FILE__, 'LiveuEventsLog\\activate_liveu_events_log' );
register_deactivation_hook( __FILE__, 'LiveuEventsLog\\deactivate_liveu_events_log' );

function activate_liveu_events_log() {
	Activator::activate();
}

function deactivate_liveu_events_log() {

	Activator::deactivate();
}

if(class_exists('LiveuEventsLog\\Plugin'))
{
	(new Plugin())->run();
}

