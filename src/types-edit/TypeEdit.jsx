import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	Flex,
	Notice,
	Spinner,
	__experimentalHeading as Heading,
	__experimentalText as Text,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { Icon, chevronDown, chevronUp, dragHandle } from '@wordpress/icons';

const { restNamespace, i18n } = window.ContentRelationsTypeEdit || {};
const ns = restNamespace || 'content-relations/v1';

/**
 * The Tools edit screen for one relation type.
 *
 * The type's relations are grouped by source post, each group showing the post title and
 * its targets of this type as the same table layout and drag behaviour as ph-postqueue's
 * QueueItems: a handle, the up/down buttons, then the title and a remove link. Only the
 * order within a group is editable, and dragging or moving a row only ever reorders
 * within its own group - weight is per source post, so reordering a post's targets here is
 * the same reordering the editor does for that post, just gathered in one place. Removing a
 * target here removes that one relation; the source post's other targets and types are
 * untouched. Saving sends each post's new (and possibly shorter) target list to the reorder
 * endpoint, which rebuilds that post keeping its other types untouched.
 */
export default function TypeEdit( { type, initialGroups } ) {
	// savedGroups is the last state confirmed written to the server - what Reset goes
	// back to. It starts as the initial load and moves forward on every successful save,
	// so resetting after a save discards only the edits made since, not the whole visit.
	const [ savedGroups, setSavedGroups ] = useState( initialGroups );
	const [ groups, setGroups ] = useState( initialGroups );
	const [ saving, setSaving ] = useState( false );
	const [ saved, setSaved ] = useState( false );
	const [ error, setError ] = useState( null );

	// Which row is being dragged and which row it is currently over, each a
	// { groupIndex, index } pair. Comparing groupIndex on drop is what keeps a drag from
	// ever moving a target into a different source post's group.
	const [ dragged, setDragged ] = useState( null );
	const [ over, setOver ] = useState( null );

	const dirty = groups !== savedGroups;

	const reset = () => {
		setGroups( savedGroups );
		setSaved( false );
		setError( null );
	};

	const move = ( groupIndex, from, to ) => {
		const group = groups[ groupIndex ];
		if ( to < 0 || to >= group.targets.length ) {
			return;
		}
		const targets = [ ...group.targets ];
		const [ moved ] = targets.splice( from, 1 );
		targets.splice( to, 0, moved );
		setGroups( groups.map( ( g, i ) => ( i === groupIndex ? { ...g, targets } : g ) ) );
		setSaved( false );
	};

	const remove = ( groupIndex, index ) => {
		const targets = groups[ groupIndex ].targets.filter( ( _, i ) => i !== index );
		setGroups( groups.map( ( g, i ) => ( i === groupIndex ? { ...g, targets } : g ) ) );
		setSaved( false );
	};

	const drop = ( groupIndex, toIndex ) => {
		if ( dragged && dragged.groupIndex === groupIndex && dragged.index !== toIndex ) {
			move( groupIndex, dragged.index, toIndex );
		}
		setDragged( null );
		setOver( null );
	};

	const save = () => {
		setSaving( true );
		setError( null );
		apiFetch( {
			path: `/${ ns }/reorder`,
			method: 'POST',
			data: {
				type,
				posts: groups.map( ( g ) => ( {
					source_id: g.source_id,
					target_ids: g.targets.map( ( t ) => t.target_id ),
				} ) ),
			},
		} )
			.then( () => {
				setSavedGroups( groups );
				setSaved( true );
			} )
			.catch( ( err ) => setError( err?.message || i18n.saving_failed ) )
			.finally( () => setSaving( false ) );
	};

	// Nothing was ever here - the empty state, no Save/Reset to offer.
	if ( groups.length === 0 ) {
		return (
			<Text isBlock variant="muted">
				{ i18n.no_relations }
			</Text>
		);
	}

	// A group a removal emptied has nothing left to show, but it stays in groups so save()
	// still sends its (now empty) target_ids and the removal takes effect on the server.
	// groups.length > 0 here, so unlike the empty state above, Save/Reset still render:
	// removing the last target is an edit like any other, undoable and worth saving.
	const visibleGroups = groups
		.map( ( group, groupIndex ) => ( { ...group, groupIndex } ) )
		.filter( ( group ) => group.targets.length > 0 );

	return (
		<Flex direction="column" gap="4" style={ { maxWidth: '760px' } }>
			{ error && (
				<Notice status="error" onRemove={ () => setError( null ) }>
					{ error }
				</Notice>
			) }
			{ saved && ! dirty && (
				<Notice status="success" onRemove={ () => setSaved( false ) }>
					{ i18n.order_saved }
				</Notice>
			) }

			{ visibleGroups.length === 0 && (
				<Text isBlock variant="muted">
					{ i18n.no_relations }
				</Text>
			) }

			{ visibleGroups.map( ( group ) => (
				<Card key={ group.source_id } size="small">
					<CardHeader>
						<Heading level={ 4 }>
							{ group.source_edit_link ? (
								<a href={ group.source_edit_link }>
									{ group.source_title || `#${ group.source_id }` }
								</a>
							) : (
								group.source_title || `#${ group.source_id }`
							) }
						</Heading>
					</CardHeader>
					<CardBody>
						<table className="wp-list-table widefat fixed striped content-relations-type-edit__items">
							<tbody>
								{ group.targets.map( ( target, index ) => {
									const isDragged =
										dragged?.groupIndex === group.groupIndex && dragged?.index === index;
									const isOver =
										over?.groupIndex === group.groupIndex &&
										over?.index === index &&
										! isDragged;
									return (
										<tr
											key={ target.target_id }
											draggable
											onDragStart={ ( event ) => {
												setDragged( { groupIndex: group.groupIndex, index } );
												event.dataTransfer.effectAllowed = 'move';
												// Firefox ignores a drag without any data set.
												event.dataTransfer.setData( 'text/plain', String( target.target_id ) );
											} }
											onDragOver={ ( event ) => {
												event.preventDefault();
												setOver( { groupIndex: group.groupIndex, index } );
											} }
											onDrop={ ( event ) => {
												event.preventDefault();
												drop( group.groupIndex, index );
											} }
											// A no-op safety net for a drag that ends without a valid drop
											// (dropped outside any row): passing the row's own index
											// as the target means drop() never treats it as a move,
											// only as cleanup. The actual move happens in onDrop,
											// fired on the row being dragged over.
											onDragEnd={ () => drop( group.groupIndex, index ) }
											className={ [
												isDragged ? 'is-dragging' : '',
												isOver ? 'is-drop-target' : '',
											]
												.join( ' ' )
												.trim() }
										>
											<td className="column-order">
												<div className="content-relations-type-edit__order">
													<span
														className="content-relations-type-edit__handle"
														aria-hidden="true"
														title={ i18n.drag_hint }
													>
														{ /* Through Icon, not as a bare element: the icons ship
														     without width or height and the SVG primitive adds
														     none, so a raw one collapses to nothing. Icon is
														     what clones it with a size. */ }
														<Icon icon={ dragHandle } size={ 20 } />
													</span>
													<Button
														size="small"
														icon={ chevronUp }
														label={ i18n.move_up }
														disabled={ index === 0 }
														onClick={ () => move( group.groupIndex, index, index - 1 ) }
													/>
													<Button
														size="small"
														icon={ chevronDown }
														label={ i18n.move_down }
														disabled={ index === group.targets.length - 1 }
														onClick={ () => move( group.groupIndex, index, index + 1 ) }
													/>
												</div>
											</td>
											<td className="column-primary">
												{ target.edit_link ? (
													<a href={ target.edit_link }>
														{ target.title || `#${ target.target_id }` }
													</a>
												) : (
													target.title || `#${ target.target_id }`
												) }
												{ target.post_status && 'publish' !== target.post_status && (
													<Text variant="muted"> ({ target.post_status })</Text>
												) }
											</td>
											<td className="column-actions">
												<Button
													variant="link"
													isDestructive
													onClick={ () => remove( group.groupIndex, index ) }
												>
													{ i18n.remove }
												</Button>
											</td>
										</tr>
									);
								} ) }
							</tbody>
						</table>
					</CardBody>
				</Card>
			) ) }

			<Flex justify="flex-start" gap="2">
				<Button variant="primary" isBusy={ saving } disabled={ saving || ! dirty } onClick={ save }>
					{ i18n.save_order }
				</Button>
				{ saving && <Spinner /> }
				<Button variant="tertiary" disabled={ saving || ! dirty } onClick={ reset }>
					{ i18n.reset }
				</Button>
			</Flex>
		</Flex>
	);
}
