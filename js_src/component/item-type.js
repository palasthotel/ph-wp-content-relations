import React, {Component, PropTypes} from 'react';
import _ from 'underscore';

import ItemRelation from './item-relation.js';


class ItemType extends Component{
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
		const {items} = this.props;
		return (
			<li
				className="content-relations-list-section"
			>
				<div
					className="content-relations-section-title"
				>
					{this.props.type}
				</div>
				<ul
					className="content-relations-list-relations"
				>
					{this.props.children}
				</ul>
			</li>
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

ItemType.propTypes = {
	type: PropTypes.string.isRequired,
};

export default ItemType;