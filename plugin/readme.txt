=== Content Relations ===
Contributors: palasthotel, edwardbock
Donate link: http://palasthotel.de/
Tags: post, relation, metabox
Requires at least: 4.0
Tested up to: 6.0.1
Stable tag: 1.0.15
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl

Add relations between posts.

== Description ==

You can add relations between posts with a new meta box in post editor.

== Installation ==

1. Upload `content-relations-wordpress.zip` to the `/wp-content/plugins/` directory
1. Extract the Plugin to a `content-relations` Folder
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==
= 1.0.15 =
* Fix: Remove deprecated dynamic properties

= 1.0.14 =
* Bugfix: Filter unpublished relations from rest api

= 1.0.13 =
* Bugfix: error when using "Members" plugin

= 1.0.12 =
* Optimization: check to prevent empty types
* Optimization: if there is only one relation type this type is preselected
* Optimization: show all types again if relations types are reopened and there is no search input

= 1.0.11 =
* Feature: Filter for meta box title.
* Optimization: post context in ajax search query for meta box content search.

= 1.0.10 =
* Feature: Before and after table in post meta box actions.

= 1.0.9 =
* Feature: two new filters for meta box autocomplete args
* Optimization: Post type names in post edit meta box autocomplete

= 1.0.8 =
* hookable into grid posts box

= 1.0.7 =
* new public function to get the relation store for a post id
* New filter: with content_relations_add_meta_box you can disable meta box for posts
* db.php functions refactoring
* public function with ph_ prefix were deprecated
* some more public functions were deprecated

= 1.0.6 =
* WP_Query extension

= 1.0.5 =
* Bugfix for WP multisite setups

= 1.0.4 =
* Sortable handler icon
* REST API compatibility

= 1.0.3 =
* sortable relations

= 1.0.2 =
* typo fix in function name "ph_content_relations_add_realtion" => "ph_content_relations_add_relation"
* Wordpress 4.4 compatibility tests

= 1.0.1 =
* Some public functions added

= 1.0 =
* First release

== Upgrade Notice ==


== Arbitrary section ==



