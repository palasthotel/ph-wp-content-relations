import React, {Component, PropTypes} from 'react';
import _ from 'underscore';


class ContentRelationsList extends Component{
	/**
	 * ---------------------
	 * lifecycle
	 * ---------------------
	 */
	constructor(props){
		super(props);
		
		this.state = {
			types: props.types,
		};
	}
	/**
	 * ---------------------
	 * rendering
	 * ---------------------
	 */
	render(){
		return (
			<div
				className="content-relations-contents"
			>
				<ul
					className="content-relations-list"
				>
					<li
						className="content-relations-list-section"
						data-type="Related Article"
					>
						<div
							className="content-relations-section-title"
						>
							Related Article
						</div>
						<ul
							className="content-relations-list-relations"
						>
							<li
								className="ph-content-relation  content-relation-item"
								data-source-id="1120391"
								data-target-id="700109"
								data-type="Related Article"
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
										7 Fakten über fehlerhafte Songtexte - mit Fleetwood Mac, Pitbull und U2
									</a>
								</div>
								<div
									className="content-relation-infos"
								>
									ID 700109 - post - Donnerstag, Juni 12, 2014
								</div>
								<input
									name="ph-content-relations-type[]"
									type="hidden"
									value="Related Article"
								/>
								<input
									name="ph-content-relations-source-id[]"
									type="hidden"
									value="1120391"
								/>
								<input
									name="ph-content-relations-target-id[]"
									type="hidden"
									value="700109"
								/>
							</li>
						</ul>
					</li>
					<li
						className="content-relations-list-section"
						data-type="Lead Relation"
					>
						<div
							className="content-relations-section-title"
						>
							Lead Relation
						</div>
						<ul
							className="content-relations-list-relations"
						>
							<li
								className="ph-content-relation  content-relation-item"
								data-source-id="1120391"
								data-target-id="580579"
								data-type="Lead Relation"
							>
								<div
									className="content-relation-item-title"
								>
									<span className="dashicons dashicons-no relation-delete"/>
									<a
										target="_new"
										href="/wp-admin/post.php?post=580579&amp;action=edit"
									>
										Die 500 besten Songs aller Zeiten: Die komplette Liste
									</a>
								</div>
								<div
									className="content-relation-infos"
								>
									ID 580579 - post - Montag, August 18, 2014
								</div>
								<input
									name="ph-content-relations-type[]"
									type="hidden"
									value="Lead Relation"
								/>
								<input
									name="ph-content-relations-source-id[]"
									type="hidden"
									value="1120391"
								/>
								<input
									name="ph-content-relations-target-id[]"
									type="hidden"
									value="580579"
								/>
							</li>
						</ul>
					</li>
				</ul>
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

ContentRelationsList.propTypes = {
	contents: PropTypes.array.isRequired,
};

export default ContentRelationsList;