<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 */
class Content_Relations_Public {

	/**
	 * The ID of this plugin.
	 *
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * add content relations object to post object on the_post action
	 *
	 */
	public function add_relations_to_post($post) {
		/**
		 * add to post object for easy access
		 */
		$post->content_relations = new Content_Relations_Store( $post->ID );
	}

	/**
	 * on initialization of rest api
	 */
	public function rest_api_init(){
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types as $post_type ) {
			register_rest_field( $post_type->name,
				'content_relations',
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

		$post = get_post( $object['id'] );
		setup_postdata($post);

		$current_post = $post;

		/**
		 * @var $store \Content_Relations_Store
		 */
		$store    = $post->content_relations;
		$relations = $store->get_relations();
		return apply_filters('content_relations_modify_rest_json', $relations, $store);
	}

}
