<?php

namespace ContentRelations;

use Content_Relations_Store;

class Grid {
	public function __construct( Plugin $plugin ) {
		add_filter( 'grid_posts_box_content_structure', array( $this, "grid_box_content_structure" ) );
		add_filter( 'grid_posts_box_query_args', array( $this, "filter_query_args" ), 1, 2 );
	}

	/**
	 * add widgets to grid box content structure
	 *
	 * @param $cs
	 */
	public function grid_box_content_structure( $cs ) {
		// TODO add box widgets for choosing from/to/with and relation-type(s)
		$store    = new Content_Relations_Store();
		$types = $store->get_types();

		$selections = array(
			array( 'key' => '0', 'text' => __( '-- All --' ) )
		);
		foreach ( $types as $type ) {
			$selections[] = array(
				'key' => $type->type,
				'text' => $type->type,
			);
		}

		$cs[] = array(
			'key' => 'content_relation_type',
			'type' => 'select',
			'label' => __( 'Relation type' ),
			'selections' => $selections,
		);

		$cs[] = array(
			'key' => 'content_relation_direction',
			'type' => 'select',
			'label' => __( 'Relation direction' ),
			'selections' => array(
				array( 'key' => 'both', 'text' => __( 'Relations from and to this post' ) ),
				array( 'key' => 'from', 'text' => __( 'Relations added to this post to another' ) ),
				array( 'key' => 'to', 'text' => __( 'Relations pointing to this post' ) ),
			),
		);
		return $cs;
	}

	/**
	 * add paramaters to grid posts box query args
	 *
	 */
	public function filter_query_args( $args, $content ) {

		if ( isset( $content->content_relation_direction ) ) {
			global $post;
			$args['content_relations'] = array();
			if ( 'both' == $content->content_relation_direction ) {
				$args['content_relations']['with'] = $post->ID;
			} elseif ( 'from' == $content->content_relation_direction ) {
				$args['content_relations']['from'] = $post->ID;
			} elseif ( 'to' == $content->content_relation_direction ) {
				$args['content_relations']['to'] = $post->ID;
			}
			if ( isset( $content->content_relation_type ) && 0 != $content->content_relation_type ) {
				$args['content_relations']['type'] = $content->content_relation_type;
			}
		}

		return $args;
	}
}
