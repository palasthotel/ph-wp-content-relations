<?php
/**
 * registeres handler for content relations
 *
 */
function ph_content_relations_post_content_relations_handler_register()
{
	ph_migrate_register_field_handler( 'ph_post_destination','content_relations:','ph_content_relations_post_content_relations_handler' );
}
add_action( 'ph_migrate_register_field_handlers','ph_content_relations_post_content_relations_handler_register' );

/**
 * function that handles the migrate process
 * @param  $post Array with post details
 * @param  $fields Array with migration data (content_relation:types, content_relations:targets)
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
	require_once plugin_dir_path( __FILE__ ) . 'classes/class-content-relations-store.php';
	$store = new Content_Relations_Store( $post['ID'] );
	foreach ( $targets as $key => $target ) {
		if($target == null) continue;
		if($types[ $key ] == null) continue;
		$store->add_relation( $post['ID'], $target, $types[ $key ] );
	}

}