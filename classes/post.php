<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 07.09.16
 * Time: 14:54
 */

namespace ContentRelations;


use ContentRelations;

class Post {
	/**
	 * Post constructor.
	 *
	 * @param \ContentRelations $plugin
	 */
	public function __construct(ContentRelations $plugin) {
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
		$post->content_relations = new Store( $post->ID );
	}
}