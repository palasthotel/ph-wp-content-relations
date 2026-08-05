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
import { useEffect, useMemo, useState } from '@wordpress/element';
import { chevronDown, chevronUp, closeSmall } from '@wordpress/icons';
import { fetchTypes, searchPosts } from '../sidebar/api';

// Translated in PHP and passed in via wp_localize_script - see RelationsI18n::strings().
// No @wordpress/i18n here: it would need wp_set_script_translations() and a JED JSON file
// per bundle per locale, which ph-postqueue's editor does without.
const { i18n } = window.ContentRelationsEditor || {};

/**
 * Group a flat, ordered relation list by type, preserving the order the types first
 * appear in and the order within each type. The relations are stored flat and ordered
 * by weight, so grouping and flattening round-trip cleanly.
 *
 * @param {Array} relations
 * @return {Array<{type: string, items: Array}>} groups
 */
export function groupByType( relations ) {
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
export function flatten( groups ) {
	return groups.reduce( ( all, group ) => all.concat( group.items ), [] );
}

/**
 * The relations editor, shared by the block editor sidebar and the classic meta box.
 *
 * Controlled: the parent owns the relations and how they are persisted (editor state in
 * the sidebar, hidden form fields in the meta box). All this component does is show them
 * grouped by type and let the user add, reorder within a type, and remove.
 *
 * @param {Object}   props
 * @param {Array}    props.relations current relations, ordered
 * @param {number}   props.postId    the post being edited, excluded from the search
 * @param {Function} props.onChange  called with the next relations list
 */
export default function RelationsEditor( { relations, postId, onChange } ) {
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
				label: i18n.create_type_template.replace( '%s', filter ),
			} );
		}
		return options;
	}, [ types, typeFilter ] );

	const addRelation = ( post ) => {
		if ( '' === chosenType ) {
			return;
		}
		onChange( [
			...relations,
			{
				target_id: post.target_id,
				type: chosenType,
				post_title: post.post_title,
				post_type: post.post_type,
				post_type_label: post.post_type_label,
				post_status: post.post_status,
			},
		] );
		setSearch( '' );
		setResults( [] );
	};

	const moveInGroup = ( groupIndex, from, to ) => {
		const group = groups[ groupIndex ];
		if ( to < 0 || to >= group.items.length ) {
			return;
		}
		const items = [ ...group.items ];
		const [ moved ] = items.splice( from, 1 );
		items.splice( to, 0, moved );
		const nextGroups = groups.map( ( g, i ) => ( i === groupIndex ? { ...g, items } : g ) );
		onChange( flatten( nextGroups ) );
	};

	const removeRelation = ( relation ) => {
		onChange(
			relations.filter(
				( r ) => ! ( r.target_id === relation.target_id && r.type === relation.type )
			)
		);
	};

	return (
		<Flex direction="column" gap="4">
			{ groups.length === 0 && (
				<Text isBlock variant="muted">
					{ i18n.no_relations }
				</Text>
			) }

			{ groups.map( ( group, groupIndex ) => (
				<div key={ group.type }>
					<Text weight="600" upperCase size="11">
						{ group.type }
					</Text>
					<Flex direction="column" gap="1">
						{ group.items.map( ( relation, index ) => (
							<Flex key={ `${ relation.target_id }-${ index }` } align="center" gap="1">
								<FlexItem isBlock>
									<Flex justify="flex-start" gap="1" wrap>
										<Text>{ relation.post_title || `#${ relation.target_id }` }</Text>
										{ relation.post_type_label && (
											<Text variant="muted" size="12">
												{ relation.post_type_label }
											</Text>
										) }
									</Flex>
									{ relation.post_status && 'publish' !== relation.post_status && (
										<Text isBlock variant="muted" size="12">
											{ relation.post_status }
										</Text>
									) }
								</FlexItem>
								<Button
									size="small"
									icon={ chevronUp }
									label={ i18n.move_up }
									disabled={ index === 0 }
									onClick={ () => moveInGroup( groupIndex, index, index - 1 ) }
								/>
								<Button
									size="small"
									icon={ chevronDown }
									label={ i18n.move_down }
									disabled={ index === group.items.length - 1 }
									onClick={ () => moveInGroup( groupIndex, index, index + 1 ) }
								/>
								<Button
									size="small"
									icon={ closeSmall }
									isDestructive
									label={ i18n.remove }
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
					{ i18n.add_relation }
				</Button>
			</FlexItem>

			{ showForm && (
				<Flex direction="column" gap="3">
					<ComboboxControl
						__next40pxDefaultSize
						label={ i18n.relation_type }
						help={ i18n.relation_type_help }
						value={ typeValue }
						options={ typeOptions }
						onChange={ setTypeValue }
						onFilterValueChange={ setTypeFilter }
					/>

					{ '' === chosenType ? (
						<Notice status="warning" isDismissible={ false }>
							{ i18n.choose_type_first }
						</Notice>
					) : (
						<SearchControl
							__nextHasNoMarginBottom
							label={ i18n.add_related_post }
							placeholder={ i18n.search_posts }
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
									<Flex justify="flex-start" gap="1" wrap>
										<Text>{ post.post_title || `#${ post.target_id }` }</Text>
										{ post.post_type_label && (
											<Text variant="muted" size="12">
												{ post.post_type_label }
											</Text>
										) }
										{ post.post_status && 'publish' !== post.post_status && (
											<Text variant="muted" size="12">
												({ post.post_status })
											</Text>
										) }
									</Flex>
								</Button>
							) ) }
						</Flex>
					) }

					{ ! searching && '' !== chosenType && '' !== search.trim() && results.length === 0 && (
						<Text isBlock variant="muted">
							{ i18n.no_posts_found }
						</Text>
					) }
				</Flex>
			) }
		</Flex>
	);
}
