<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 07.09.16
 * Time: 14:45
 */

namespace ContentRelations;


class Tools {
	const PAGE_SLUG = "settings-content-relations";
	
	public function __construct(\ContentRelations $plugin) {
		add_action( 'admin_menu', array( $this, 'menu_page') );
	}
	/**
	 * Register the menu page for gallery sharing
	 *
	 */
	public function menu_page()
	{
		add_submenu_page(
			'tools.php',
			'Content Relations',
			'Content Relations',
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_menu' )
		);
	}
	
	/**
	 *  renders settings page
	 */
	public function render_menu()
	{
		$store = new Store();
		
		$deleted_relations = "";
		if ( isset( $_POST[ 'delete_relation' ] ) && is_numeric($_POST["delete_relation"]) ){
			$type_id = intval($_POST['delete_relation']);
			$deleted_relations = $store->delete_type($type_id);
			$deleted_relations = "<p>".$deleted_relations." had been deleted</p>";
		}
		
		$relation_types = $store->get_types();
		
		$url = add_query_arg(array(
			'page' => self::PAGE_SLUG,
		), admin_url( 'tools.php' ) );
		
		?>
		<div class="wrap delete-relations-wrapper">
			<h2>Content Relations</h2>
			
			<?php echo $deleted_relations; ?>
			<table class="form-table">
				<?php
				foreach ( $relation_types as $relation_type ) {
					?>
					<form method="post" action="<?php echo $url; ?>">
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
	
}