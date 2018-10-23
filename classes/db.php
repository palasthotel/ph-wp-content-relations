<?php

namespace ContentRelations\Db;

/**
 * @param $type_id
 * @return int
 */
function count_relations_by_type( $type_id ){
	global $wpdb;
	return intval($wpdb->get_var("SELECT count(id) FROM ".$wpdb->prefix."content_relations WHERE type_id = ".$type_id)) ;
}

/**
 * @param int $source_id source post id
 * @param int $target_id target post id
 * @param string $type relation type
 * @param int $weight for order purpose
 *
 * @return bool|false|int
 */
function add_relation($source_id, $target_id, $type, $weight = 0){
	global $wpdb;
	// if there are invalid posts given, don't add them.
	if ( intval( $source_id ) <= 0 || intval( $target_id ) <= 0 ) { return false; }
	$type_id = get_type_id( $type );
	if ( $type_id == null ){
		// add the type if it does not exist yet
		$type_id = $this->add_type( $type );
		// if the type was empty, add_type returns -1.
		// catch this and return without creating the relation
		if ( $type_id === -1 ) { return false; }
	}
	return $wpdb->replace(
		$wpdb->prefix.'content_relations',
		array(
			'source_id' => $source_id,
			'target_id' => $target_id,
			'type_id' => $type_id,
			'weight' => $weight
		)
	);
}

/**
 * get all relations with that post
 * @return array relations
 */
function get_relations($post_id){

	global $wpdb;

	// get relations where this post is source
	$query = 'SELECT source_id, target_id, type, weight, post_title, post_type '.
	         'FROM '.$wpdb->prefix.'content_relations as relations '.
	         'LEFT JOIN '.$wpdb->prefix.'content_relations_types as types ON relations.type_id=types.id '.
	         'LEFT JOIN '.$wpdb->prefix.'posts as posts ON relations.target_id = posts.ID '.
	         "WHERE source_id = '".$post_id." ORDER BY weight ASC';";
	$result = $wpdb->get_results( $query, OBJECT );

	// get relations where this post is target
	$query = 'SELECT source_id, target_id, type, weight, post_title, post_type '.
	         'FROM '.$wpdb->prefix.'content_relations as relations '.
	         'LEFT JOIN '.$wpdb->prefix.'content_relations_types as types ON relations.type_id=types.id '.
	         'LEFT JOIN '.$wpdb->prefix.'posts as posts ON relations.source_id = posts.ID '.
	         "WHERE target_id = '".$post_id."' ORDER BY weight ASC ;";
	$result = array_merge( $result, $wpdb->get_results( $query, OBJECT ) );
	/**
	 * Save result to class array
	 */
	usort($result, function($a, $b){
		if($a->weight == $b->weight) return 0;
		return ($a->weight < $b->weight) ? -1 : 1;
	});

	return $result;
}

/**
 * @param string $type
 *
 * @return int
 */
function add_type($type){
	global $wpdb;
	$wpdb->insert( $wpdb->prefix.'content_relations_types', array( 'type' => $type ), array( '%s' ) );
	return $wpdb->insert_id;
}

/**
 * delete all relations and type itself
 * @param $type_id
 *
 * @return false|int
 */
function delete_type($type_id){
	global $wpdb;
	$deleted = $wpdb->delete($wpdb->prefix.'content_relations', array('type_id' => $type_id), array('%d'));
	$wpdb->delete($wpdb->prefix.'content_relations_types', array('id' => $type_id), array('%d'));
	return $deleted;
}

/**
 * @param string $type
 *
 * @return null|string
 */
function get_type_id($type){
	global $wpdb;
	return $wpdb->get_var( 'SELECT id FROM '.$wpdb->prefix."content_relations_types WHERE type='$type' LIMIT 1" );
}

/**
 * get all relation types
 * @return  array relation_types
 */
function get_types(){
	global $wpdb;
	return $wpdb->get_results( 'SELECT * FROM '.$wpdb->prefix.'content_relations_types', OBJECT );
}

/**
 * @param int $post_id
 * @param bool $target clear relations where post id is target too?
 *
 * @return false|int
 */
function clear($post_id, $target = false){
	global $wpdb;
	if ( $target ){
		$wpdb->delete( $wpdb->prefix.'content_relations', array( 'target_id' => $post_id ) );
	}
	return $wpdb->delete( $wpdb->prefix.'content_relations', array( 'source_id' => $post_id ) );
}

/**
 * Delete all relations with post id as source and a given type
 *
 * @param $post_id
 * @param string $type
 * @param bool $target
 *
 * @return false|int
 */

function clearByType($post_id, $type, $target = false){
	global $wpdb;
	$type_id = get_type_id($type);
	if ( $target ){
		$wpdb->delete( $wpdb->prefix.'content_relations', array( 'target_id' => $post_id, 'type_id' => $type_id ) );
	}
	return $wpdb->delete( $wpdb->prefix.'content_relations', array( 'source_id' => $post_id, 'type_id' => $type_id ) );
}


/**
 * install database tables
 */
function install(){
	/**
	 * wpdb object for prefix
	 */
	global $wpdb;
	/**
	 * require upgrade.php for dbDelta function
	 */
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	/**
	 * Create content_relations_relations table
	 */
	dbDelta('CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.'content_relations` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `source_id` int(11) unsigned NOT NULL,
				  `target_id` int(11) unsigned NOT NULL,
				  `type_id` int(11) NOT NULL,
				  `weight` int(11) NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `item_key` (`source_id`,`target_id`, `type_id`),
				  KEY `source_id` (`source_id`),
				  KEY `target_id` (`target_id`),
				  KEY `type_id` (`type_id`)
				) DEFAULT CHARSET=utf8;');

	/**
	 * create content_relations_types table
	 */
	dbDelta( 'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix."content_relations_types` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `type` varchar(30) NOT NULL DEFAULT '',
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `type` (`type`)
				) DEFAULT CHARSET=utf8;");
}