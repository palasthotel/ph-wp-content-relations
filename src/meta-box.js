import { createRoot, useState } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import RelationsEditor from './shared/RelationsEditor.jsx';

/**
 * The same relations editor as the block editor sidebar, mounted into the classic
 * editor's meta box.
 *
 * The classic editor saves on form submit, not through the REST field, so instead of the
 * editor's state this keeps the relations in local state and mirrors them into the hidden
 * fields the existing save_post handler reads - the parallel type[]/source-id[]/
 * target-id[] arrays. Nothing in the PHP save path changes; only the UI does.
 */
function MetaBoxApp( { initial, postId } ) {
	const [ relations, setRelations ] = useState( initial );

	return (
		<>
			<RelationsEditor
				relations={ relations }
				postId={ postId }
				onChange={ setRelations }
			/>
			{ relations.map( ( relation, index ) => (
				// eslint-disable-next-line react/no-array-index-key
				<span key={ index }>
					<input type="hidden" name="ph-content-relations-type[]" value={ relation.type } />
					<input type="hidden" name="ph-content-relations-source-id[]" value={ postId } />
					<input type="hidden" name="ph-content-relations-target-id[]" value={ relation.target_id } />
				</span>
			) ) }
		</>
	);
}

domReady( () => {
	const root = document.getElementById( 'content-relations-metabox-root' );
	if ( ! root ) {
		return;
	}
	const data = window.ContentRelationsMetaBox || {};
	createRoot( root ).render(
		<MetaBoxApp initial={ data.relations || [] } postId={ data.postId || 0 } />
	);
} );
