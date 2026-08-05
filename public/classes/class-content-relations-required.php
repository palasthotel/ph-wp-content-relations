<?php

/**
 * Content_Relations_Required handles the duty
 *
 */
class Content_Relations_Required
{

	/**
	 * saves required to wp options
	 */
	public function set($values)
	{
		return update_site_option( 'content-relations-required', $values );
	}

	/**
	 * get required from wp options
	 * @param  $post_type  		if set only get options of post type
	 * @return  mixed 			array of required fields or just required by post_type or boolean if type is required by post type
	 */
	public function get($post_type = null, $content_relation_type_id = null)
	{
		$required = get_site_option( 'content-relations-required', array() );
		if ( $post_type != null ){
			if ( ! isset($required[ $this->get_required_name( $post_type ) ]) ) { return array(); }
			$required = $required[ $this->get_required_name( $post_type ) ];
			if ( $content_relation_type_id != null ){
				return $this->is( $required, $content_relation_type_id );
			}
		}
		return $required;
	}

	/**
	 * get value name of post type option key
	 * @param  string $post_type 	name of post type
	 * @return string            	name of option key
	 */
	public function get_required_name($post_type, $relation_type_id = null)
	{
		$key = 'content_relations_required_'.$post_type;
		if ( $relation_type_id != null ){
			$key .= '[id-'.$relation_type_id.']';
		}
		return $key;
	}

	/**
	 * checks if relation type is required
	 * @return  boolean
	 */
	public function is($required_array, $content_relations_type_id)
	{
		if ( isset($required_array[ 'id-'.$content_relations_type_id ]) ) {
			return true;
		}
		return false;
	}

}

?>