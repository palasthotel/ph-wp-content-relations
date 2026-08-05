import apiFetch from '@wordpress/api-fetch';

const ns = ( window.ContentRelationsEditor || {} ).restNamespace || 'content-relations/v1';

/**
 * Existing relation type names.
 *
 * @return {Promise<string[]>} the type names
 */
export function fetchTypes() {
	return apiFetch( { path: `/${ ns }/types` } ).catch( () => [] );
}

/**
 * Search posts by title for the relation target picker.
 *
 * @param {string}   query   search term
 * @param {number[]} exclude post ids to leave out (the current post, already-related ones)
 * @return {Promise<Array>} matching posts as { target_id, post_title, post_type, post_status }
 */
export function searchPosts( query, exclude = [] ) {
	const params = new URLSearchParams();
	params.set( 'q', query );
	exclude.forEach( ( id ) => params.append( 'exclude[]', String( id ) ) );
	return apiFetch( { path: `/${ ns }/search?${ params.toString() }` } ).catch( () => [] );
}
