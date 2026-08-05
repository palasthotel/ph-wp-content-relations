<?php

namespace ContentRelations;

defined( 'WPINC' ) || exit;

use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Server;

/**
 * REST surface for the block editor sidebar and the reworked meta box.
 *
 * Three things the editor needs and the read-only content_relations field cannot give it:
 *
 * - an updatable field so relations save with the post, the way core saves everything
 *   else (no separate "save relations" button)
 * - the list of relation types, to offer them in the picker
 * - a post search, so a relation target can be found by title
 *
 * The existing content_relations field (see rest-api.php) is left untouched: headless
 * front ends read it, and it returns both directions of a relation. This edit field only
 * concerns the outgoing relations of the post being edited, which is what the meta box
 * has always let you change.
 */
class RestEditor {

	const NAMESPACE = 'content-relations/v1';

	/**
	 * The editable field on the post. Named _edit so it cannot be confused with the
	 * read field, and only exposed in the edit context.
	 */
	const FIELD = 'content_relations_edit';

	private Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	public function rest_api_init(): void {
		$this->register_edit_field();

		register_rest_route( self::NAMESPACE, '/types', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_types' ),
			'permission_callback' => array( $this, 'can_edit_posts' ),
		) );

		register_rest_route( self::NAMESPACE, '/search', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'search' ),
			'permission_callback' => array( $this, 'can_edit_posts' ),
			'args'                => array(
				'q'         => array( 'type' => 'string', 'required' => true ),
				'post_type' => array( 'type' => 'string', 'required' => false ),
				'exclude'   => array( 'type' => 'array', 'required' => false ),
			),
		) );
	}

	/**
	 * The relations field is only useful to someone editing the post.
	 */
	public function can_edit_posts(): bool {
		return current_user_can( 'edit_posts' );
	}

	private function register_edit_field(): void {
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		register_rest_field( $post_types, self::FIELD, array(
			'get_callback'    => array( $this, 'get_field' ),
			'update_callback' => array( $this, 'update_field' ),
			'schema'          => array(
				'description' => 'Outgoing content relations of this post.',
				'type'        => 'array',
				'context'     => array( 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'target_id' => array( 'type' => 'integer' ),
						'type'      => array( 'type' => 'string' ),
					),
				),
			),
		) );
	}

	/**
	 * The post's outgoing relations, in order, with what the sidebar needs to show them.
	 *
	 * @param array $post
	 * @return array
	 */
	public function get_field( $post ): array {
		$post_id = (int) $post['id'];
		$store   = new \Content_Relations_Store( $post_id );

		$out = array();
		foreach ( $store->get_relations() as $relation ) {
			// Only the outgoing ones: the sidebar edits what this post points at, and
			// the store's update() clears exactly those.
			if ( (int) $relation->source_id !== $post_id ) {
				continue;
			}
			$target_id = (int) $relation->target_id;
			$out[]     = array(
				'target_id'  => $target_id,
				'type'       => (string) $relation->type,
				'post_title' => get_the_title( $target_id ),
				'post_type'  => get_post_type( $target_id ),
				'post_status' => get_post_status( $target_id ),
			);
		}

		return $out;
	}

	/**
	 * Replace the post's outgoing relations with the submitted list.
	 *
	 * @param mixed    $value
	 * @param \WP_Post $post
	 * @return true|WP_Error
	 */
	public function update_field( $value, $post ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return new WP_Error(
				'content_relations_cannot_edit',
				__( 'You are not allowed to edit the relations of this post.', 'ph-content-relations' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! is_array( $value ) ) {
			return new WP_Error(
				'content_relations_invalid',
				__( 'Relations must be a list.', 'ph-content-relations' ),
				array( 'status' => 400 )
			);
		}

		$data = array();
		foreach ( $value as $relation ) {
			$target_id = isset( $relation['target_id'] ) ? (int) $relation['target_id'] : 0;
			$type      = isset( $relation['type'] ) ? sanitize_text_field( $relation['type'] ) : '';
			if ( $target_id <= 0 || '' === $type ) {
				continue;
			}
			$data[] = array(
				'source_id' => (int) $post->ID,
				'target_id' => $target_id,
				'type'      => $type,
			);
		}

		$store = new \Content_Relations_Store( (int) $post->ID );
		$store->update( $data );

		return true;
	}

	/**
	 * Existing relation type names, for the picker.
	 *
	 * @return array
	 */
	public function get_types(): array {
		$store = new \Content_Relations_Store();
		$types = array();
		foreach ( $store->get_types() as $type ) {
			$types[] = (string) $type->type;
		}
		sort( $types );

		return $types;
	}

	/**
	 * Search posts by title for the relation target picker.
	 *
	 * Mirrors what the classic meta box's AJAX endpoint does, but as a proper REST
	 * route: capability-gated, and perm=readable so it never surfaces a draft or private
	 * post the searcher may not see.
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function search( WP_REST_Request $request ): array {
		$q       = sanitize_text_field( (string) $request->get_param( 'q' ) );
		$exclude = array_map( 'intval', (array) $request->get_param( 'exclude' ) );

		if ( '' === $q ) {
			return array();
		}

		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$post_types = apply_filters( Plugin::FILTER_META_BOX_POST_TYPES, $post_types, $request->get_param( 'post_type' ), 0 );

		$args = array(
			'posts_per_page'   => 20,
			's'                => $q,
			'post_status'      => 'any',
			'perm'             => 'readable',
			'post_type'        => array_values( $post_types ),
			'post__not_in'     => $exclude,
			'no_found_rows'    => true,
			'suppress_filters' => false,
		);

		$query   = new WP_Query( apply_filters( Plugin::FILTER_META_BOX_FIND_QUERY_ARGS, $args ) );
		$results = array();
		foreach ( $query->posts as $post ) {
			$results[] = array(
				'target_id'  => (int) $post->ID,
				'post_title' => get_the_title( $post ),
				'post_type'  => $post->post_type,
				'post_status' => get_post_status( $post ),
			);
		}
		wp_reset_postdata();

		return $results;
	}
}
