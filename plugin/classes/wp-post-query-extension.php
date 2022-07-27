<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 30.01.18
 * Time: 10:26
 */

namespace ContentRelations;


class WPPostQueryExtension {

	/**
	 * WPPostQueryExtension constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		add_filter('posts_where', array($this, 'posts_where'), 10 , 2);
	}

	/**
	 * @param string $where
	 * @param \WP_Query $query
	 *
	 * @return string
	 */
	function posts_where($where, $query){

		// no relation query parameter set
		if(!isset($query->query_vars[Plugin::WP_QUERY_ARG_RELATION]) || empty($query->query_vars[Plugin::WP_QUERY_ARG_RELATION])) return $where;

		$relation_param = $query->query_vars[Plugin::WP_QUERY_ARG_RELATION];
		// no valid element
		if(!is_array($relation_param) && !is_object($relation_param)) return $where;

		// get args from query
		$to   = null;
		$from = null;
		if ( isset( $relation_param[ Plugin::WP_QUERY_ARG_RELATED_WITH ] ) && $this->isOnlyNumbers($relation_param[ Plugin::WP_QUERY_ARG_RELATED_WITH ]) ) {
			$to   = intval( $relation_param[ Plugin::WP_QUERY_ARG_RELATED_WITH ] );
			$from = intval( $relation_param[ Plugin::WP_QUERY_ARG_RELATED_WITH ] );
		} else {
			if ( isset( $relation_param[ Plugin::WP_QUERY_ARG_RELATED_TO ] ) && $this->isOnlyNumbers($relation_param[ Plugin::WP_QUERY_ARG_RELATED_TO ]) ) {
				$to = intval( $relation_param[ Plugin::WP_QUERY_ARG_RELATED_TO ] );
			} else if ( isset( $relation_param[ Plugin::WP_QUERY_ARG_RELATED_FROM ] ) && $this->isOnlyNumbers($relation_param[ Plugin::WP_QUERY_ARG_RELATED_FROM ]) ) {
				$from = intval( $relation_param[ Plugin::WP_QUERY_ARG_RELATED_FROM ] );
			}
		}

		$types = array();
		if(isset($relation_param[Plugin::WP_QUERY_ARG_RELATED_TYPE])){
			$type = $relation_param[Plugin::WP_QUERY_ARG_RELATED_TYPE];
			if(is_string($type)){
				$types[] = sanitize_text_field($type);
			} else if(is_array($type)){
				foreach ($type as $t){
					$types[] = sanitize_text_field($t);
				}
			}
		}

		global $wpdb;
		$prefix = $wpdb->prefix;
		$conditions = array();

		if($to != null){
			$type_conditions = "";
			if(count($types) > 0){
				$tcs = array();
				foreach ($types as $type){
					$tcs[] = " ID IN ( SELECT source_id FROM {$prefix}content_relations as cr RIGHT JOIN {$prefix}content_relations_types as crt".
					         " ON cr.type_id = crt.id WHERE crt.type = '$type' ) ";
				}
				$type_conditions = " AND ( ".join(" OR ",$tcs)." ) ";
			}
			$conditions[] = " (ID IN ( SELECT source_id FROM {$prefix}content_relations WHERE target_id = $to ) $type_conditions) ";
		}
		if($from != null){
			$type_conditions = "";
			if(count($types) > 0){
				$tcs = array();
				foreach ($types as $type){
					$tcs[] = " ID IN ( SELECT target_id FROM {$prefix}content_relations as cr RIGHT JOIN {$prefix}content_relations_types as crt".
					         " ON cr.type_id = crt.id WHERE crt.type = '$type' ) ";
				}
				$type_conditions = " AND ( ".join(" OR ",$tcs)." ) ";
			}
			$conditions[] = " (ID IN ( SELECT target_id FROM {$prefix}content_relations WHERE source_id = $from ) $type_conditions) ";
		}

		if(count($conditions) > 0){
			$where.=" AND ( ".join(" OR ",$conditions)." )";
		}

		return $where;
	}

	private function isOnlyNumbers($var){
		if( is_integer( $var ) || preg_match( '/\\d/', $var ) === 1 ){
			return true;
		}

		return false;
	}
}