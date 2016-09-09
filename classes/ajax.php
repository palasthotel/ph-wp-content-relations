<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 07.09.16
 * Time: 14:52
 */

namespace ContentRelations;

use WP_Query;

class Ajax {
	public function __construct(\ContentRelations $plugin) {
		add_action( 'wp_ajax_ph_content_relations_title', array($this, 'get_contents_by_title') );
	}
	
	/**
	 * Endpoint for getting gallery ids
	 */
	public function get_contents_by_title() {
		
		if( !isset( $_GET['q'] ) ){
			print json_encode( array( 'result' => array() ) );
			die();
		}
		
		$query_string = sanitize_text_field( $_GET['q'] );
		$result = array();
		
		/**
		 * first have a look if its a post id
		 */
		if(is_numeric($query_string)){
			$post = get_post($query_string);
			if( is_a($post, "WP_Post") ){
				$result[$post->post_type] = array( $this->get_contents_item($post) );
				wp_reset_postdata();
			}
		}
		
		
		$post_types = get_post_types( array('public' => true) );
		foreach ($post_types as $type) {
			if($type == "landing_page" || $type == "sidebar") continue;
			$args = array(
				'posts_per_page' => 20,
				's' => $query_string,
				'post_status' => 'any',
				'post_type' => $type,
			);
			$query = new WP_Query( $args );
			/**
			 * Cleanup WP_Query results to minimize result size
			 * Add gallery images for preview in backend
			 */
			
			foreach ( $query->posts as $post ) {
				$item = $this->get_contents_item($post);
				$format = $item["format"];
				if(!$format){
					$_type = $type;
				} else {
					$_type = $type."_".$format;
				}
				if(!isset($results[$_type])) $results[$_type] = array();
				$result[$_type][] = $item;
			}
			wp_reset_postdata();
		}
		
		// print json for JavaScript result
		print json_encode( array( 'result' => $result ) );
		die();
	}
	
	private function get_contents_item($post){
		$item = array();
		$item['post_title'] = $post->post_title;
		$item['ID'] = $post->ID;
		$item['post_type'] = $post->post_type;
		$item['format'] = get_post_format($post->ID);
		if($post->post_type == "attachment"){
			$item['src'] = wp_get_attachment_image_src( $post->ID, 'thumbnail', false );
		}
		$item['pub_date'] = get_the_date('l, F j, Y', $post->ID);
		return $item;
	}
}