<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 07.09.16
 * Time: 14:42
 */

namespace ContentRelations;


class Meta {
	public function __construct( \ContentRelations $plugin) {
		$this->plugin = $plugin;
		/**
		 * adds content relations metabox to post
		 */
		add_action( 'add_meta_boxes', array($this, 'add_post_meta_relations') );
		
		/**
		 * registers save_post action
		 */
		add_action( 'save_post', array($this, 'save_post_meta_relations' ));
		
		/**
		 * registers delete_post action that is triggert before post is deleted
		 */
		add_action( 'delete_post', array($this, 'delete_post_meta_relations' ));
	}
	
	/**
	 * Register meta fields for content relations to post
	 *
	 */
	public function add_post_meta_relations()
	{
		
		add_meta_box(
			'ph_meta_box_content_relations',
			__( 'Content relations', 'ph_content_relations' ),
			array( $this, 'render_post_meta_relations' )
		// 'post'
		);
		/**
		 * Add css and javascript for meta box
		 */
		wp_enqueue_style(
			"content-relations-style", $this->plugin->url. 'css/content-relations-admin.css',
			array(),
			1.0,
			'all'
		);
		
//		wp_enqueue_script(
//			"content-relations-script", $this->plugin->url . 'js/content-relations-admin.js',
//			array( 'jquery', 'jquery-ui-autocomplete' ),
//			1.0,
//			false
//		);
		
		wp_enqueue_script(
			"react-content-relations-script", $this->plugin->url . 'js/meta.js',
			array( 'jquery' ),
			1.1,
			true
		);
		
	}
	
	/**
	 * Render meta fields for content relations to post
	 *
	 */
	public function render_post_meta_relations($post)
	{
		
		/**
		 * add to post object for easy access
		 */
		$post->content_relations = new Store( $post->ID );
		$required = new Required();
		/**
		 * template file for content relations meta box
		 */
		include $this->plugin->dir . 'partials/meta-box.tpl.php';
//		include $this->plugin->dir . 'partials/content-relations-meta-box.tpl.php';
	}
	
	
	/**
	 * save content relations on post save
	 *
	 */
	public function save_post_meta_relations( $post_id )
	{
		
		// Check if our nonce is set.
		if ( ! isset( $_POST['ph_meta_box_content_relations_nonce'] ) ) {
			return $post_id;
		}
		
		
		$nonce = $_POST['ph_meta_box_content_relations_nonce'];
		
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'ph_meta_box_content_relations' ) ) {
			return $post_id;
		}
		
		
		// If this is an autosave, our form has not been submitted,
		//     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		
		/* OK, its safe for us to save the data now. */
		$store = new Store( $post_id );
		
		// check if there is any data
		if ( ! isset( $_POST['ph-content-relations-type'] ) || ! is_array( $_POST['ph-content-relations-type'] ) ) {
			$store->clear();
			return $post_id;
		}
		
		
		
		$types = $_POST['ph-content-relations-type'];
		/**
		 * check values
		 */
		if ( ! isset( $_POST['ph-content-relations-source-id'] ) || ! is_array( $_POST['ph-content-relations-source-id'] ) ){
			return $post_id;
		}
		if ( ! isset( $_POST['ph-content-relations-target-id'] ) || ! is_array( $_POST['ph-content-relations-target-id'] ) ){
			return $post_id;
		}
		$source_ids = $_POST['ph-content-relations-source-id'];
		$target_ids = $_POST['ph-content-relations-target-id'];
		
		$data = array();
		foreach ( $source_ids as $key => $source_id ) {
			$data[] = array(
				'source_id' => (int) $source_id,
				'target_id' => (int) $target_ids[ $key ],
				'type' => sanitize_text_field( $types[ $key ] ),
			);
		}
		$store->update( $data );
		
	}
	
	/**
	 * delete content relations on post delete
	 *
	 */
	public function delete_post_meta_relations($post_id) {
		$store = new Store( $post_id );
		$store->clear( true );
		
	}
}