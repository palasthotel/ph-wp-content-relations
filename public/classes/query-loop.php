<?php

namespace ContentRelations;

defined( 'WPINC' ) || exit;

/**
 * Lets the core Query Loop block filter by a relation type, scoped to the post the block
 * is placed on - the block editor's equivalent of what the sidebar and meta box already
 * let you build: a "related posts" section listing this post's outgoing relations of one
 * type, in the order the editor (or the Tools screen) put them in.
 *
 * The block variation is registered in JavaScript and writes the relation type into the
 * block's own query attribute, as query.content_relations_type. Two places then have to
 * turn that into a real query:
 *
 * - the front end, through query_loop_block_query_vars - the source post is simply the
 *   post currently being rendered (get_the_ID()), since the block is saved as part of that
 *   post's own content and only ever renders alongside it
 * - the editor preview, which is not rendered by PHP at all: the Post Template block
 *   builds a REST request from the query attribute and spreads keys it does not know into
 *   it, so both the type and the currently-edited post's id
 *   (query.content_relations_source, written by the same control) arrive at
 *   /wp/v2/<post type> and are picked up by rest_{$post_type}_query
 *
 * Both paths end in the same translation: the post's related target ids of that type, in
 * their saved order, as post__in.
 */
class QueryLoop {

	const QUERY_TYPE_KEY   = 'content_relations_type';
	const QUERY_SOURCE_KEY = 'content_relations_source';

	private Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		add_filter( 'query_loop_block_query_vars', array( $this, 'query_loop_block_query_vars' ), 10, 2 );

		add_action( 'rest_api_init', function () {
			foreach ( get_post_types( array( 'public' => true, 'show_in_rest' => true ) ) as $post_type ) {
				add_filter( "rest_{$post_type}_query", array( $this, 'rest_query' ), 10, 2 );
			}
		} );
	}

	/**
	 * Front end: the Query Loop's WP_Query arguments.
	 *
	 * @param array     $query
	 * @param \WP_Block $block
	 * @return array
	 */
	public function query_loop_block_query_vars( $query, $block ) {
		$type = $block->context['query'][ self::QUERY_TYPE_KEY ] ?? null;

		return $this->apply_relation( $query, $type, get_the_ID() );
	}

	/**
	 * Editor preview: the REST arguments of the posts collection.
	 *
	 * @param array            $args
	 * @param \WP_REST_Request $request
	 * @return array
	 */
	public function rest_query( $args, $request ) {
		return $this->apply_relation(
			$args,
			$request->get_param( self::QUERY_TYPE_KEY ),
			(int) $request->get_param( self::QUERY_SOURCE_KEY )
		);
	}

	/**
	 * Turns a relation type and its source post into post__in, or leaves the arguments
	 * alone.
	 */
	private function apply_relation( array $args, $type, $source_id ): array {
		if ( ! is_string( $type ) || '' === $type || ! $source_id ) {
			return $args;
		}

		$store = new \Content_Relations_Store( (int) $source_id );
		$ids   = array();
		foreach ( $store->get_relations() as $relation ) {
			if ( (int) $relation->source_id !== (int) $source_id ) {
				continue;
			}
			if ( (string) $relation->type !== $type ) {
				continue;
			}
			$ids[] = (int) $relation->target_id;
		}

		// An empty post__in is ignored by WP_Query, which would silently return every
		// post - the opposite of what no related posts means. 0 matches nothing.
		$args['post__in'] = empty( $ids ) ? array( 0 ) : $ids;
		$args['orderby']  = 'post__in';
		unset( $args['order'] );

		return $args;
	}
}
