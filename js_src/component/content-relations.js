import React, {Component, PropTypes} from 'react';
import _ from 'underscore';

import Controls from './controls.js';
import List from './content-relations-list.js';

class ContentRelations extends Component{
	/**
	 * ---------------------
	 * lifecycle
	 * ---------------------
	 */
	constructor(props){
		super(props);
		
		/**
		 * collect types
		 * @type {Array}
		 */
		const types = [];
		for( let i = 0; i < props.contents.length; i++){
			if(!_.contains(types, props.contents[i].type)){
				types.push(props.contents[i].type);
			}
		}
		
		/**
		 * set initial state
		 */
		this.state = {
			types: types,
		};
	}
	/**
	 * ---------------------
	 * rendering
	 * ---------------------
	 */
	render(){
		const {events, contents} = this.props;
		const {types} = this.state;
		return (
			<div
				className="meta-box-content-relations clearfix"
			>
				<Controls
					events={events}
					types={types}
				/>
				<List
					types={types}
					contents={contents}
					events={events}
				/>
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

ContentRelations.propTypes = {
	events: PropTypes.object.isRequired,
	contents: PropTypes.array.isRequired,
};

export default ContentRelations;