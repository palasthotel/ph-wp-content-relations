<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 03.04.18
 * Time: 08:48
 */

namespace ContentRelations;


use Content_Relations_Store;

class Post {
	public function __construct(Plugin $plugin) {
		add_action( 'the_post', array($this, 'add_relations_to_post') );
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
}