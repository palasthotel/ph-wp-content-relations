import React, {Component, PropTypes} from 'react';
import _ from 'underscore';

class AutocompleteResults extends Component{
	/**
	 * ---------------------
	 * lifecycle
	 * ---------------------
	 */
	constructor(props){
		super(props);
		
		this.state = {
			active_type: null,
		};
		
	}
	/**
	 * ---------------------
	 * rendering
	 * ---------------------
	 */
	renderType(type){
		const active = (type==this.state.active_type);
		return (
			<div
				className={`ph-relation-post-type ${ (active) ? "active" : ""}`}
			    onClick={this.onTypeClick.bind(this, type, !active)}
			>
				{type}
			</div>
		)
	}
	renderTypes(){
		const types = Object.keys(this.props.items);
		let render = [];
		for(type in types){
			render.push(this.renderType(type))
		}
		return (
			<div className="ph-content-relation-autocomplete-types">
				{render}
			</div>
		)
	}
	renderItem(item){
		return (
			<li
				className="post-relation-item"
			>
				<div className="post-relation-title">
					{item.post_title}<br/>
					ID {item.ID} - {item.post_type} - {item.pub_date}
				</div>
			</li>
		)
	}
	renderItems(){
		let render = [];
		for( type in this.items){
			
		}
	}
	render(){
		console.log("autocomplete render");
		if(this.state.active_type == null && Object.keys(this.props.items.length).length > 0){
			this.state.active_type = Object.keys(this.props.items)[0];
		} else {
			this.state.active_type = null;
		}
		return (
			<div
				className="ph-content-relation-autocomplete"
			>
				{this.renderTypes.bind(this)}
				{this.renderItems.bind(this)}
			</div>
		)
	}
	/**
	 * ---------------------
	 * events
	 * ---------------------
	 */
	onTypeClick(type, state){
		this.state.types[type] = state;
		this.setState({types: this.state.types});
	}
	
	
	/**
	 * ---------------------
	 * other functions
	 * ---------------------
	 */
	
}
AutocompleteResults.propTypes = {
	items: PropTypes.object.isRequired,
};

class PostAutocomplete extends Component{
	/**
	 * ---------------------
	 * lifecycle
	 * ---------------------
	 */
	constructor(props){
		super(props);
		this.state = {
			q: "",
			items: {},
			timeout: null,
		}
	}
	/**
	 * ---------------------
	 * rendering
	 * ---------------------
	 */
	render(){
		const {events, types} = this.props;
		const {q, items} = this.state;
		console.log(items);
		return (
			<div>
				<input
					type="text"
					placeholder="Titel oder ID"
					value={q}
					onChange={this.onChange.bind(this)}
					name="ph-content-relation-title"
				/>
				<AutocompleteResults
					items={items}
				/>
			</div>
		)
	}
	/**
	 * ---------------------
	 * events
	 * ---------------------
	 */
	onChange(e){
		const q = e.target.value;
		this.setState({q:q});
		
		if( q != "" ){
			this.request_autocomplete();
		} else {
			this.clear_items();
		}
	}
	onAutocompleteResult(data){
		this.setState({ items:data.result });
	}
	
	/**
	 * ---------------------
	 * other functions
	 * ---------------------
	 */
	request_autocomplete(){
		console.log("request ");
		clearTimeout(this.state.timeout);
		this.state.timeout = setTimeout(this.execute_request.bind(this),500);
	}
	execute_request(){
		console.log("execute "+this.state.q);
		jQuery.ajax({
			url: "/wp-admin/admin-ajax.php?action=ph_content_relations_title",
			dataType: "json",
			data: {
				q: this.state.q
			},
			success: this.onAutocompleteResult.bind(this),
		});
	}
	clear_items(){
		this.setState({items:{}});
	}
}

PostAutocomplete.propTypes = {
	events: PropTypes.object.isRequired,
};

export default PostAutocomplete;