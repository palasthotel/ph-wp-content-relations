<?php

/**
 * The content relations meta box, classic editor only.
 *
 * The whole widget - a custom type dropdown, a custom title autocomplete and a
 * JavaScript-built list with custom order arrows - used to live here as markup. It is a
 * React mount now, the same editor the block editor sidebar uses, so both look and behave
 * like the rest of a modern WordPress screen. This template is just the mount point plus
 * the hidden nonce; the relations are handed to the script by wp_localize_script, and the
 * component writes the hidden fields save_post_meta_relations still reads.
 *
 * $post     Post object
 * $required Content_Relations_Required object
 */

defined( 'WPINC' ) || exit;

wp_nonce_field( 'ph_meta_box_content_relations', 'ph_meta_box_content_relations_nonce' );

do_action( \ContentRelations\Plugin::ACTION_META_BOX_LIST_BEFORE, $post );
?>
<div id="content-relations-metabox-root" class="content-relations-metabox"></div>
<?php
do_action( \ContentRelations\Plugin::ACTION_META_BOX_LIST_AFTER, $post );

// Which types a post of this type ought to have. Kept from the old template, shown below
// the editor as a hint.
$required_types = array();
foreach ( $post->content_relations->get_types() as $type ) {
	if ( $required->get( $post->post_type, $type->id ) ) {
		$required_types[] = $type->type;
	}
}
if ( ! empty( $required_types ) ) {
	printf(
		'<p class="description">%s</p>',
		esc_html( sprintf(
			/* translators: %s: comma-separated relation type names */
			_n(
				'%s should be filled in.',
				'%s should be filled in.',
				count( $required_types ),
				'ph-content-relations'
			),
			implode( ', ', $required_types )
		) )
	);
}
