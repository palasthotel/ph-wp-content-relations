<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 03.04.18
 * Time: 08:52
 */

namespace ContentRelations;

defined( 'ABSPATH' ) || exit;


use Content_Relations_Store;

class RestApi {
	public function __construct(Plugin $plugin) {
		add_action('rest_api_init', array( $this, 'rest_api_init') );
		add_filter('content_relations_modify_rest_json', [$this, 'content_relations_modify_rest_json'], 10 , 3);
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

	/**
	 * Only relations whose both ends the current user may read.
	 *
	 * This used to hand every relation, with the other post's title, to anyone with
	 * edit_posts - so a contributor could read the titles of other authors' drafts and
	 * private posts here - and filter by "publish" for everybody else. read_post covers
	 * both: published posts for visitors, and for a logged-in user exactly the drafts and
	 * private posts WordPress would show them anyway.
	 */
	public function content_relations_modify_rest_json($relations, $store, $post_id){
		return array_values( array_filter($relations, function($relation){
			return current_user_can( 'read_post', (int) $relation->source_id )
				&& current_user_can( 'read_post', (int) $relation->target_id );
		}) );
	}
}