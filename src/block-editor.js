import { registerPlugin } from '@wordpress/plugins';
import RelationsPanel from './sidebar/RelationsPanel.jsx';

registerPlugin( 'content-relations', {
	render: RelationsPanel,
} );
