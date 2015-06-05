<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 */
class Content_Relations_Activator {

	/**
	 * Create all tables for content relations.
	 *
	 */
	public static function activate() {
		/**
		 * wpdb object for prefix
		 */
		global $wpdb;
		/**
		 * require upgrade.php for dbDelta function
		 */
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		/**
		 * Create content_relations_relations table
		 */
		dbDelta('CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.'content_relations` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `source_id` int(11) unsigned NOT NULL,
				  `target_id` int(11) unsigned NOT NULL,
				  `type_id` int(11) NOT NULL,
				  `weight` int(11) NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `item_key` (`source_id`,`target_id`, `type_id`),
				  KEY `source_id` (`source_id`),
				  KEY `target_id` (`target_id`),
				  KEY `type_id` (`type_id`)
				) DEFAULT CHARSET=utf8;');

		/**
		 * create content_relations_types table
		 */
		dbDelta( 'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix."content_relations_types` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `type` varchar(30) NOT NULL DEFAULT '',
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `type` (`type`)
				) DEFAULT CHARSET=utf8;");
	}

}