<?php
/**
 *
 * @wordpress-plugin
 * Plugin Name:       PALASTHOTEL Content Relations
 * Description:       To relate contents to other contents
 * Version:           1.0
 * Author:            PALASTHOTEL by Edward Bock
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-content-relations-activator.php';

/** This action is documented in includes/class-content-relations-activator.php */
register_activation_hook( __FILE__, array( 'Content_Relations_Activator', 'activate' ) );

/**
 * The code that runs during plugin deactivation.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-content-relations-deactivator.php';

/** This action is documented in includes/class-content-relations-deactivator.php */
register_deactivation_hook( __FILE__, array( 'Content_Relations_Deactivator', 'deactivate' ) );

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-content-relations.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 */
function run_ph_content_relations()
{
	$plugin = new Content_Relations();
	$plugin->run();

}
run_ph_content_relations();

/**
 * -------------> PH Migrate Part
 */
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
/**
 * -------------> PH Migrate Part END
 */

