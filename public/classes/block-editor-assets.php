<?php

namespace ContentRelations;

defined( 'WPINC' ) || exit;

/**
 * Loads the compiled sidebar bundle into the block editor.
 *
 * Block editor only: the classic editor keeps its meta box. The bundle and its data are
 * enqueued through enqueue_block_editor_assets, so nothing here touches the front end or
 * a classic-editor screen.
 */
class BlockEditorAssets {

	const HANDLE = 'content-relations-block-editor';

	private Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
	}

	public function enqueue(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$dir   = $this->plugin->path . '/dist';
		$asset = $dir . '/block-editor.asset.php';

		// dist/ is built by the pipeline and is not in the repository. If it is missing
		// (a source checkout that was never built), there is simply no sidebar rather
		// than a fatal.
		if ( ! file_exists( $asset ) ) {
			return;
		}

		$meta = include $asset;

		wp_enqueue_script(
			self::HANDLE,
			$this->plugin->url . 'dist/block-editor.js',
			$meta['dependencies'],
			$meta['version'],
			true
		);

		wp_set_script_translations( self::HANDLE, 'ph-content-relations', $this->plugin->path . '/languages' );

		wp_localize_script( self::HANDLE, 'ContentRelationsEditor', array(
			'restNamespace' => RestEditor::NAMESPACE,
			'restField'     => RestEditor::FIELD,
		) );
	}
}
