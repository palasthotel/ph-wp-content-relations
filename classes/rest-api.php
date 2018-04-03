<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 03.04.18
 * Time: 08:52
 */

namespace ContentRelations;


use Content_Relations_Store;

class RestApi {
	public function __construct(Plugin $plugin) {
		add_action('rest_api_init', array( $this, 'rest_api_init') );
	}


	/**
	 * on initialization of rest api
	 */
	public function rest_api_init(){
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types as $post_type ) {
			register_rest_field( $post_type->name,
				apply_filters('content_relations_modify_rest_attribute_name', 'content_relations', $post_type),
				array(
					'get_callback' => array( $this, 'add_relations_to_rest_api' ),
					'schema'       => null,
				)
			);
		}
	}

	/**
	 * add relations to json
	 *
	 * @param $object
	 *
	 * @return array
	 */
	public function add_relations_to_rest_api($object){

		/**
		 * @var $store \Content_Relations_Store
		 */
		$post_id = $object['id'];
		$store    = new Content_Relations_Store($post_id);
		$relations = $store->get_relations();
		return apply_filters('content_relations_modify_rest_json', $relations, $store, $post_id);
	}
}