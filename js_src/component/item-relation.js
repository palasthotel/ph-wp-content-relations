import React, {Component, PropTypes} from 'react';
import _ from 'underscore';


class ItemRelation extends Component{
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
		const {type,post_title,post_type,pub_date,source_id,target_id} = this.props;
		return (
			<li
				className="ph-content-relation content-relation-item"
			>
				<div
					className="content-relation-item-title"
				>
							<span
								className="dashicons dashicons-no relation-delete"
							/>
					<a
						target="_new"
						href="/wp-admin/post.php?post=700109&amp;action=edit"
					>
						{post_title}
					</a>
				</div>
				<div
					className="content-relation-infos"
				>
					ID {source_id} - {post_type} - {pub_date}
				</div>
				<input
					name="ph-content-relations-type[]"
					type="hidden"
					value={type}
				/>
				<input
					name="ph-content-relations-source-id[]"
					type="hidden"
					value={source_id}
				/>
				<input
					name="ph-content-relations-target-id[]"
					type="hidden"
					value={target_id}
				/>
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

ContentRelationsList.propTypes = {
	post_title: PropTypes.string.isRequired,
	post_type: PropTypes.string.isRequired,
	pub_date: PropTypes.string.isRequired,
	type: PropTypes.string.isRequired,
	source_id: PropTypes.string.isRequired,
	target_id: PropTypes.string.isRequired,
};

export default ContentRelationsList;