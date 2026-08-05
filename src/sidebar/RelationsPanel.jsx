import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useDispatch, useSelect } from '@wordpress/data';
import RelationsEditor from '../shared/RelationsEditor.jsx';

const { restField, i18n } = window.ContentRelationsEditor || {};
const FIELD = restField || 'content_relations_edit';

/**
 * The Content Relations panel in the block editor's document sidebar.
 *
 * A thin wrapper around the shared RelationsEditor: the relations live in the editor's
 * state and are written with the post through the content_relations_edit REST field, the
 * way core saves a taxonomy - no separate save button.
 */
export default function RelationsPanel() {
	const { saved, edited, postId } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		return {
			saved: editor.getCurrentPost()?.[ FIELD ],
			edited: editor.getPostEdits()?.[ FIELD ],
			postId: editor.getCurrentPostId(),
		};
	}, [] );
	const { editPost } = useDispatch( 'core/editor' );

	// An edit of [] is a real value and must win over the saved one, so the check is on
	// undefined rather than on falsiness.
	const relations = ( undefined !== edited ? edited : saved ) || [];

	return (
		<PluginDocumentSettingPanel
			name="content-relations"
			title={ i18n.panel_title }
		>
			<RelationsEditor
				relations={ relations }
				postId={ postId }
				onChange={ ( next ) => editPost( { [ FIELD ]: next } ) }
			/>
		</PluginDocumentSettingPanel>
	);
}
