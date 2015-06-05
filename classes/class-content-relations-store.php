<?php

/**
 * Content_Relations class that holds the actual information
 *
 */
class Content_Relations_Store {

	/**
	 * the post ID
	 */
	private $post_ID;

	/**
	 * Content relations array
	 */
	private $content_relations;

	/**
	 * Relation types
	 */
	private $relation_types;

	/**
	 * contstruct with post
	 */
	public function __construct( $post_ID = null ) {
		$this->post_ID = $post_ID;
		$this->content_relations = null;
		$this->relation_types = null;
	}

	/**
	 * saves relations to post object
	 * @return  relations
	 */
	public function get_relations(){
		if ( $this->content_relations == null ){
			/**
			 * find all relations and save them to the post
			 */
			global $wpdb;

			// get relations where this post is source
			$query = 'SELECT source_id, target_id, type, weight, post_title, post_type '.
					'FROM '.$wpdb->prefix.'content_relations as relations '.
					'LEFT JOIN '.$wpdb->prefix.'content_relations_types as types ON relations.type_id=types.id '.
					'LEFT JOIN '.$wpdb->prefix.'posts as posts ON relations.target_id = posts.ID '.
					"WHERE source_id = '".$this->post_ID."';";
			$result = $wpdb->get_results( $query, OBJECT );

			// get relations where this post is target
			$query = 'SELECT source_id, target_id, type, weight, post_title, post_type '.
					'FROM '.$wpdb->prefix.'content_relations as relations '.
					'LEFT JOIN '.$wpdb->prefix.'content_relations_types as types ON relations.type_id=types.id '.
					'LEFT JOIN '.$wpdb->prefix.'posts as posts ON relations.source_id = posts.ID '.
					"WHERE target_id = '".$this->post_ID."' ;";
			$result = array_merge( $result, $wpdb->get_results( $query, OBJECT ) );
			/**
			 * Save result to class array
			 */
			$this->content_relations = $result;
		}
		return $this->content_relations;
	}

	/**
	 * get only relations of a spezifc type
	 */
	public function get_relations_by_type($type, $source_only = true)
	{
		$relations = array();
		foreach ($this->get_relations() as $relation)
		{
			if($source_only && $this->post_ID != $relation->source_id )
			{
				continue;
			}
			if( strtolower($relation->type) == strtolower($type) )
			{
				$relations[] = $relation;
			}
		}
		return $relations;
	}

	/**
	 * returns source_id that is actually the post id
	 */
	public function get_source_id(){
		return $this->post_ID;
	}

	/**
	 * get all relation types
	 * @return  relation_types
	 */
	public function get_types(){
		if ( $this->relation_types == null ){
			global $wpdb;
			$result = $wpdb->get_results( 'SELECT * FROM '.$wpdb->prefix.'content_relations_types', OBJECT );
			$this->relation_types = $result;
		}
		return $this->relation_types;
	}

	/**
	 * adds a type to database
	 */
	public function add_type($type){
		global $wpdb;
		// if there is no type given (or an empty string etc.) return and don't add it to the database
		if ( ! $type ) { return -1; }
		$wpdb->insert( $wpdb->prefix.'content_relations_types', array( 'type' => $type ), array( '%s' ) );
		return $wpdb->insert_id;
	}

	/**
	 * get a single type
	 */
	public function get_type_id($type){
		global $wpdb;
		return $wpdb->get_var( 'SELECT id FROM '.$wpdb->prefix."content_relations_types WHERE type='$type' LIMIT 1" );
	}

	/**
	 * deletes all relations with post id as source
	 * @param   targets  	boolean if target relations should be cleared too
	 * @return  numbers of rows that were deleted
	 */
	public function clear($target = false){
		global $wpdb;
		if ( $target ){
			$wpdb->delete( $wpdb->prefix.'content_relations', array( 'target_id' => $this->post_ID ) );
		}
		return $wpdb->delete( $wpdb->prefix.'content_relations', array( 'source_id' => $this->post_ID ) );
	}

	/**
	 * Updates the relations
	 * @param  [type] $arguments Array of source_id, target_id and Type-Name values
	 */
	public function update($data){
		global $wpdb;
		// clear before adding new relations
		$this->clear();
		// add new relation
		for ( $i = 0; $i < count( $data ); $i++ ){
			$this->add_relation( $data[ $i ]['source_id'], $data[ $i ]['target_id'], $data[ $i ]['type'] );
		}

	}

	/**
	 * Adds a single relation
	 */
	public function add_relation($source_id, $target_id, $type){
		global $wpdb;
		// if there are invalid posts given, don't add them.
		if ( intval( $source_id ) <= 0 || intval( $target_id ) <= 0 ) { return; }
		$type_id = $this->get_type_id( $type );
		if ( $type_id == null ){
			// add the type if it does not exist yet
			$type_id = $this->add_type( $type );
			// if the type was empty, add_type returns -1.
			// catch this and return without creating the relation
			if ( $type_id === -1 ) { return; }
		}
		return $wpdb->replace(
			$wpdb->prefix.'content_relations',
			array( 'source_id' => $source_id, 'target_id' => $target_id, 'type_id' => $type_id )
		);
	}

	/**
	 * delete relation type and all relations
	 */
	public function delete_type($type_id){
		global $wpdb;
		$deleted = $wpdb->delete($wpdb->prefix.'content_relations', array('type_id' => $type_id), array('%d'));
		$wpdb->delete($wpdb->prefix.'content_relations_types', array('id' => $type_id), array('%d'));
		return $deleted;
	}

	/**
	 * get count of relations by type
	 */
	public function get_relations_count_by_type($type_id){
		global $wpdb;
		return $wpdb->get_var("SELECT count(id) FROM ".$wpdb->prefix."content_relations WHERE type_id = ".$type_id);
	}

}
?>