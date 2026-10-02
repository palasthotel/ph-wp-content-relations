<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 03.04.18
 * Time: 08:21
 */

namespace ContentRelations;

defined( 'ABSPATH' ) || exit;


use Content_Relations_Required;
use Content_Relations_Store;
use WP_Query;

class MetaBox {

    public Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		// The Tools screen moved to its own TypesScreen class as a WP_List_Table; the
		// menu_page/render_menu that used to live here are gone.
		add_action( 'add_meta_boxes', array(
			$this,
			'add_post_meta_relations',
		), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post_meta_relations' ) );
		add_action( 'delete_post', array(
			$this,
			'delete_post_meta_relations',
		) );
		add_filter(Plugin::FILTER_ADD_META_BOX, array($this, 'should_add_meta_box'), 10, 3);
	}

	/**
	 * @param $add
	 * @param $post_type
	 * @param $post
	 *
	 * @return bool
	 */
	public function should_add_meta_box($add, $post_type, $post){
		// some plugins like "Members" use meta boxes but are no real post
		return ($post instanceof \WP_Post);
	}

	/**
	 * Register meta fields for content relations to post
	 *
	 */
	public function add_post_meta_relations( $post_type, $post ) {

		// should I render meta box?
		if ( ! apply_filters( Plugin::FILTER_ADD_META_BOX, true, $post_type, $post ) ) {
			return;
		}

		// In the block editor the sidebar panel does this job, so the meta box would just
		// be a second, worse copy of it. It stays for the classic editor.
		$screen = get_current_screen();
		if ( $screen && method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
			return;
		}

		add_meta_box(
			'ph_meta_box_content_relations',
			// "Content relations" is the plugin's name, not translated.
			apply_filters( Plugin::FILTER_META_BOX_TITLE, 'Content relations', $post_type, $post),
			array( $this, 'render_post_meta_relations' )
		// 'post'
		);
		$this->enqueue_metabox_app( $post );
	}

	/**
	 * The compiled React meta box, the same editor the block editor sidebar uses.
	 *
	 * Replaced the hand-written jQuery meta box (content-relations-admin.js, since deleted)
	 * and its custom autocomplete/arrow styles. The relations are localized as the initial state, and
	 * the component writes the hidden fields save_post_meta_relations reads, so the save
	 * path is unchanged.
	 */
	private function enqueue_metabox_app( $post ): void {
		$dir   = $this->plugin->path . '/dist';
		$asset = $dir . '/meta-box.asset.php';

		// dist/ is built by the pipeline and is not in the repository.
		if ( ! file_exists( $asset ) ) {
			return;
		}

		$meta = include $asset;

		wp_enqueue_script(
			'content-relations-meta-box',
			$this->plugin->url . 'dist/meta-box.js',
			$meta['dependencies'],
			$meta['version'],
			true
		);

		// The block editor loads the @wordpress/components stylesheet on its own; the
		// classic editor does not, so without this the core components in the meta box
		// render unstyled.
		wp_enqueue_style( 'wp-components' );

		// The outgoing relations of this post, in order, as the shared editor expects them.
		$store     = new \Content_Relations_Store( (int) $post->ID );
		$relations = array();
		foreach ( $store->get_relations() as $relation ) {
			if ( (int) $relation->source_id !== (int) $post->ID ) {
				continue;
			}
			$relations[] = RestEditor::editor_relation( (int) $relation->target_id, (string) $relation->type );
		}

		wp_localize_script( 'content-relations-meta-box', 'ContentRelationsMetaBox', array(
			'postId'    => (int) $post->ID,
			'relations' => $relations,
		) );

		// apiFetch needs the REST namespace too; the shared api.js reads it here.
		wp_localize_script( 'content-relations-meta-box', 'ContentRelationsEditor', array(
			'restNamespace' => RestEditor::NAMESPACE,
			'restField'     => RestEditor::FIELD,
			'i18n'          => RelationsI18n::strings(),
		) );
	}

	/**
	 * Render meta fields for content relations to post
	 *
	 */
	public function render_post_meta_relations( $post ) {

		/**
		 * add to post object for easy access
		 */
		$post->content_relations = new Content_Relations_Store( $post->ID );
		$required                = new Content_Relations_Required();
		/**
		 * template file for content relations meta box
		 */
		include dirname( __FILE__ ) . '/../parts/content-relations-meta-box.tpl.php';
	}

	/**
	 * save content relations on post save
	 *
	 */
	public function save_post_meta_relations( $post_id ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['ph_meta_box_content_relations_nonce'] ) ) {
			return $post_id;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ph_meta_box_content_relations_nonce'] ) ), 'ph_meta_box_content_relations' ) ) {
			return $post_id;
		}

		// If this is an autosave, our form has not been submitted,
		//     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// save_post also fires for the revision WordPress stores alongside, with the same
		// $_POST - the relations belong to the post, not to its revisions.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		// The nonce only proves the request came from a meta box form, any meta box form
		// of this user - not that they may edit this post.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		/* OK, its safe for us to save the data now. */
		$store = new Content_Relations_Store( $post_id );

		// check if there is any data
		if ( ! isset( $_POST['ph-content-relations-type'] ) || ! is_array( $_POST['ph-content-relations-type'] ) ) {
			$store->clear();

			return $post_id;
		}

		if ( ! isset( $_POST['ph-content-relations-target-id'] ) || ! is_array( $_POST['ph-content-relations-target-id'] ) ) {
			return $post_id;
		}
		$types      = wp_unslash( $_POST['ph-content-relations-type'] );
		$target_ids = wp_unslash( $_POST['ph-content-relations-target-id'] );

		// The source is always the post being saved. The form still sends a source id
		// per relation, and that used to be taken from the request as it was - so anyone
		// who could save one post could write relations for any other post, including
		// ones they cannot edit. It is ignored now.
		$data = array();
		foreach ( $types as $key => $type ) {
			$target_id = isset( $target_ids[ $key ] ) ? (int) $target_ids[ $key ] : 0;
			$type      = is_string( $type ) ? sanitize_text_field( $type ) : '';
			if ( '' === $type || ! RestEditor::can_link_target( (int) $post_id, $target_id ) ) {
				continue;
			}
			$data[] = array(
				'source_id' => (int) $post_id,
				'target_id' => $target_id,
				'type'      => $type,
			);
		}
		$store->update( $data );

		return $post_id;
	}

	/**
	 * delete content relations on post delete
	 *
	 */
	public function delete_post_meta_relations( $post_id ) {

		$store = new Content_Relations_Store( $post_id );
		$store->clear( true );

	}

}
