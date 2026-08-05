<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 03.04.18
 * Time: 08:21
 */

namespace ContentRelations;


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
			apply_filters( Plugin::FILTER_META_BOX_TITLE, __( 'Content relations', 'ph_content_relations' ), $post_type, $post),
			array( $this, 'render_post_meta_relations' )
		// 'post'
		);
		$this->enqueue_metabox_app( $post );
	}

	/**
	 * The compiled React meta box, the same editor the block editor sidebar uses.
	 *
	 * Replaces the hand-written jQuery (content-relations-admin.js) and its custom
	 * autocomplete/arrow styles. The relations are localized as the initial state, and
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
			$this->plugin->url . '/dist/meta-box.js',
			$meta['dependencies'],
			$meta['version'],
			true
		);

		// The block editor loads the @wordpress/components stylesheet on its own; the
		// classic editor does not, so without this the core components in the meta box
		// render unstyled.
		wp_enqueue_style( 'wp-components' );

		wp_set_script_translations( 'content-relations-meta-box', 'ph-content-relations', $this->plugin->path . '/languages' );

		// The outgoing relations of this post, in order, as the shared editor expects them.
		$store     = new \Content_Relations_Store( (int) $post->ID );
		$relations = array();
		foreach ( $store->get_relations() as $relation ) {
			if ( (int) $relation->source_id !== (int) $post->ID ) {
				continue;
			}
			$target_id   = (int) $relation->target_id;
			$relations[] = array(
				'target_id'   => $target_id,
				'type'        => (string) $relation->type,
				'post_title'  => get_the_title( $target_id ),
				'post_type'   => get_post_type( $target_id ),
				'post_status' => get_post_status( $target_id ),
			);
		}

		wp_localize_script( 'content-relations-meta-box', 'ContentRelationsMetaBox', array(
			'postId'    => (int) $post->ID,
			'relations' => $relations,
		) );

		// apiFetch needs the REST namespace too; the shared api.js reads it here.
		wp_localize_script( 'content-relations-meta-box', 'ContentRelationsEditor', array(
			'restNamespace' => RestEditor::NAMESPACE,
			'restField'     => RestEditor::FIELD,
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
		$store = new Content_Relations_Store( $post_id );

		// check if there is any data
		if ( ! isset( $_POST['ph-content-relations-type'] ) || ! is_array( $_POST['ph-content-relations-type'] ) ) {
			$store->clear();

			return $post_id;
		}


		$types = $_POST['ph-content-relations-type'];
		/**
		 * check values
		 */
		if ( ! isset( $_POST['ph-content-relations-source-id'] ) || ! is_array( $_POST['ph-content-relations-source-id'] ) ) {
			return $post_id;
		}
		if ( ! isset( $_POST['ph-content-relations-target-id'] ) || ! is_array( $_POST['ph-content-relations-target-id'] ) ) {
			return $post_id;
		}
		$source_ids = $_POST['ph-content-relations-source-id'];
		$target_ids = $_POST['ph-content-relations-target-id'];

		$data = array();
		foreach ( $source_ids as $key => $source_id ) {
			$data[] = array(
				'source_id' => (int) $source_id,
				'target_id' => (int) $target_ids[ $key ],
				'type'      => sanitize_text_field( $types[ $key ] ),
			);
		}
		$store->update( $data );

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
