<?php
namespace LiveuEventsLog;

class Activator
{
	public static function activate() {
		self::create_tables();

		flush_rewrite_rules();
	}

	public static function create_tables() {

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		if ( $wpdb->get_var( "show tables like '".EVENTS_DATABASE_TABLE."'" ) !== EVENTS_DATABASE_TABLE )
		{
			$sql = 'CREATE TABLE ' . EVENTS_DATABASE_TABLE . ' (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `level` varchar(20) NOT NULL,
            `user_id` int(11) unsigned NOT NULL,
            `post_id` int(11) unsigned NOT NULL,
            `post_type` varchar(255) NOT NULL,
            `action` int(11) NOT NULL,
            `logger` varchar(255) NOT NULL,
            `date` datetime,
            `message` varchar(255) NULL,
            `new` boolean NOT NULL DEFAULT true,
            PRIMARY KEY (id),
            UNIQUE KEY `id` (id)) ' . $charset_collate . ';';
			dbDelta($sql);
		}

		if ( $wpdb->get_var( "show tables like '".EVENTS_CONTEXT_TABLE."'" ) !== EVENTS_CONTEXT_TABLE )
		{
			$sql = 'CREATE TABLE ' . EVENTS_CONTEXT_TABLE . ' (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `event_id` int(11) unsigned NOT NULL,
            `key` varchar(255) NULL,
            `value` longtext NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (event_id) REFERENCES '.EVENTS_DATABASE_TABLE.'(id),
            UNIQUE KEY `id` (id)) ' . $charset_collate . ';';
			dbDelta($sql);
		}
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}
}
