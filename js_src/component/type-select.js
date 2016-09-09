import React, {Component, PropTypes} from 'react';
import Events from '../constants/events.js';

class TypeSelect extends Component{
	/**
	 * ---------------------
	 * lifecycle
	 * ---------------------
	 */
	constructor(props){
		super(props);
		this.state = {
			active: false,
			types: props.types,
			new_label: "",
		};
		props.events.on(Events.CLICK_OUTSIDE, this.onClickOutside.bind(this));
	}
	/**
	 * ---------------------
	 * rendering
	 * ---------------------
	 */
	renderType(type){
		return (
			<li
				key={type}
				className="content-relation-type-item"
			>
				{type}
			</li>
		)
	}
	render(){
		const {active, new_label} = this.state;
		return (
			<div
				className={`content-relation-type-select-widget ${ (active)? "active": ""}`}
			>
				<div
					className="content-relation-type-title"
				    onClick={this.onClick.bind(this)}
				>
					<span className="content-relation-type-title-text">(Bitte auswählen)</span>
					<span className="dashicons dropdown-arrow dashicons-arrow-down" />
				</div>
				
				<div
					className="content-relation-type-dropdown"
				>
					<div
						className="content-relation-type-search"
					>
						<input
							type="text"
							placeholder="Typ suchen/erstellen"
							value={new_label}
						    onChange={this.onChange.bind(this)}
						/>
					</div>
					<ul
						className="content-relation-types"
					>
						<li
							className="content-relation-type-item content-relation-type-new"
						>
							»<span className="content-relation-type-new-title">{new_label}</span>« neue anlegen
						</li>
						{this.state.types.map((type)=> this.renderType(type))}
					</ul>
				</div>
			</div>
		)
	}
	/**
	 * ---------------------
	 * events
	 * ---------------------
	 */
	onChange(e){
		this.setState({new_label: e.target.value});
	}
	onClick(){
		this.setState({active: !this.state.active});
	}
	onClickOutside(){
		this.setState({active: false});
	}
	
	/**
	 * ---------------------
	 * other functions
	 * ---------------------
	 */
}

TypeSelect.propTypes = {
	types: PropTypes.array.isRequired,
	events: PropTypes.object.isRequired,
};

export default TypeSelect;