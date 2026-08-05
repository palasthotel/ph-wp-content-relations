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
		add_action( 'admin_menu', array( $this, 'menu_page' ) );
		add_action( 'add_meta_boxes', array(
			$this,
			'add_post_meta_relations',
		), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post_meta_relations' ) );
		add_action( 'delete_post', array(
			$this,
			'delete_post_meta_relations',
		) );
		add_action( 'wp_ajax_ph_content_relations_title', array(
			$this,
			'get_contents_by_title',
		) );
		add_filter(Plugin::FILTER_ADD_META_BOX, array($this, 'should_add_meta_box'), 10, 3);
	}

	/**
	 * Register the menu page for gallery sharing
	 *
	 */
	public function menu_page() {
		add_submenu_page(
			'tools.php',
			'Content Relations',
			'Content Relations',
			'manage_options',
			'settings-content-realations',
			array( $this, 'render_menu' )
		);
	}

	/**
	 *  renders settings page
	 */
	public function render_menu() {
		$store = new Content_Relations_Store();

		$deleted_relations = "";
		if ( isset( $_POST['delete_relation'] ) && is_numeric( $_POST["delete_relation"] ) ) {
			// This deleted a whole relation type on any POST, with no nonce - so a page
			// on another site could delete an admin's relation types by CSRF. The token
			// is bound to the type id, so the confirm cannot be replayed for another one.
			$type_id = intval( $_POST['delete_relation'] );
			check_admin_referer( 'delete_relation_' . $type_id );
			$deleted           = (int) $store->delete_type( $type_id );
			$deleted_relations = '<div class="notice notice-success"><p>' . sprintf(
				/* translators: %d: number of deleted relations */
				esc_html__( '%d relations had been deleted', 'ph-content-relations' ),
				$deleted
			) . '</p></div>';
		}

		$relation_types = $store->get_types();
		$page           = 'settings-content-realations';
		?>
		<div class="wrap delete-relations-wrapper">
			<h2>Content Relations</h2>

			<?php echo $deleted_relations; ?>
			<table class="form-table">
				<?php
				foreach ( $relation_types as $relation_type ) {
					?>
					<form method="post"
					      action="<?php echo esc_url( add_query_arg( 'page', $page, admin_url( 'tools.php' ) ) ); ?>">
						<?php wp_nonce_field( 'delete_relation_' . $relation_type->id ); ?>
						<tr>
							<th scope="row"><?php echo esc_html( $relation_type->type ); ?>
								(<?php echo (int) $store->get_relations_count_by_type( $relation_type->id ); ?>
								)
							</th>
							<input type="hidden" name="delete_relation"
							       value="<?php echo esc_attr( $relation_type->id ); ?>"/>
							<td><?php submit_button( __( 'Delete', 'ph-content-relations' ), 'delete delete-relation-button button-primary', 'delete_' . $relation_type->id ); ?></td>
						</tr>
					</form>
					<?php
				}
				?>
			</table>
			<script type="text/javascript">
				jQuery('.delete-relations-wrapper')
					.on('click', '.delete-relation-button', function(e) {
						if (!confirm(
							'Do you really want to delete this relation type and all post relations of this type?')) {
							e.preventDefault();
						}
					});
			</script>
		</div>
		<?php
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

	/**
	 * Endpoint for getting gallery ids
	 */
	public function get_contents_by_title() {

		// This endpoint used to answer any logged-in user, with no nonce, searching
		// post_status "any" - so a subscriber could read drafts, private posts and, via
		// the whole-object bug below, password hashes. It is a picker for the relations
		// metabox, so it needs the capability that metabox needs and its nonce.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json( array( 'result' => array() ), 403 );
		}
		check_ajax_referer( 'ph_content_relations_title' );

		if ( ! isset( $_GET['q'] ) || ! isset( $_GET['post_id'] ) || ! isset( $_GET['post_type'] ) ) {
			print json_encode( array( 'result' => array() ) );
			die();
		}

		$query_string = sanitize_text_field( $_GET['q'] );
		$post_id_context = sanitize_text_field( $_GET['post_id'] );
		$post_type_context = sanitize_text_field( $_GET['post_type'] );
		$result       = array();

		/**
		 * first have a look if its a post id
		 */
		if ( is_numeric( $query_string ) ) {
			$post = get_post( $query_string );
			// current_user_can( 'read_post', … ) so a direct id lookup cannot pull up a
			// draft or private post the searcher is not allowed to see.
			if ( is_a( $post, "WP_Post" ) && current_user_can( 'read_post', $post->ID ) ) {
				$result[ $post->post_type ] = array( $this->get_contents_item( $post ) );
				wp_reset_postdata();
			}
		}


		$post_types = apply_filters(
			Plugin::FILTER_META_BOX_POST_TYPES,
			get_post_types( array( 'public' => true ) ),
			$post_type_context,
			$post_id_context
		);
		$types = array();
		foreach ( $post_types as $type ) {
			$type_object = get_post_type_object($type);
			if ( $type == "landing_page" || $type == "sidebar" ) {
				continue;
			}
			$args  = array(
				'posts_per_page' => 20,
				's'              => $query_string,
				'post_status'    => 'any',
				// 'readable' makes WP_Query drop statuses the current user may not read -
				// other authors' drafts and private posts - instead of returning every
				// status to anyone who can edit a post.
				'perm'           => 'readable',
				'post_type'      => $type,
			);
			$query = new WP_Query( apply_filters( Plugin::FILTER_META_BOX_FIND_QUERY_ARGS, $args ) );
			/**
			 * Cleanup WP_Query results to minimize result size
			 * Add gallery images for preview in backend
			 */

			foreach ( $query->posts as $post ) {
				$types[$type] = $type_object->labels->name;
				$item   = $this->get_contents_item( $post );
				$format = $item["format"];
				if ( ! $format ) {
					$_type = $type;
				} else {
					$_type = $type . "_" . $format;
				}
				if ( ! isset( $results[ $_type ] ) ) {
					$results[ $_type ] = array();
				}
				$result[ $_type ][] = $item;
			}
			wp_reset_postdata();
		}

		// print json for JavaScript result
		print json_encode( array( 'result' => $result, 'types' => $types ) );
		die();
	}

	private function get_contents_item( $post ) {
		$item                = array();
		$item['post_title']  = $post->post_title;
		$item['ID']          = $post->ID;
		$item['post_type']   = $post->post_type;
		// Was get_post(), which serialised the whole WP_Post - post_content and
		// post_password included - into the JSON. The script only compares this to
		// "trash", so the status string is all it ever needed.
		$item['post_status'] = get_post_status( $post->ID );
		$item['format']      = get_post_format( $post->ID );
		if ( $post->post_type == "attachment" ) {
			$item['src'] = wp_get_attachment_image_src( $post->ID, 'thumbnail', false );
		}
		$item['pub_date'] = get_the_date( 'l, F j, Y', $post->ID );

		return $item;
	}
}
