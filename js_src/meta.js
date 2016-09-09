import React from 'react';
import ReactDOM from 'react-dom';
import {EventEmitter} from 'events';

import ContentRelations from './component/content-relations.js';

import Events from './constants/events.js';

(function($){
	"use strict";
	
	/**
	 * events for component
	 */
	const events = new EventEmitter();
	events.setMaxListeners(0);
	
	/**
	 * root for component
	 * @type {Element}
	 */
	const root = document.getElementById("react-content-relation-meta");
	
	/**
	 * render component
	 */
	ReactDOM.render(
		<ContentRelations
			events={events}
		    contents={window.ph_content_relations_initial}
		/>,
		root
	);
	
	/**
	 * for outside click
	 */
	$(document.body).on("click",function(e){
		if(!$.contains(root, e.target)){
			events.emit(Events.CLICK_OUTSIDE);
		}
	});
	
})(jQuery);
