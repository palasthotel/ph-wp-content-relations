<?php
/**
 *
 * @wordpress-plugin
 * Plugin Name:       Content Relations
 * Description:       To relate contents to other contents
 * Version:           1.1.0
 * Author:            PALASTHOTEL by Edward Bock
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ContentRelations{
	
	public $dir;
	public $url;
	
	public function __construct() {
		
		$this->dir = plugin_dir_path( __FILE__ );
		$this->url = plugin_dir_url( __FILE__ ) ;
		
		/**
		 * on activation or deactivation
		 */
		require_once $this->dir . '/classes/activator.php';
		$activator = new \ContentRelations\Activator();
		register_activation_hook( __FILE__, array( $activator, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $activator, 'deactivate' ) );
		
		/**
		 * require objects
		 */
		require_once $this->dir . '/classes/store.php';
		require_once $this->dir . '/classes/required.php';
		
		/**
		 * meta box
		 */
		require_once $this->dir . '/classes/meta.php';
		new \ContentRelations\Meta($this);
		
		/**
		 * tools page
		 */
		require_once $this->dir . '/classes/tools.php';
		new \ContentRelations\Tools($this);
		
		/**
		 * post object
		 */
		require_once $this->dir . '/classes/post.php';
		new \ContentRelations\Post($this);
		
		/**
		 * ajax endpoints
		 */
		require_once $this->dir . '/classes/ajax.php';
		new \ContentRelations\Ajax($this);
		
	}
}

/**
 * expose to public
 */
global $content_relations;
$content_relations = new ContentRelations();

/**
 * Class Content_Relations_Store for backward compatibility
 */
class Content_Relations_Store extends ContentRelations\Store{}

/**
 * Class Content_Relations_Required for backward compatibility
 */
class Content_Relations_Required extends ContentRelations\Required{}

/**
 * Adds a new relation
 * @param $post_id_source integer Post ID
 * @param $post_id_target integer Post ID
 * @param $relation_type string type
 * @return false|int|void
 */
function ph_content_relations_add_relation($post_id_source, $post_id_target, $relation_type){
	$store = new Content_Relations_Store($post_id_source);
	return $store->add_relation($post_id_target, $relation_type, $relation_type);
}

/**
 * All relations by post id
 * @param $post_id
 * @return null|array
 */
function ph_content_relations_get_relations_by_post_id($post_id){
	$store = new Content_Relations_Store($post_id);
	return $store->get_relations();
}

/**
 * All relations by post id and type. Optional you can get both directions and not only relations where post is source
 * @param $post_id
 * @param $relation_type
 * @param bool|true $source_only
 * @return array
 */
function ph_content_relations_get_relations_by_post_id_and_type($post_id, $relation_type, $source_only = true){
	$store = new Content_Relations_Store($post_id);
	return $store->get_relations_by_type($relation_type, $source_only);
}


/**
 * -------------> PH Migrate Part
 */
/**
 * registeres handler for content relations
 *
 */
function ph_content_relations_post_content_relations_handler_register()
{
	if(function_exists("ph_migrate_register_field_handler")){
		ph_migrate_register_field_handler( 'ph_post_destination','content_relations:','ph_content_relations_post_content_relations_handler' );
	}
}
add_action( 'ph_migrate_register_field_handlers','ph_content_relations_post_content_relations_handler_register' );

/**
 * function that handles the migrate process
 * @param  array $post with post details
 * @param  array $fields with migration data (content_relation:types, content_relations:targets)
 *
 */
function ph_content_relations_post_content_relations_handler($post, $fields)
{
	$types = $fields['content_relations:types'];
	$targets = $fields['content_relations:targets'];

	/**
	 * be sure that there are arrays
	 */
	if ( ! is_array( $types ) ){
		$types = array( $types );
	}
	if ( ! is_array( $targets ) ){
		$targets = array( $targets );
	}

	/**
	 * save relations
	 */
	$store = new Content_Relations_Store( $post['ID'] );
	foreach ( $targets as $key => $target ) {
		if($target == null) continue;
		if($types[ $key ] == null) continue;
		$store->add_relation( $post['ID'], $target, $types[ $key ] );
	}

}
/**
 * -------------> PH Migrate Part END
 */

