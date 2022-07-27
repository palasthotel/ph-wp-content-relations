<?php

/**
 * @param $post_id
 *
 * @return \Content_Relations_Store
 */
function content_relations_get_store($post_id){
	return new Content_Relations_Store($post_id);
}

/**
 * Adds a new relation
 * @param $post_id_source WP_Post ID
 * @param $post_id_target WP_Post ID
 * @param $relation_type string type
 * @return false|int
 *
 * @deprecated please use content_relations_get_store and use the store functions
 *
 */
function content_relations_add_relation($post_id_source, $post_id_target, $relation_type){
	$store = content_relations_get_store($post_id_source);
	return $store->add_relation($post_id_source, $post_id_target, $relation_type);
}

/**
 * @param $post_id_source
 * @param $post_id_target
 * @param $relation_type
 *
 * @return false|int
 *
 * @deprecated please use content_relations_get_store and use the store functions
 *
 */
function ph_content_relations_add_relation($post_id_source, $post_id_target, $relation_type){
	return content_relations_add_relation($post_id_source, $post_id_target, $relation_type);
}

/**
 * All relations by post id
 * @param $post_id
 * @return array relations
 *
 * @deprecated please use content_relations_get_store and use the store functions
 *
 */
function content_relations_get_relations_by_post_id($post_id){
	$store = content_relations_get_store($post_id);
	return $store->get_relations();
}

/**
 * @param $post_id
 *
 * @return array
 *
 * @deprecated please use content_relations_get_store and use the store functions
 *
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
 *
 * @deprecated please use content_relations_get_store and use the store functions
 */
function content_relations_get_relations_by_post_id_and_type($post_id, $relation_type, $source_only = true){
	$store = content_relations_get_store($post_id);
	return $store->get_relations_by_type($relation_type, $source_only);
}

/**
 * @param $post_id
 * @param $relation_type
 * @param bool $source_only
 *
 * @return array
 *
 * @deprecated please use content_relations_get_store and use the store functions
 */
function ph_content_relations_get_relations_by_post_id_and_type($post_id, $relation_type, $source_only = true){
	return content_relations_get_relations_by_post_id_and_type($post_id, $relation_type, $source_only);
}

/**
 * Delete relations by post id and type
 *
 * @param int $post_id
 * @param string $relation_type
 *
 * @return false|int
 *
 * @deprecated please use content_relations_get_store and use the store functions
 */
function content_relations_delete_relations_by_type($post_id, $relation_type){
	$store = content_relations_get_store($post_id);
	return $store->clearByType($relation_type);
}

/**
 * Delete relations by post id and type
 *
 * @param int $post_id
 * @param string $relation_type
 *
 * @return false|int
 *
 * @deprecated please use content_relations_get_store and use the store functions
 */
function ph_content_relations_delete_relations_by_type($post_id, $relation_type){
	return content_relations_delete_relations_by_type($post_id, $relation_type);
}
