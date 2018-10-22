<?php
/**
 *
 * @wordpress-plugin
 * Plugin Name:       Content Relations
 * Description:       To relate contents to other contents.
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
class Plugin{

	/**
	 * WP_Query args extension
	 */
	const WP_QUERY_ARG_RELATION = "content_relations";
	const WP_QUERY_ARG_RELATED_TO = "to";
	const WP_QUERY_ARG_RELATED_FROM = "from";
	const WP_QUERY_ARG_RELATED_WITH = "with";
	const WP_QUERY_ARG_RELATED_TYPE = "type";

	/**
	 * Filters
	 */
	const FILTER_ADD_META_BOX = "content_relations_add_meta_box";

	/**
	 * singleton pattern
	 * @var Plugin
	 */
	private static $instance =  null;
	public static function instance(){
		if(self::$instance == null) self::$instance = new Plugin();
		return self::$instance;
	}

	/**
	 * Plugin constructor.
	 */
	private function __construct() {

		$this->url = plugin_dir_url( __FILE__ );

		/**
		 * db handle
		 */
		require_once dirname(__FILE__)."/classes/db.php";

		/**
		 * The class that handles required relations for post types
		 */
		require_once dirname(__FILE__)."/classes/class-content-relations-required.php";

		/**
		 * The class that handles all data
		 */
		require_once dirname(__FILE__)."/classes/class-content-relations-store.php";

		/**
		 * Post meta box
		 */
		require_once dirname(__FILE__)."/classes/meta-box.php";
		$this->meta_box = new MetaBox($this);

		/**
		 * Post meta box
		 */
		require_once dirname(__FILE__)."/classes/post.php";
		$this->post = new Post($this);

		/**
		 * WP_Query args extension
		 */
		require_once dirname(__FILE__)."/classes/wp-post-query-extension.php";
		$this->wp_query_extension = new WPPostQueryExtension($this);

		/**
		 * Post meta box
		 */
		require_once dirname(__FILE__)."/classes/rest-api.php";
		$this->rest_api = new RestApi($this);


		register_activation_hook( __FILE__, array( $this, 'activate' ) );
	}

	function activate(){
		Db\install();
	}

}
Plugin::instance();

require_once dirname(__FILE__)."/public-functions.php";
require_once dirname(__FILE__)."/migrate.php";