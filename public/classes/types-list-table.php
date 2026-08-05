<?php

namespace ContentRelations;

defined( 'WPINC' ) || exit;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The relation types on the Tools screen, as the table WordPress uses everywhere else.
 *
 * WP_List_Table is what gives Posts, Pages and Users their search box, sortable columns,
 * row actions, bulk actions and pagination. The screen used to be a hand-built form
 * table with one delete form per type; this is the same list the rest of wp-admin uses.
 */
class TypesListTable extends \WP_List_Table {

	const NONCE_ACTION = 'content_relations_types';

	private string $pageSlug;

	public function __construct( string $pageSlug ) {
		$this->pageSlug = $pageSlug;
		parent::__construct( array(
			'singular' => 'relation-type',
			'plural'   => 'relation-types',
			'ajax'     => false,
		) );
	}

	public function get_columns(): array {
		return array(
			'cb'    => '<input type="checkbox" />',
			'name'  => _x( 'Type', 'list table', 'ph-content-relations' ),
			'count' => _x( 'Relations', 'list table', 'ph-content-relations' ),
		);
	}

	protected function get_sortable_columns(): array {
		return array(
			'name'  => array( 'name', false ),
			'count' => array( 'count', false ),
		);
	}

	protected function get_bulk_actions(): array {
		return array(
			'delete' => _x( 'Delete', 'list table', 'ph-content-relations' ),
		);
	}

	protected function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="types[]" value="%d" />', (int) $item['id'] );
	}

	protected function column_name( $item ): string {
		$deleteUrl = wp_nonce_url(
			add_query_arg(
				array( 'page' => $this->pageSlug, 'action' => 'delete', 'type' => (int) $item['id'] ),
				admin_url( 'tools.php' )
			),
			self::NONCE_ACTION
		);

		$actions = array(
			'delete' => sprintf(
				'<a href="%s" class="submitdelete" onclick="return confirm(%s)">%s</a>',
				esc_url( $deleteUrl ),
				esc_js( wp_json_encode( __( 'Delete this relation type and every relation of it?', 'ph-content-relations' ) ) ),
				esc_html_x( 'Delete', 'list table', 'ph-content-relations' )
			),
		);

		return sprintf(
			'<strong>%s</strong>%s',
			esc_html( $item['name'] ),
			$this->row_actions( $actions )
		);
	}

	protected function column_count( $item ): string {
		return (string) (int) $item['count'];
	}

	public function no_items() {
		esc_html_e( 'No relation types yet. They appear here once a relation is created.', 'ph-content-relations' );
	}

	/**
	 * @param array $rows already assembled: id, name, count
	 */
	public function prepare_items( array $rows = array() ): void {
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		if ( '' !== $search ) {
			$needle = strtolower( $search );
			$rows   = array_values( array_filter( $rows, function ( $row ) use ( $needle ) {
				return false !== strpos( strtolower( $row['name'] ), $needle );
			} ) );
		}

		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_key( $_REQUEST['orderby'] ) : 'name';
		$order   = ( isset( $_REQUEST['order'] ) && 'desc' === strtolower( (string) $_REQUEST['order'] ) ) ? 'desc' : 'asc';
		if ( in_array( $orderby, array( 'name', 'count' ), true ) ) {
			usort( $rows, function ( $a, $b ) use ( $orderby ) {
				return 'count' === $orderby
					? $a['count'] <=> $b['count']
					: strnatcasecmp( $a['name'], $b['name'] );
			} );
			if ( 'desc' === $order ) {
				$rows = array_reverse( $rows );
			}
		}

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$perPage = 20;
		$total   = count( $rows );
		$page    = $this->get_pagenum();

		$this->items = array_slice( $rows, ( $page - 1 ) * $perPage, $perPage );
		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $perPage,
			'total_pages' => (int) ceil( $total / $perPage ),
		) );
	}
}
