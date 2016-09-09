<?php

/**
 * HTML markup for content relations meta box in post editor
 *
 * @var WP_Post $post Post object
 * @var \ContentRelations\Required $required Content_Relations_Required object
 * @var array $required_array Array with boolean of required relation types
 */

/**
 * Add an nonce field so we can check for it later.
 */
wp_nonce_field( 'ph_meta_box_content_relations', 'ph_meta_box_content_relations_nonce' );

?>

<script>
	
	<?php
	$relations = $post->content_relations->get_relations();
	for ($i=0; $i < count($relations) ; $i++) {
		global $post;
		$target_pid = $relations[$i]->target_id;
		if($post->ID == $target_pid){
			$target_pid = $relations[$i]->source_id;
		}
		if($relations[$i]->post_type == "attachment"){
			$src = wp_get_attachment_image_src( $target_pid, 'thumbnail', false );
			$relations[$i]->src = $src[0];
		}
		$relations[$i]->pub_date = get_the_date('l, F j, Y', $target_pid);
	}
	?>
	window.ph_content_relations_initial = <?php echo json_encode($relations); ?>;
	
	
</script>

<div id="react-content-relation-meta"></div>
	
<?php

$relation_types = $post->content_relations->get_types();
$required_types= array();
foreach ($relation_types as $type) {
	if ( $required->get( $post->post_type, $type->id ) ) {
		$required_types[] = $type->type;
	}
}
if (!empty( $required_types )) {
	if (count($required_types) !== 1) : // pluralize ?>
		<div><small><?php echo implode(", ", $required_types)." sollten ausgefüllt sein"; ?></small></div>
	<?php else : ?>
		<div><small><?php echo implode(", ", $required_types)." sollte ausgefüllt sein"; ?></small></div>
	<?php endif; // endif pluralize
} // endif empty
?>
