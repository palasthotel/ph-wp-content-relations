<?php

/**
 * Adds a new relation
 * @param $post_id_source WP_Post ID
 * @param $post_id_target WP_Post ID
 * @param $relation_type string type
 * @return false|int
 */
function ph_content_relations_add_relation($post_id_source, $post_id_target, $relation_type){
	$store = new Content_Relations_Store($post_id_source);
	return $store->add_relation($post_id_target, $relation_type);
}

/**
 * All relations by post id
 * @param $post_id
 * @return array relations
 */
function ph_content_relations_get_relations_by_post_id($post_id){
	$store = new Content_Relations_Store($post_id);
	return $store->get_relations();
}

/**
 * All relations by post id and type. Optional you can get both directions and not only relations where post is source
 * @param $post_id
 * @param $relation_type
 * @param bool|true $source_only
 * @return array
 */
function ph_content_relations_get_relations_by_post_id_and_type($post_id, $relation_type, $source_only = true){
	$store = new Content_Relations_Store($post_id);
	return $store->get_relations_by_type($relation_type, $source_only);
}