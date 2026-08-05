import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import './styles/types-edit.scss';
import TypeEdit from './types-edit/TypeEdit.jsx';

domReady( () => {
	const root = document.getElementById( 'content-relations-type-edit-root' );
	if ( ! root ) {
		return;
	}
	const data = window.ContentRelationsTypeEdit || {};
	createRoot( root ).render(
		<TypeEdit
			type={ data.type || '' }
			initialGroups={ data.groups || [] }
		/>
	);
} );
