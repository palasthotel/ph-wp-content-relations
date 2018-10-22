<?php

/**
 * Adds a new relation
 * @param $post_id_source WP_Post ID
 * @param $post_id_target WP_Post ID
 * @param $relation_type string type
 * @return false|int
 */
function content_relations_add_relation($post_id_source, $post_id_target, $relation_type){
	$store = new Content_Relations_Store($post_id_source);
	return $store->add_relation($post_id_source, $post_id_target, $relation_type);
}

/**
 * @param $post_id_source
 * @param $post_id_target
 * @param $relation_type
 *
 * @return false|int
 * @deprecated will be remove with version 2
 */
function ph_content_relations_add_relation($post_id_source, $post_id_target, $relation_type){
	return content_relations_add_relation($post_id_source, $post_id_target, $relation_type);
}

/**
 * All relations by post id
 * @param $post_id
 * @return array relations
 */
function content_relations_get_relations_by_post_id($post_id){
	$store = new Content_Relations_Store($post_id);
	return $store->get_relations();
}

/**
 * @param $post_id
 *
 * @return array
 * @deprecated will be removed with version 2
 */
function ph_content_relations_get_relations_by_post_id($post_id){
	return content_relations_get_relations_by_post_id($post_id);
}

/**
 * All relations by post id and type. Optional you can get both directions and not only relations where post is source
 * @param $post_id
 * @param $relation_type
 * @param bool|true $source_only
 * @return array
 */
function content_relations_get_relations_by_post_id_and_type($post_id, $relation_type, $source_only = true){
	$store = new Content_Relations_Store($post_id);
	return $store->get_relations_by_type($relation_type, $source_only);
}

/**
 * @param $post_id
 * @param $relation_type
 * @param bool $source_only
 *
 * @return array
 * @deprecated will be removed with version 2
 */
function ph_content_relations_get_relations_by_post_id_and_type($post_id, $relation_type, $source_only = true){
	return content_relations_get_relations_by_post_id_and_type($post_id, $relation_type, $source_only);
}