import { PluginDocumentSettingPanel } from '@wordpress/editor';
import {
	Button,
	ComboboxControl,
	Flex,
	FlexItem,
	Notice,
	SearchControl,
	Spinner,
	__experimentalText as Text,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, _x, sprintf } from '@wordpress/i18n';
import { chevronDown, chevronUp, closeSmall } from '@wordpress/icons';
import { fetchTypes, searchPosts } from './api';

const FIELD = ( window.ContentRelationsEditor || {} ).restField || 'content_relations_edit';

/**
 * Group a flat, ordered relation list by type, preserving both the order the types
 * first appear in and the order within each type. The field is stored flat and ordered
 * by weight, so grouping and flattening round-trip cleanly.
 *
 * @param {Array} relations
 * @return {Array<{type: string, items: Array}>} groups
 */
function groupByType( relations ) {
	const order = [];
	const byType = {};
	relations.forEach( ( relation ) => {
		if ( ! byType[ relation.type ] ) {
			byType[ relation.type ] = [];
			order.push( relation.type );
		}
		byType[ relation.type ].push( relation );
	} );
	return order.map( ( type ) => ( { type, items: byType[ type ] } ) );
}

/**
 * Flatten groups back to the stored list: each type's items stay contiguous and in
 * order, so a reload re-groups them the same way.
 *
 * @param {Array<{type: string, items: Array}>} groups
 * @return {Array} relations
 */
function flatten( groups ) {
	return groups.reduce( ( all, group ) => all.concat( group.items ), [] );
}

