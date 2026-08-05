=== Content Relations ===
Contributors: palasthotel, janaeggebrecht, edwardbock
Donate link: http://palasthotel.de/
Tags: post, relation, metabox, rest, related
Requires at least: 4.8
Tested up to: 7.0.2
Requires PHP: 7.4
Stable tag: 1.0.15
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Relate posts to other posts, with a meta box in the editor and the relations on the REST API.

== Description ==

Content Relations adds a meta box to the post editor for linking a post to any other post. Relations are typed - you name the kind of relation ("see also", "part of", whatever fits) - and ordered by dragging.

Each related post's relations are exposed on the REST API under a `content_relations` field, so a headless or block-based front end can read them. Relations to unpublished posts are hidden from anyone who cannot edit posts.

A `WP_Query` extension lets a query filter by relation: give it a related post id and, optionally, a relation type, and it returns the posts related to it.

== Installation ==

1. Install the plugin through **Plugins → Add New**, or upload it to `/wp-content/plugins/`.
2. Activate it through the **Plugins** menu.
3. A **Content Relations** meta box appears in the post editor, and a settings page under **Tools → Content Relations** lists the relation types.

== Frequently Asked Questions ==

= How do I read relations on the front end? =

Every post's REST response carries a `content_relations` field with its relations. In PHP, `content_relations_get_relations_by_post_id( $post_id )` returns the same data.

= Are relations to drafts or private posts visible? =

No. On the REST API they are filtered out for anyone who cannot edit posts, and the editor's search only offers posts the current user may read.

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



