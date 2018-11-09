<?php
/**
 *
 * @wordpress-plugin
 * Plugin Name:       PALASTHOTEL Content Relations
 * Description:       To relate contents to other contents
 * Version:           1.0.7
 * Author:            PALASTHOTEL by Edward Bock
 */

namespace ContentRelations;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Plugin
 *
 * @package ContentRelations
 */
class Plugin {

	/**
	 * singleton pattern
	 * @var Plugin
	 */
	private static $instance = null;

	public static function instance() {
		if ( self::$instance == null ) {
			self::$instance = new Plugin();
		}

		return self::$instance;
	}

	/**
	 * Plugin constructor.
	 */
	private function __construct() {

		$this->url = plugin_dir_url( __FILE__ );

		/**
		 * The class that handles required relations for post types
		 */
		require_once dirname( __FILE__ ) . "/classes/class-content-relations-required.php";

		/**
		 * The class that handles all data
		 */
		require_once dirname( __FILE__ ) . "/classes/class-content-relations-store.php";

		/**
		 * Post meta box
		 */
		require_once dirname( __FILE__ ) . "/classes/meta-box.php";
		$this->meta_box = new MetaBox( $this );

		/**
		 * Post meta box
		 */
		require_once dirname( __FILE__ ) . "/classes/post.php";
		$this->post = new Post( $this );

		/**
		 * WP_Query args extension
		 */
		require_once dirname( __FILE__ ) . "/classes/wp-post-query-extension.php";
		$this->wp_query_extension = new WPPostQueryExtension( $this );

		/**
		 * Post meta box
		 */
		require_once dirname( __FILE__ ) . "/classes/rest-api.php";
		$this->rest_api = new RestApi( $this );

		/**
		 * Grid Add Ons
		 */
		require_once dirname( __FILE__ ) . "/classes/grid.php";
		$this->grid = new Grid( $this );


		register_activation_hook( __FILE__, array( $this, 'activate' ) );
	}

	function activate() {
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
		dbDelta( 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'content_relations` (
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
				) DEFAULT CHARSET=utf8;' );

		/**
		 * create content_relations_types table
		 */
		dbDelta( 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . "content_relations_types` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `type` varchar(30) NOT NULL DEFAULT '',
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `type` (`type`)
				) DEFAULT CHARSET=utf8;" );
	}

}

Plugin::instance();

require_once dirname( __FILE__ ) . "/public-functions.php";
require_once dirname( __FILE__ ) . "/migrate.php";
