<?php

namespace ContentRelations;

defined( 'WPINC' ) || exit;

/**
 * The Tools -> Content Relations screen: a WP_List_Table of relation types.
 *
 * Delete is an ordinary form post handled on load-{$hook} and answered with a redirect,
 * the pattern core uses so a reload does not repeat the action. Bulk delete comes from
 * the table's form, a single delete from a row-action link; the two nonces are checked
 * accordingly.
 */
class TypesScreen {

	const PAGE_SLUG = 'settings-content-realations';

	private Plugin $plugin;
	private ?TypesListTable $table = null;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		add_action( 'admin_menu', array( $this, 'menu_page' ) );
	}

	public function capability(): string {
		return 'manage_options';
	}

	public function menu_page(): void {
		$hook = add_submenu_page(
			'tools.php',
			__( 'Content Relations', 'ph-content-relations' ),
			__( 'Content Relations', 'ph-content-relations' ),
			$this->capability(),
			self::PAGE_SLUG,
			array( $this, 'render' )
		);

		if ( $hook ) {
			add_action( "load-$hook", array( $this, 'load' ) );
		}
	}

	public function load(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			return;
		}

		$this->handle_actions();

		$this->table = new TypesListTable( self::PAGE_SLUG );
		$this->table->prepare_items( $this->rows() );
	}

	private function pageUrl( array $args = array() ): string {
		return add_query_arg(
			array_merge( array( 'page' => self::PAGE_SLUG ), $args ),
			admin_url( 'tools.php' )
		);
	}

	/**
	 * The relation types with their relation counts.
	 *
	 * @return array
	 */
	private function rows(): array {
		$store = new \Content_Relations_Store();
		$rows  = array();
		foreach ( $store->get_types() as $type ) {
			$rows[] = array(
				'id'    => (int) $type->id,
				'name'  => (string) $type->type,
				'count' => (int) $store->get_relations_count_by_type( $type->id ),
			);
		}

		return $rows;
	}

	/**
	 * Delete, then redirect. Nothing is rendered from here.
	 */
	private function handle_actions(): void {
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : '';
		$bulk   = isset( $_REQUEST['action2'] ) && 'delete' === sanitize_key( $_REQUEST['action2'] );

		if ( 'delete' !== $action && ! $bulk ) {
			return;
		}

		$ids = array();
		if ( isset( $_REQUEST['types'] ) && is_array( $_REQUEST['types'] ) ) {
			$ids = array_map( 'intval', $_REQUEST['types'] );
		} elseif ( isset( $_REQUEST['type'] ) ) {
			$ids = array( (int) $_REQUEST['type'] );
		}

		if ( empty( $ids ) ) {
			return;
		}

		// A single delete is a row-action link carrying the types nonce; a bulk delete is
		// the list table's form, which carries its own bulk nonce.
		if ( isset( $_REQUEST['types'] ) ) {
			check_admin_referer( 'bulk-relation-types' );
		} else {
			check_admin_referer( TypesListTable::NONCE_ACTION );
		}

		$store   = new \Content_Relations_Store();
		$deleted = 0;
		foreach ( $ids as $id ) {
			if ( $id > 0 ) {
				$deleted += (int) $store->delete_type( $id );
			}
		}

		wp_safe_redirect( $this->pageUrl( array( 'message' => 'deleted', 'count' => $deleted ) ) );
		exit;
	}

	private function render_notice(): void {
		if ( ! isset( $_GET['message'] ) || 'deleted' !== sanitize_key( $_GET['message'] ) ) {
			return;
		}
		$count = isset( $_GET['count'] ) ? (int) $_GET['count'] : 0;
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( sprintf(
				/* translators: %s: number of deleted relations */
				_n( '%s relation deleted.', '%s relations deleted.', $count, 'ph-content-relations' ),
				number_format_i18n( $count )
			) )
		);
	}

	public function render(): void {
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Content Relations', 'ph-content-relations' ); ?></h1>
			<hr class="wp-header-end" />
			<?php $this->render_notice(); ?>
			<p><?php esc_html_e( 'Relation types are created when a relation is added in the editor. Delete a type here to remove it and every relation of it.', 'ph-content-relations' ); ?></p>
			<form method="get" action="<?php echo esc_url( admin_url( 'tools.php' ) ); ?>">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>" />
				<?php $this->table->search_box( __( 'Search types', 'ph-content-relations' ), 'relation-type' ); ?>
			</form>
			<form method="post" action="<?php echo esc_url( $this->pageUrl() ); ?>">
				<?php
				wp_nonce_field( 'bulk-relation-types' );
				$this->table->display();
				?>
			</form>
		</div>
		<?php
	}
}
