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
			$type_id           = intval( $_POST['delete_relation'] );
			$deleted_relations = $store->delete_type( $type_id );
			$deleted_relations = "<p>" . $deleted_relations . " had been deleted</p>";
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
					      action="<?php echo sanitize_text_field( $_SERVER['PHP_SELF'] ) . '?page=' . sanitize_text_field( $page ); ?>">
						<tr>
							<th scope="row"><?php echo $relation_type->type ?>
								(<?php echo $store->get_relations_count_by_type( $relation_type->id ); ?>
								)
							</th>
							<input type="hidden" name="delete_relation"
							       value="<?php echo $relation_type->id; ?>"/>
							<td><?php submit_button( 'Löschen', 'delete delete-relation-button button-primary', 'delete_' . $relation_type->id ); ?></td>
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

		add_meta_box(
			'ph_meta_box_content_relations',
			apply_filters( Plugin::FILTER_META_BOX_TITLE, __( 'Content relations', 'ph_content_relations' ), $post_type, $post),
			array( $this, 'render_post_meta_relations' )
		// 'post'
		);
		/**
		 * Add css and javascript for meta box
		 */
		wp_enqueue_style(
			'content-relations-style', $this->plugin->url . '/css/content-relations-admin.css',
			array(),
			1,
			'all'
		);
		wp_enqueue_script(
			'content-relations-js', $this->plugin->url . '/js/content-relations-admin.js',
			array( 'jquery', 'jquery-ui-autocomplete', 'jquery-ui-sortable' ),
			1,
			false
		);
		wp_localize_script(
			'content-relations-js',
			'_ContentRelations',
			array(
				"config" => array(
					"ID" => $post->ID,
					"post_type" => $post_type,
				),
				"i18n" => array(

				),
			)
		);

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
			if ( is_a( $post, "WP_Post" ) ) {
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
		$item['post_status'] = get_post( $post->ID );
		$item['format']      = get_post_format( $post->ID );
		if ( $post->post_type == "attachment" ) {
			$item['src'] = wp_get_attachment_image_src( $post->ID, 'thumbnail', false );
		}
		$item['pub_date'] = get_the_date( 'l, F j, Y', $post->ID );

		return $item;
	}
}