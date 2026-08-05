<?php
/**
 *
 * @wordpress-plugin
 * Plugin Name:       Content Relations
 * Plugin URI:        https://wordpress.org/plugins/content-relations/
 * Description:       Relate posts to other posts, edit them in the block editor sidebar or a meta box, and show them with a Related content block or on the REST API.
 * Version:           1.1.0
 * Requires at least: 6.6
 * Tested up to:      7.0.2
 * Requires PHP:      7.4
 * Author:            Palasthotel <rezeption@palasthotel.de> (Edward Bock, Jana Marie Eggebrecht)
 * Author URI:        https://palasthotel.de
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       ph-content-relations
 * Domain Path:       /languages
 */

namespace ContentRelations;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Plugin
 * @package ContentRelations
 */
class Plugin{

    public string $url;
    public string $path;
    public MetaBox $meta_box;
    public Post $post;
    public WPPostQueryExtension $wp_query_extension;
    public RestApi $rest_api;
    public RestEditor $rest_editor;
    public BlockEditorAssets $block_editor_assets;
    public TypesScreen $types_screen;
    public QueryLoop $query_loop;
    public Grid $grid;

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
	const FILTER_META_BOX_TITLE = "content_relations_meta_box_title";

	const FILTER_META_BOX_POST_TYPES = "content_relations_meta_box_post_types";
	const FILTER_META_BOX_FIND_QUERY_ARGS = "content_relations_meta_box_find_query_args";

	/**
	 * actions
	 */
	const ACTION_META_BOX_LIST_BEFORE = "content_relations_meta_box_list_before";
	const ACTION_META_BOX_LIST_AFTER = "content_relations_meta_box_list_after";

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
		$this->path = plugin_dir_path(__FILE__);

		// Load the bundled translations. On init, not earlier: WordPress 6.7 warns about
		// translations loaded before the init hook.
		add_action( 'init', function () {
			load_plugin_textdomain( 'ph-content-relations', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		} );

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

		/**
		 * REST surface for the block editor sidebar and the reworked meta box
		 */
		require_once dirname(__FILE__)."/classes/rest-editor.php";
		$this->rest_editor = new RestEditor($this);

		/**
		 * Translated strings for the block editor bundle: RelationsEditor.jsx (shared by
		 * the sidebar panel and the meta box) and the Query Loop variation
		 */
		require_once dirname(__FILE__)."/classes/relations-i18n.php";

		/**
		 * Block editor sidebar bundle
		 */
		require_once dirname(__FILE__)."/classes/block-editor-assets.php";
		$this->block_editor_assets = new BlockEditorAssets($this);

		/**
		 * Tools -> Content Relations screen (relation types as a WP_List_Table)
		 */
		require_once dirname(__FILE__)."/classes/types-list-table.php";
		require_once dirname(__FILE__)."/classes/types-screen.php";
		$this->types_screen = new TypesScreen($this);

		/**
		 * A "related posts" variation of the core Query Loop block
		 */
		require_once dirname(__FILE__)."/classes/query-loop.php";
		$this->query_loop = new QueryLoop($this);

		/**
		 * Grid Add Ons
		 */
		require_once dirname( __FILE__ ) . "/classes/grid.php";
		$this->grid = new Grid( $this );


		register_activation_hook( __FILE__, array( $this, 'activate' ) );
	}

	function activate(){
		Db\install();
	}

}
Plugin::instance();

require_once dirname(__FILE__)."/public-functions.php";
require_once dirname(__FILE__)."/migrate.php";
