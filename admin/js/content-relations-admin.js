/**
 * Javascript for post editor content relations box
 */
 (function( $ ) {
 	'use strict';
	/**
	 * Start after dom is ready
	 */
	 $(function() {
	 	var ContentRelations = function(){
		 	/**
		 	 * get the post ID
		 	 */
		 	var ID = $("#post_ID").val();

		 	/**
		 	 * content relation type widget elements
		 	 */
		 	 var $type_widget = $(".content-relation-type-select-widget");
		 	 var $type_input = $("#ph-content-relation-type-input");
		 	 var $type_title = $type_widget.find(".content-relation-type-title-text");
		 	 var $type_drowdown_arrow = $type_widget.find(".dropdown-arrow");
		 	 var $type_list = $type_widget.find(".content-relation-types");
		 	 var $type_new_item = $type_list.find(".content-relation-type-new");
		 	 var $type_new_item_title = $type_list.find(".content-relation-type-new-title");

		 	/**
		 	 * title click listener that opens select widget
		 	 */
		 	 $type_widget.on("click", ".content-relation-type-title", function(){
		 	 	var $this = $(this);
		 	 	$type_widget.addClass("active");
		 	 	$type_input.val("");
		 	 	$type_input.focus();
		 	 	$type_drowdown_arrow.removeClass("dashicons-arrow-down").addClass("dashicons-arrow-up");
		 	 });
		 	/**
		 	 * type input blur listener that closes widget
		 	 */
		 	 $type_input.on("blur", function(){
		 	 	var $this = $(this);
		 	 	$type_widget.removeClass("active");
		 	 	$type_drowdown_arrow.removeClass("dashicons-arrow-up").addClass("dashicons-arrow-down");
		 	 	$type_list.find(".content-relation-type-item.selected").removeClass("selected");
		 	 });
		 	/**
		 	 * key listener for filtering and new item preview
		 	 */
		 	 $type_input.on("keyup",function(e){
		 	 	var $this = $(this);
		 	 	var $selected = $type_list.find(".content-relation-type-item.selected");
		 	 	$selected.removeClass("selected");
		 	 	switch(e.keyCode){
		 	 		case 27:
		 			// esc
		 				$type_input.blur();
		 			break;
		 			case 13:
		 			// enter
		 				if($selected.length > 0){
		 					$selected.trigger("mousedown");
		 					$relation_title.focus();
		 				}
		 			break;
		 			case 38:
		 			// up
		 				var $prev = $selected.prev();
		 				if($selected.length > 0 && $prev.length > 0){
		 					while($prev.hasClass("hide") && $prev.length > 0){
			 					$prev = $prev.prev();
			 				}
		 				} 
		 				if($prev.length > 0){
		 					$prev.addClass("selected");
		 				} else {
		 					var $candidate = $type_list.find(".content-relation-type-item").last();
		 					while($candidate.hasClass("hide") && $candidate.length > 0){
		 						$candidate = $candidate.prev();
		 					}
		 					$candidate.addClass("selected");
		 				}
		 			break;
		 			case 40:
		 			// down
		 				var $next = $selected.next();
		 				if($selected.length > 0 && $next.length > 0){
		 					while($next.hasClass("hide") && $next.length > 0){
			 					$next = $next.next();
			 				}
		 				}
		 				if($next.length > 0){
		 					$next.addClass("selected");
		 				} else {
		 					var $candidate = $type_list.find(".content-relation-type-item").first();
		 					while($candidate.hasClass("hide") && $candidate.length > 0){
		 						$candidate = $candidate.next();
		 					}
		 					$candidate.addClass("selected");
		 				}
		 			break;
		 			default:
			 			$type_new_item_title.text($this.val());
			 			filter_type($this.val());
		 			break;
		 		}	 		
		 	});

		 	function filter_type(text){
		 	 	$type_list.children().each(function(index, item){
		 	 		var $item = $(item);
		 	 		if($item.hasClass("content-relation-type-new")){
		 	 			return true;
		 	 		}
		 	 		if($item.text().toLowerCase().indexOf(text.toLowerCase())>-1){
		 	 			$item.removeClass("hide");
		 	 		} else {
		 	 			$item.addClass("hide");
		 	 		}
		 	 	});
		 	 }
		 	/**
		 	 * listens for mousedown on widget list item and selects item
		 	 */
		 	 $type_list.on("mousedown", ".content-relation-type-item", function(e){
		 	 	var $this = $(this);;
		 	 	if($this.hasClass("content-relation-type-new")){
		 	 		var new_title = $type_new_item_title.text();
		 	 		if(new_title.length < 1){
		 	 			alert("Bitte gib vorher einen Typ-Namen ein.");
		 	 			e.stopPropagation();
		 	 			return false;
		 	 		}
		 	 		$type_list.append('<li class="content-relation-type-item" data-value="">'+new_title+'</li>');
		 	 		$type_title.text(new_title);
		 	 		$type_new_item_title.text("");
		 	 	} else {
		 	 		$type_title.text($this.text());
		 	 	}
		 	 	$type_widget
		 	 	.attr("data-type-name", $type_title.text());
		 	 });

		 	 /**
		 	  * hover listener for up and down compatibility
		 	  */
		 	 $type_list.on("hover",".content-relation-type-item", function(){
		 	 	$type_list.find(".content-relation-type-item.selected").removeClass("selected");
		 	 	$(this).addClass("selected");
		 	 });

		 	/**
		 	 * autocomplete for content relations meta box content title
		 	 */
			var $relation_widget = $(".content-relation-title-widget");
			var $relation_title =  $( "#ph-content-relation-title" );
			var $autocomplete_wrapper = $(".ph-content-relation-autocomplete");
			$autocomplete_wrapper.on("click", function(e){
				e.stopPropagation();
			});
			$(document).on("click", function(e){
				$autocomplete_wrapper.empty();
			});
			var _value = "";
			var active_type = null;
			var autocomplete_items = null;
			$relation_title.on("keyup", function(){
				if(_value == this.value) return;
				_value = this.value;
				clearTimeout(this.auto_timeout);
				this.auto_timeout = setTimeout(autocomplete_title, 600, this.value);
			});
			function autocomplete_title(title){
				if(title == ""){
					build_autocomplete([]);
					return;
				}
				$.ajax({
					url: "/wp-admin/admin-ajax.php?action=ph_content_relations_title",
					dataType: "json",
					data: {
						q: title
					},
					success: function( data ) {
						autocomplete_items = data.result;
						build_autocomplete( data.result );
					}
				});
			}
			function build_autocomplete(result){
				var $types = $("<div>").addClass("ph-content-relation-autocomplete-types");
				var $list = $("<ul>").addClass("ph-content-relation-autocomplete-list");
				jQuery.each(result,function(type, list){
					if(list.length > 0){
						$types.append("<div class='ph-relation-post-type post-type-"+type+"'>"+type+"</div>");
						build_autocomplete_items($list, list, type);
					}
				});
				$autocomplete_wrapper.empty();
				$autocomplete_wrapper.append($types).append($list);
				if(active_type != null){
					var $active_type = $types.find(".post-type-"+active_type);
					if($active_type.length > 0){
						$active_type.trigger("click");
						return;
					}
				}
				$types.children().first().trigger("click");
				
			}
			function build_autocomplete_items($list, items, type){
				jQuery.each(items, function(index, item){
					var classes = "post-relation-item";
			 	 	if(ID == item.ID)
			 	 	{
			 	 		return;
			 	 	}
			 	 	classes += " post-type-"+type;
			 	 	var img = "";
			 	 	if(typeof item.src != "undefined")
			 	 	{
			 	 		classes+= " post-relation-has-image";
			 	 		img = "<img class='post-relation-image' src='"+item.src[0]+"' />";
			 	 	}
			 	 	var $item = $( "<li class='"+classes+"'></li>" )
			 	 	.append( img+"<div class='post-relation-title'>" + item.post_title + 
			 	 		"<br>ID " + item.ID + " - "+type+" - "+item.pub_date+"</div>" );

			 	 	$item.attr("data-type", type).attr("data-index", index);

			 	 	$item.appendTo( $list );
				});
			}
			$autocomplete_wrapper.on("click", ".ph-relation-post-type", function(){
				var $aw = $autocomplete_wrapper;
				$aw.find(".ph-relation-post-type").removeClass("active");
				$(this).addClass("active");
				$aw.find(".post-relation-item").hide();
				active_type = $(this).text();
				$aw.find(".post-relation-item.post-type-"+active_type).show();
			});
			$autocomplete_wrapper.on("click", ".post-relation-item", function(){
				var item = autocomplete_items[$(this).attr("data-type")][$(this).attr("data-index")];
				if(ID == item.ID){
	 	 			alert("Relation kann nicht auf sich selbst verweisen.");
	 	 			return false;
	 	 		}
	 	 		$relation_title.val( item.post_title );
	 	 		$relation_widget.attr("data-title",item.post_title);
	 	 		$relation_widget.attr("data-id", item.ID);
	 	 		$relation_widget.attr("data-post-type", item.post_type);
	 	 		if(typeof item.src != "undefined"){
	 	 			$relation_widget.attr("data-src", item.src[0]);
	 	 		} else {
	 	 			$relation_widget.removeAttr("data-src");
	 	 		}
	 	 		
	 	 		$relation_widget.attr("data-pub-date", item.pub_date);
	 	 		if( $type_widget.attr("data-type-name") != ""){
					$relation_add.trigger("click");
	 	 		}
	 	 		$autocomplete_wrapper.empty();
			});

		 	 /**
		 	  * tab jump back to relation types
		 	  */
		 	$relation_title.on("keydown",function(e){
		 		if(e.keyCode == 9 && e.shiftKey){
		 			e.preventDefault();
		 			$type_title.trigger("click");
		 		}
		 	});

		 	 /**
		 	 * prevent submit on enter
		 	 */
		 	var no_submit = function(event){
		 	 	if(event.keyCode == 13) {
		 	 		event.preventDefault();
		 	 		return false;
		 	 	}
		 	 };
		 	 $type_input.keydown(no_submit);
		 	 $relation_title.keydown(no_submit);

		 	 /**
		 	  * add button listener
		 	  */
		 	var $relation_add = $("#content-relations-add-relation-btn");
		 	$relation_add.on("click", function(e){
		 	  	e.preventDefault();
		 	  	var type_name = $type_widget.attr("data-type-name");
		 	  	var post_id = $relation_widget.attr("data-id");
		 	  	var post_title = $relation_widget.attr("data-title");
		 	  	var post_type = $relation_widget.attr("data-post-type");
		 	  	var src = $relation_widget.attr("data-src");
		 	  	var pub_date = $relation_widget.attr("data-pub-date");
		 	  	if(type_name == ""){
		 	  		alert("Kein Typ ausgewählt");
		 	  		return;
		 	  	}
		 	  	if(post_id == "" || post_title == ""){
		 	  		alert("Kein Inhalt ausgewählt");
		 	  		return;
		 	  	}
		 	  	addRelation(post_id, post_title, type_name, pub_date, post_type, src);
		 	  	$relation_title.val("");
		 	  	$relation_title.focus();
		 	});

		 	/**
		 	 * content relations list elements
		 	 */
		 	var $list = $(".content-relations-list");

			/**
			* array of all relations with key "source_id-target-id"
			*/
			var relations = {};

		 	 /**
	 	 	 * adds relation to json
	 	 	 */
	 	 	 function addRelation(target_id, post_title, type_name, pub_date, post_type, src){
	 	 	 	relations[ID+"-"+target_id+"-"+type_name] = {
	 	 	 		source_id: ID, 
	 	 	 		target_id: target_id, 
	 	 	 		post_title: post_title, 
	 	 	 		type: type_name, 
	 	 	 		post_type: post_type, 
	 	 	 		src: src ,
	 	 	 		pub_date: pub_date,
	 	 	 	};
	 	 	 	renderRelations();
	 	 	 }

	 	 	 /**
	 	 	  * renders relation json to list
	 	 	  */
			function renderRelations(){

				$list.empty();
				var relations_by_type = {};

				for(var key in relations){
					// if section does not already exist
					if(!(relations[key].type in relations_by_type )){
						relations_by_type[relations[key].type] = [];
					}
					relations_by_type[relations[key].type].push(relations[key]);
				}
				for(var type in relations_by_type){
					var $section = renderListTypeSection(type);
					var $relations = $section.find(".content-relations-list-relations");
					for (var i = 0; i < relations_by_type[type].length; i++) {
						var relation = relations_by_type[type][i];
						$relations.append(renderListItem( type, relation.source_id, relation.target_id, relation.post_title, relation.pub_date, relation.post_type, relation.src ));
					};
					$list.append($section);
				}
			}

			/**
			 * renders relations list type section
			 */
			function renderListTypeSection(type){
				var $section = $("<li></li>")
					.addClass("content-relations-list-section")
					.attr("data-type", type);
				var $title = $("<div>"+type+"</div>").addClass("content-relations-section-title");
				var $relations_list = $("<ul></ul>").addClass("content-relations-list-relations");
				return $section.append($title).append($relations_list);
			}

			/**
			 * renders relations list item
			 */
			function renderListItem(type, source_id, target_id, post_title, pub_date, post_type, src){
				var classes = "ph-content-relation ";
				var $field_type = $("<input />")
					.attr("name", "ph-content-relations-type[]")
					.attr("type", "hidden")
					.val(type);

				var $field_soruce_id = $("<input />")
					.attr("name", "ph-content-relations-source-id[]")
					.attr("type", "hidden")
					.val(source_id);

				var $field_target_id = $("<input />")
					.attr("name", "ph-content-relations-target-id[]")
					.attr("type", "hidden")
					.val(target_id);

				var icon_type = "dashicons-no relation-delete";
				
				var display_id = target_id;
				if(ID == target_id){
					display_id = source_id;
					icon_type = "dashicons-external relation-external";
				}
				var link = "<a target='_new' href='/wp-admin/post.php?post="+display_id+"&action=edit'>"+post_title+"</a>";
				var icon = "<span class='dashicons "+icon_type+"'></span> ";
				var image = "";
				if(post_type == "attachment"){
					classes+= "ph-content-relation-has-image ";
					image = "<img class='ph-relation-image' src='"+src+"' />";
				}
				var $display = $("<div class='content-relation-item-title'>"
								+icon
								+link
								+"</div>");
				var $infos = $("<div class='content-relation-infos'>"
								+"ID " + display_id + " - " + post_type + " - " + pub_date
								+"</div>");

				var $item = $("<li class='"+classes+"' ></li>")
					.addClass("content-relation-item")
					.attr("data-source-id", source_id)
					.attr("data-target-id", target_id)
					.attr("data-type", type)
					.append($display)
					.append($infos);
				if(image != ""){
					$item.append(image);
				}			
				$item.append($field_type)
					.append($field_soruce_id)
					.append($field_target_id);
				return $item;
			}

			/**
			 * eventhandler for delete relation
			 */
			$list.on("click", ".relation-delete", function(e){
				var $element = $(this).closest(".content-relation-item");
				var _ID = $element.attr("data-source-id");
				var _target_id = $element.attr("data-target-id");
				var _type = $element.attr('data-type');
				delete relations[_ID+"-"+_target_id+"-"+_type];
				renderRelations();
			});

		 	/**
		 	* Init relations that are already saved
		 	*/
		 	function initRelationsList(){
		 		var init_relations = window.ph_content_relations_initial;
		 		for (var i = init_relations.length - 1; i >= 0; i--) {
		 			var src = null;
		 			if(typeof init_relations[i].src != "undefined"){
		 				src = init_relations[i].src;
		 			} 
		 			relations[init_relations[i].source_id+"-"+init_relations[i].target_id+"-"+init_relations[i].type] = {
		 				source_id: init_relations[i].source_id, 
		 				target_id: init_relations[i].target_id, 
		 				post_title: init_relations[i].post_title, 
		 				type: init_relations[i].type,
		 				post_type: init_relations[i].post_type,
		 				src: src,
		 				pub_date: init_relations[i].pub_date,
		 			};
		 		};
		 		renderRelations();
		 	}
		 	initRelationsList();
		 	/**
		 	 * global function to use in third party scripts
		 	 */
		 	this.add_relation = function(post_id, post_title, type_name, pub_date, post_type, src){
		 		addRelation(post_id, post_title, type_name, pub_date, post_type, src);
		 	}
	 	}
	 	// to global object
	 	window.content_relations = new ContentRelations();

	 });

	

})( jQuery );
