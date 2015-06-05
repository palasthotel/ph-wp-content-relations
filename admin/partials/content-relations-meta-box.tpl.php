<?php

/**
 * HTML markup for content relations meta box in post editor
 *
 * $post 				Post object
 * $required 			Content_Relations_Required object
 * $required_array 		Array with boolean of required relation types
 */

?>
<div class="meta-box-content-relations clearfix">
	

	<div class="content-relations-controls clearfix">
		<?php
		/**
		 * Add an nonce field so we can check for it later.
		 */
		wp_nonce_field( 'ph_meta_box_content_relations', 'ph_meta_box_content_relations_nonce' );
		?>

		<!-- <span class="dashicons dashicons-sort"></span>  -->
		<!-- content relation type widget -->
		<div class="content-relation-type-select-widget" data-type-name="">

			<div class="content-relation-type-title">
				<span class="content-relation-type-title-text">(Bitte auswählen)</span>
				<span class="dashicons dashicons-arrow-down dropdown-arrow"></span>
			</div>

			<div class="content-relation-type-dropdown">
				<div class="content-relation-type-search">
					<input type="text" id="ph-content-relation-type-input" placeholder="Typ suchen/erstellen"  />
				</div>

				<ul class="content-relation-types">
					<li data-value="new" class="content-relation-type-item content-relation-type-new">
						&raquo;<span class="content-relation-type-new-title"></span>&laquo; neue anlegen
					</li>
					<?php 
					$relation_types = $post->content_relations->get_types();
					$required_types= array();
					foreach ($relation_types as $type) {

						if( $required->get($post->post_type, $type->id) )
						{
							$required_types[] = $type->type;
						}
						?><li class="content-relation-type-item" data-value="<?php echo $type->id; ?>"><?php echo $type->type; ?></li><?php
					}
					?>
				</ul>
			</div>
		</div>
		<!-- ENDE content relation type widget -->
		
		<!-- content relation title widget -->
		<div class="content-relation-title-widget" data-id="" data-title="">
			<input type="text" id="ph-content-relation-title" placeholder="Titel oder ID" name="ph-content-relation-title" />
			<div class="ph-content-relation-autocomplete"></div>
			<button id="content-relations-add-relation-btn" class="button">Hinzufügen</button>
		</div>
		<!-- ENDE content relation title widget -->
		
		
	
	</div>
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
	
	<div class="content-relations-contents">
		<ul class="content-relations-list"></ul>
	</div>

	<?php
	if (!empty( $required_types )) { 
		if (count($required_types) !== 1) : // pluralize ?>
			<div><small><?php echo implode(", ", $required_types)." sollten ausgefüllt sein"; ?></small></div>
		<?php else : ?>
			<div><small><?php echo implode(", ", $required_types)." sollte ausgefüllt sein"; ?></small></div>
		<?php endif; // endif pluralize
	} // endif empty ?>
	

</div>