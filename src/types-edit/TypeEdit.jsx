import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	Flex,
	FlexItem,
	Notice,
	Spinner,
	__experimentalHeading as Heading,
	__experimentalText as Text,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { chevronDown, chevronUp } from '@wordpress/icons';

const { restNamespace, i18n } = window.ContentRelationsTypeEdit || {};
const ns = restNamespace || 'content-relations/v1';

/**
 * The Tools edit screen for one relation type.
 *
 * The type's relations are grouped by source post, each group showing the post title and
 * its targets of this type. Only the order within a group is editable - weight is per
 * source post, so reordering a post's targets here is the same reordering the editor does
 * for that post, just gathered in one place. Saving sends each post's new target order to
 * the reorder endpoint, which rebuilds that post keeping its other types untouched.
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

	if ( groups.length === 0 ) {
		return (
			<Text isBlock variant="muted">
				{ i18n.no_relations }
			</Text>
		);
	}

	return (
		<Flex direction="column" gap="4" style={ { maxWidth: '640px' } }>
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

			{ groups.map( ( group, groupIndex ) => (
				<Card key={ group.source_id } size="small">
					<CardHeader>
						<Heading level={ 4 }>
							{ group.source_title || `#${ group.source_id }` }
						</Heading>
					</CardHeader>
					<CardBody>
						<Flex direction="column" gap="1">
							{ group.targets.map( ( target, index ) => (
								<Flex key={ target.target_id } align="center" gap="1">
									<FlexItem isBlock>
										<Text>{ target.title || `#${ target.target_id }` }</Text>
										{ target.post_status && 'publish' !== target.post_status && (
											<Text isBlock variant="muted" size="12">
												{ target.post_status }
											</Text>
										) }
									</FlexItem>
									<Button
										size="small"
										icon={ chevronUp }
										label={ i18n.move_up }
										disabled={ index === 0 }
										onClick={ () => move( groupIndex, index, index - 1 ) }
									/>
									<Button
										size="small"
										icon={ chevronDown }
										label={ i18n.move_down }
										disabled={ index === group.targets.length - 1 }
										onClick={ () => move( groupIndex, index, index + 1 ) }
									/>
								</Flex>
							) ) }
						</Flex>
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
