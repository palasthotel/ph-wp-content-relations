import { registerBlockVariation } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { fetchTypes } from '../sidebar/api';

const { i18n } = window.ContentRelationsEditor || {};

const TYPE_KEY = 'content_relations_type';
const SOURCE_KEY = 'content_relations_source';
const VARIATION = 'content-relations/related';

/**
 * A variation of core/query for a "related posts" section: the posts this post relates to
 * under one relation type, in the order the sidebar, meta box or the Tools edit screen
 * saved them in - the same core/query block used for any other loop, so it gets the same
 * pagination, layout and Post Template inner blocks.
 *
 * The relation type is written into the block's own query attribute; the source post is
 * always the post the block lives in, resolved on the front end from the post being
 * rendered (see QueryLoop::query_loop_block_query_vars() in PHP), never from anything
 * saved in the block. query.content_relations_source only carries the current post's id
 * for the editor preview's REST request, which otherwise has no post of its own to
 * resolve one from.
 */
registerBlockVariation( 'core/query', {
	name: VARIATION,
	title: i18n.variation,
	description: i18n.variation_desc,
	icon: 'admin-links',
	scope: [ 'inserter' ],
	isActive: ( blockAttributes ) => blockAttributes.namespace === VARIATION,
	attributes: {
		namespace: VARIATION,
		query: {
			perPage: 10,
			pages: 0,
			offset: 0,
			postType: 'post',
			order: 'desc',
			orderBy: 'date',
			author: '',
			search: '',
			exclude: [],
			sticky: '',
			inherit: false,
			[ TYPE_KEY ]: '',
			[ SOURCE_KEY ]: 0,
		},
	},
} );

const RelationTypeSelect = ( { attributes, setAttributes } ) => {
	const [ types, setTypes ] = useState( null );
	const postId = useSelect( ( select ) => select( 'core/editor' )?.getCurrentPostId(), [] );

	useEffect( () => {
		let cancelled = false;
		fetchTypes().then( ( result ) => ! cancelled && setTypes( result ) );
		return () => {
			cancelled = true;
		};
	}, [] );

	// The editor preview's REST request needs the post this block lives on; the front end
	// never reads this key (it always resolves the post being rendered instead), so
	// keeping it in sync only ever affects what the editor previews.
	useEffect( () => {
		if ( postId && attributes.query?.[ SOURCE_KEY ] !== postId ) {
			setAttributes( { query: { ...attributes.query, [ SOURCE_KEY ]: postId } } );
		}
	}, [ postId ] ); // eslint-disable-line react-hooks/exhaustive-deps

	if ( null === types ) {
		return <Spinner />;
	}

	const options = [
		{ label: i18n.select_none, value: '' },
		...types.map( ( type ) => ( { label: type, value: type } ) ),
	];

	return (
		<SelectControl
			label={ i18n.select_type }
			help={ i18n.select_type_help }
			value={ attributes.query?.[ TYPE_KEY ] || '' }
			options={ options }
			onChange={ ( value ) =>
				setAttributes( {
					query: { ...attributes.query, [ TYPE_KEY ]: value },
				} )
			}
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
};

const withRelationTypeControl = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		if ( 'core/query' !== props.name || VARIATION !== props.attributes?.namespace ) {
			return <BlockEdit { ...props } />;
		}
		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					<PanelBody title={ i18n.select_type } initialOpen>
						<RelationTypeSelect { ...props } />
					</PanelBody>
				</InspectorControls>
			</>
		);
	},
	'withContentRelationsTypeControl'
);

addFilter( 'editor.BlockEdit', 'content-relations/query-loop-control', withRelationTypeControl );
