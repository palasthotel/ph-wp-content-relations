<?php

/**
 * Content_Relations class that holds the actual information
 *
 */
use ContentRelations\Db;

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
	 * construct with post
	 *
	 * @param null $post_ID
	 */
	public function __construct( $post_ID = null ) {
		$this->post_ID = $post_ID;
		$this->content_relations = null;
		$this->relation_types = null;
	}

	/**
	 * saves relations to post object
	 * @return array relations
	 */
	public function get_relations(){
		if ( $this->content_relations == null )
			$this->content_relations = Db\get_relations($this->post_ID);
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
	 * @return  array relation_types
	 */
	public function get_types(){
		if ( $this->relation_types == null )
			$this->relation_types = Db\get_types();
		return $this->relation_types;
	}

	/**
	 * adds a type to database
	 *
	 * @param string $type
	 *
	 * @return int
	 */
	public function add_type($type){
		// if there is no type given (or an empty string etc.) return and don't add it to the database
		if ( ! $type ) { return -1; }
		return Db\add_type($type);
	}

	/**
	 * get a single type
	 */
	public function get_type_id($type){
		return Db\get_type_id($type);
	}

	/**
	 * Delete all relations with post id as source and a given type
	 *
	 * @param string $type
	 * @param int numbers of rows that were deleted
	 *
	 * @return false|int
	 */
	public function clearByType($type, $target = false){
		return Db\clearByType($this->post_ID, $type, $target);
	}


	/**
	 * deletes all relations with post id as source
	 * @param   boolean if target relations should be cleared too
	 * @return  int numbers of rows that were deleted
	 */
	public function clear($target = false){
		return Db\clear($this->post_ID, $target);
	}

	/**
	 * Updates the relations
	 * @param  [type] $arguments Array of source_id, target_id and Type-Name values
	 */
	public function update($data){
		// clear before adding new relations
		$this->clear();
		// add new relation
		for ( $i = 0; $i < count( $data ); $i++ ){
			$this->add_relation( $data[ $i ]['source_id'], $data[ $i ]['target_id'], $data[ $i ]['type'], $i );
		}
	}

	/**
	 * Adds a single relation
	 * @return false|int
	 */
	public function add_relation($source_id, $target_id, $type, $weight = 0){
		return Db\add_relation($source_id, $target_id, $type, $weight);
	}

	/**
	 * delete relation type and all relations
	 */
	public function delete_type($type_id){
		return Db\delete_type($type_id);
	}

	/**
	 * get count of relations by type
	 */
	public function get_relations_count_by_type($type_id){
		return Db\count_relations_by_type($type_id);
	}

}
?>