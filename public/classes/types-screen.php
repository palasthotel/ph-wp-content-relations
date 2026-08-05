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

	const PAGE_SLUG = 'settings-content-relations';

	// The misspelled slug 1.0.15 shipped with (note "realations"). Anyone who bookmarked
	// or linked the page under the typo gets redirected rather than "Sorry, you are not
	// allowed to access this page." - see menu_page().
	const LEGACY_PAGE_SLUG = 'settings-content-realations';

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

		// Registering under the old slug keeps its hook reachable by direct URL -
		// remove_submenu_page() only hides the menu entry, not the registration. The
		// redirect has to run on load-{$hook}, before admin-header.php starts sending
		// output - the same reason handle_actions() below runs there rather than in
		// render(). A first attempt put it in the page callback itself, which fired too
		// late: "Cannot modify header information - headers already sent".
		$legacy_hook = add_submenu_page(
			'tools.php',
			'',
			'',
			$this->capability(),
			self::LEGACY_PAGE_SLUG,
			'__return_null'
		);
		if ( $legacy_hook ) {
			add_action( "load-$legacy_hook", array( $this, 'redirect_legacy_slug' ) );
		}
		remove_submenu_page( 'tools.php', self::LEGACY_PAGE_SLUG );
	}

	/**
	 * Sends a visitor of the old, misspelled URL to the same screen under the corrected
	 * slug, keeping every other query argument (action, type, s, orderby, message, ...).
	 */
	public function redirect_legacy_slug(): void {
		$args = $_GET;
		unset( $args['page'] );
		wp_safe_redirect( add_query_arg(
			array_merge( array( 'page' => self::PAGE_SLUG ), $args ),
			admin_url( 'tools.php' )
		) );
		exit;
	}

	public function load(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			return;
		}

		$this->handle_actions();

		if ( $this->current_edit_type() ) {
			$this->enqueue_edit_app( $this->current_edit_type() );
			return;
		}

		$this->table = new TypesListTable( self::PAGE_SLUG );
		$this->table->prepare_items( $this->rows() );
	}

	/**
	 * The type being edited, or 0 for the list.
	 */
	private function current_edit_type(): int {
		if ( ! isset( $_GET['action'] ) || 'edit' !== sanitize_key( $_GET['action'] ) ) {
			return 0;
		}
		$id = isset( $_GET['type'] ) ? (int) $_GET['type'] : 0;
		if ( $id <= 0 ) {
			return 0;
		}
		$store = new \Content_Relations_Store();
		foreach ( $store->get_types() as $type ) {
			if ( (int) $type->id === $id ) {
				return $id;
			}
		}

		return 0;
	}

	private function type_name( int $type_id ): string {
		$store = new \Content_Relations_Store();
		foreach ( $store->get_types() as $type ) {
			if ( (int) $type->id === $type_id ) {
				return (string) $type->type;
			}
		}

		return '';
	}

	/**
	 * The relations of a type, grouped by source post, for the edit screen.
	 *
	 * @param string $type_name
	 * @return array groups: [ { source_id, source_title, targets: [ { target_id, title, post_type, post_status } ] } ]
	 */
	private function grouped_relations( string $type_name ): array {
		global $wpdb;

		// The relations of this type, source and target, ordered by weight so each
		// source post's targets come out in the order the editor saved them.
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT r.source_id, r.target_id
			 FROM {$wpdb->prefix}content_relations r
			 LEFT JOIN {$wpdb->prefix}content_relations_types t ON r.type_id = t.id
			 WHERE t.type = %s
			 ORDER BY r.source_id ASC, r.weight ASC",
			$type_name
		) );

		$groups = array();
		foreach ( $rows as $row ) {
			$source_id = (int) $row->source_id;
			if ( ! isset( $groups[ $source_id ] ) ) {
				$groups[ $source_id ] = array(
					'source_id'    => $source_id,
					'source_title' => get_the_title( $source_id ),
					'targets'      => array(),
				);
			}
			$target_id = (int) $row->target_id;
			$groups[ $source_id ]['targets'][] = array(
				'target_id'   => $target_id,
				'title'       => get_the_title( $target_id ),
				'post_type'   => get_post_type( $target_id ),
				'post_status' => get_post_status( $target_id ),
			);
		}

		return array_values( $groups );
	}

	private function enqueue_edit_app( int $type_id ): void {
		$dir   = $this->plugin->path . '/dist';
		$asset = $dir . '/types-edit.asset.php';
		if ( ! file_exists( $asset ) ) {
			return;
		}
		$meta = include $asset;

		wp_enqueue_script(
			'content-relations-types-edit',
			$this->plugin->url . 'dist/types-edit.js',
			$meta['dependencies'],
			$meta['version'],
			true
		);
		wp_enqueue_style( 'wp-components' );
		wp_set_script_translations( 'content-relations-types-edit', 'ph-content-relations', $this->plugin->path . '/languages' );

		$type_name = $this->type_name( $type_id );
		wp_localize_script( 'content-relations-types-edit', 'ContentRelationsTypeEdit', array(
			'restNamespace' => RestEditor::NAMESPACE,
			'type'          => $type_name,
			'groups'        => $this->grouped_relations( $type_name ),
		) );
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
		if ( $this->current_edit_type() ) {
			$this->render_edit( $this->current_edit_type() );
			return;
		}
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

	private function render_edit( int $type_id ): void {
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php
				printf(
					/* translators: %s: relation type name */
					esc_html__( 'Edit relation type: %s', 'ph-content-relations' ),
					esc_html( $this->type_name( $type_id ) )
				);
				?>
			</h1>
			<a href="<?php echo esc_url( $this->pageUrl() ); ?>" class="page-title-action">
				<?php esc_html_e( 'Back to types', 'ph-content-relations' ); ?>
			</a>
			<hr class="wp-header-end" />
			<div id="content-relations-type-edit-root"></div>
		</div>
		<?php
	}
}
