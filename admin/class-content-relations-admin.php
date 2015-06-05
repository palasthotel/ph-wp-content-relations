<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 */
class Content_Relations_Admin
{

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
	public function __construct( $plugin_name, $version )
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the menu page for gallery sharing
	 *
	 */
	public function menu_page()
	{
		add_submenu_page( 'tools.php', 'Content Relations', 'Content Relations', 'manage_options', 'settings-'.$this->plugin_name, array( $this, 'render_menu' ) );
	}

	/**
	 *  renders settings page
	 */
	public function render_menu()
	{
		$store = new Content_Relations_Store();
		
		$deleted_relations = "";
		if ( isset( $_POST[ 'delete_relation' ] ) && is_numeric($_POST["delete_relation"]) ){
			$type_id = intval($_POST['delete_relation']);
			$deleted_relations = $store->delete_type($type_id);
			$deleted_relations = "<p>".$deleted_relations." had been deleted</p>";
		}		

		$relation_types = $store->get_types();
		$page = 'settings-'.$this->plugin_name;
		?>
		<div class="wrap delete-relations-wrapper">
			<h2>Content Relations</h2>
			
				<?php echo $deleted_relations; ?>
				<table class="form-table">
				<?php
				foreach ( $relation_types as $relation_type ) {
					?>
					<form method="post" action="<?php echo sanitize_text_field( $_SERVER['PHP_SELF'] ).'?page='.sanitize_text_field( $page ); ?>">
					<tr>
						<th scope="row"><?php echo $relation_type->type ?> (<?php echo $store->get_relations_count_by_type($relation_type->id); ?>)</th>
						<input type="hidden" name="delete_relation" value="<?php echo $relation_type->id ; ?>" />
						<td><?php submit_button( 'Löschen' ,'delete delete-relation-button button-primary', 'delete_'.$relation_type->id ); ?></td>
					</tr>
					</form>
					<?php
				}	
				?>
				</table>
				<script type="text/javascript">
				jQuery(".delete-relations-wrapper").on("click", ".delete-relation-button", function(e){
					if(!confirm("Do you really want to delete this relation type and all post relations of this type?")){
						e.preventDefault();
					}
				});
				</script>			
		</div>
		<?php
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
			$this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/content-relations-admin.css',
			array(),
			$this->version,
			'all'
		);
		wp_enqueue_script(
			$this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/content-relations-admin.js',
			array( 'jquery', 'jquery-ui-autocomplete' ),
			$this->version,
			false
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
		$post->content_relations = new Content_Relations_Store( $post->ID );
		$required = new Content_Relations_Required();
		/**
		 * template file for content relations meta box
		 */
		include plugin_dir_path( __FILE__ ) . 'partials/content-relations-meta-box.tpl.php';
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

		$store = new Content_Relations_Store( $post_id );
		$store->clear( true );

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
