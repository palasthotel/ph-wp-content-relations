import React, {Component, PropTypes} from 'react';
import _ from 'underscore';

import TypeSelect from './type-select.js';
import PostAutocomplete from './post-autocomplete';

class Controls extends Component{
	/**
	 * ---------------------
	 * lifecycle
	 * ---------------------
	 */
	constructor(props){
		super(props);
	}
	/**
	 * ---------------------
	 * rendering
	 * ---------------------
	 */
	render(){
		const {events, types} = this.props;
		return (
			<div
				className="content-relations-controls clearfix"
			>
				<TypeSelect
					events={events}
					types={types}
				/>
				
				<div
					className="content-relation-title-widget"
				>
					<PostAutocomplete
						events={events}
					/>
					<button
						className="button"
					>
						Hinzufügen
					</button>
				</div>
				
			</div>
		)
	}
	/**
	 * ---------------------
	 * events
	 * ---------------------
	 */
	
	/**
	 * ---------------------
	 * other functions
	 * ---------------------
	 */
}

Controls.propTypes = {
	events: PropTypes.object.isRequired,
	types: PropTypes.array.isRequired,
};

export default Controls;