/**
 * The Content Relations sidebar panel.
 *
 * Relations are grouped by type and ordered within a type. A post can belong to more
 * than one type, so the target search only excludes the posts already related under the
 * type being added to. The assignment lives in the editor's state and is written with
 * the post, the way core saves a taxonomy.
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
	const setRelations = ( next ) => editPost( { [ FIELD ]: next } );
	const groups = useMemo( () => groupByType( relations ), [ relations ] );

	const [ types, setTypes ] = useState( [] );
	const [ showForm, setShowForm ] = useState( false );
	const [ typeValue, setTypeValue ] = useState( null );
	const [ typeFilter, setTypeFilter ] = useState( '' );
	const [ search, setSearch ] = useState( '' );
	const [ results, setResults ] = useState( [] );
	const [ searching, setSearching ] = useState( false );

	useEffect( () => {
		let cancelled = false;
		fetchTypes().then( ( t ) => ! cancelled && setTypes( t ) );
		return () => {
			cancelled = true;
		};
	}, [] );

	const chosenType = ( typeValue || typeFilter ).trim();

	// Only exclude posts already related under the chosen type, so the same post can be
	// added to a second type.
	const excludeIds = useMemo( () => {
		const inType = relations
			.filter( ( r ) => r.type === chosenType )
			.map( ( r ) => r.target_id );
		return [ postId, ...inType ];
	}, [ relations, chosenType, postId ] );

	useEffect( () => {
		const term = search.trim();
		if ( '' === term ) {
			setResults( [] );
			return undefined;
		}
		setSearching( true );
		const handle = setTimeout( () => {
			searchPosts( term, excludeIds ).then( ( r ) => {
				setResults( r );
				setSearching( false );
			} );
		}, 400 );
		return () => clearTimeout( handle );
	}, [ search, chosenType ] ); // eslint-disable-line react-hooks/exhaustive-deps

	const typeOptions = useMemo( () => {
		const options = types.map( ( t ) => ( { label: t, value: t } ) );
		const filter = typeFilter.trim();
		if ( filter && ! types.some( ( t ) => t.toLowerCase() === filter.toLowerCase() ) ) {
			options.unshift( {
				value: filter,
				/* translators: %s: the relation type name being created */
				label: sprintf( __( 'Create "%s"', 'ph-content-relations' ), filter ),
			} );
		}
		return options;
	}, [ types, typeFilter ] );

	const addRelation = ( post ) => {
		if ( '' === chosenType ) {
			return;
		}
		setRelations( [
			...relations,
			{
				target_id: post.target_id,
				type: chosenType,
				post_title: post.post_title,
				post_type: post.post_type,
				post_status: post.post_status,
			},
		] );
		setSearch( '' );
		setResults( [] );
	};

	// Reorder within one type group, then flatten back to the stored list.
	const moveInGroup = ( groupIndex, from, to ) => {
		const group = groups[ groupIndex ];
		if ( to < 0 || to >= group.items.length ) {
			return;
		}
		const items = [ ...group.items ];
		const [ moved ] = items.splice( from, 1 );
		items.splice( to, 0, moved );
		const nextGroups = groups.map( ( g, i ) => ( i === groupIndex ? { ...g, items } : g ) );
		setRelations( flatten( nextGroups ) );
	};

	const removeRelation = ( relation ) => {
		setRelations(
			relations.filter(
				( r ) => ! ( r.target_id === relation.target_id && r.type === relation.type )
			)
		);
	};

	return (
		<PluginDocumentSettingPanel
			name="content-relations"
			title={ __( 'Content relations', 'ph-content-relations' ) }
		>
			<Flex direction="column" gap="4">
				{ groups.length === 0 && (
					<Text isBlock variant="muted">
						{ __( 'No relations yet.', 'ph-content-relations' ) }
					</Text>
				) }

				{ groups.map( ( group, groupIndex ) => (
					<div key={ group.type }>
						<Text weight="600" upperCase size="11">
							{ group.type }
						</Text>
						<Flex direction="column" gap="1">
							{ group.items.map( ( relation, index ) => (
								<Flex
									key={ `${ relation.target_id }-${ index }` }
									align="center"
									gap="1"
								>
									<FlexItem isBlock>
										<Text>{ relation.post_title || `#${ relation.target_id }` }</Text>
										{ relation.post_status && 'publish' !== relation.post_status && (
											<Text isBlock variant="muted" size="12">
												{ relation.post_status }
											</Text>
										) }
									</FlexItem>
									<Button
										size="small"
										icon={ chevronUp }
										label={ __( 'Move up', 'ph-content-relations' ) }
										disabled={ index === 0 }
										onClick={ () => moveInGroup( groupIndex, index, index - 1 ) }
									/>
									<Button
										size="small"
										icon={ chevronDown }
										label={ __( 'Move down', 'ph-content-relations' ) }
										disabled={ index === group.items.length - 1 }
										onClick={ () => moveInGroup( groupIndex, index, index + 1 ) }
									/>
									<Button
										size="small"
										icon={ closeSmall }
										isDestructive
										label={ __( 'Remove', 'ph-content-relations' ) }
										onClick={ () => removeRelation( relation ) }
									/>
								</Flex>
							) ) }
						</Flex>
					</div>
				) ) }

				<FlexItem>
					<Button
						variant="link"
						aria-expanded={ showForm }
						onClick={ () => setShowForm( ( v ) => ! v ) }
					>
						{ __( 'Add relation', 'ph-content-relations' ) }
					</Button>
				</FlexItem>

				{ showForm && (
					<Flex direction="column" gap="3">
						<ComboboxControl
							__next40pxDefaultSize
							label={ __( 'Relation type', 'ph-content-relations' ) }
							help={ __( 'Pick a type or type a new name.', 'ph-content-relations' ) }
							value={ typeValue }
							options={ typeOptions }
							onChange={ setTypeValue }
							onFilterValueChange={ setTypeFilter }
						/>

						{ '' === chosenType ? (
							<Notice status="warning" isDismissible={ false }>
								{ __( 'Choose a relation type first.', 'ph-content-relations' ) }
							</Notice>
						) : (
							<SearchControl
								__nextHasNoMarginBottom
								label={ __( 'Add a related post', 'ph-content-relations' ) }
								placeholder={ __( 'Search posts…', 'ph-content-relations' ) }
								value={ search }
								onChange={ setSearch }
							/>
						) }

						{ searching && (
							<Flex justify="center">
								<Spinner />
							</Flex>
						) }

						{ ! searching && results.length > 0 && (
							<Flex direction="column" gap="1">
								{ results.map( ( post ) => (
									<Button
										key={ post.target_id }
										variant="tertiary"
										onClick={ () => addRelation( post ) }
									>
										{ post.post_title || `#${ post.target_id }` }
										{ post.post_status && 'publish' !== post.post_status
											? ` (${ post.post_status })`
											: '' }
									</Button>
								) ) }
							</Flex>
						) }

						{ ! searching && '' !== chosenType && '' !== search.trim() && results.length === 0 && (
							<Text isBlock variant="muted">
								{ _x( 'No posts found.', 'post search', 'ph-content-relations' ) }
							</Text>
						) }
					</Flex>
				) }
			</Flex>
		</PluginDocumentSettingPanel>
	);
}
