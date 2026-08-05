=== Content Relations ===
Contributors: palasthotel, janaeggebrecht, edwardbock
Donate link: http://palasthotel.de/
Tags: post, relation, related, rest, gutenberg
Requires at least: 6.6
Tested up to: 7.0.2
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Relate posts to other posts, edit them in the block editor sidebar or a meta box, and show them with a Related content block or on the REST API.

== Description ==

Content Relations lets you link a post to any other post. Relations are typed - you name the kind of relation ("see also", "part of", whatever fits) - and ordered by dragging.

In the block editor, a **Content Relations** panel in the document sidebar edits a post's relations the same way you'd edit categories: grouped by type, saved with the rest of the post, no separate save button. The classic editor keeps the same editor in a meta box instead.

A **Related content** block - a variation of the core Query Loop block - lists a post's own related posts under one relation type, in the order you set in the sidebar, meta box, or the Tools screen. Drop it in wherever a "related articles" section belongs; it uses the same layout options as any other Query Loop.

**Tools → Content Relations** lists every relation type in a sortable, searchable table. Opening a type there lets you reorder or remove its relations across every post that uses it, grouped by source post, with drag handles or up/down buttons.

Each related post's relations are exposed on the REST API under a `content_relations` field, so a headless front end can read them too. Relations to unpublished posts are hidden from anyone who cannot edit posts.

A `WP_Query` extension lets a query filter by relation: give it a related post id and, optionally, a relation type, and it returns the posts related to it.

== Installation ==

1. Install the plugin through **Plugins → Add New**, or upload it to `/wp-content/plugins/`.
2. Activate it through the **Plugins** menu.
3. A **Content Relations** panel appears in the block editor's document sidebar (a meta box in the classic editor), and a settings page under **Tools → Content Relations** lists the relation types.

== Frequently Asked Questions ==

= How do I show related posts on the front end? =

Add the **Related content** block (in the inserter, under the same category as the Query Loop block) to a post, and pick a relation type in its settings panel. It lists that post's related posts of that type, in the order you set in the sidebar or meta box.

= How do I read relations on the front end myself? =

Every post's REST response carries a `content_relations` field with its relations. In PHP, `content_relations_get_relations_by_post_id( $post_id )` returns the same data.

= Are relations to drafts or private posts visible? =

No. On the REST API they are filtered out for anyone who cannot edit posts, and the editor's search only offers posts the current user may read.

== Screenshots ==


== Changelog ==

= 1.1.0 =
**Features**
* add a Query Loop variation for related content (3a51a66)
* add an edit screen for a relation type, grouped by source post (a0c3b74)
* rebuild the Tools screen as a WP_List_Table (bec0d3f)
* rework the type-edit screen onto ph-postqueue's item table (00f4d5e)
* show each related post's type next to its title (062ac13)

**Bug Fixes**
* hide the Query Loop's post type, order and sticky controls (15385ef)
* link the relation type's name to its edit screen (658d69c)
* raise Requires at least to 6.6 (75fd296)
* redirect the misspelled legacy Tools page slug (78bf51c)
* repair and complete the translations (11270a8)
* replace the redundant "Back to types" button with Reset (d48a8b5)
* translate the JS UI strings in PHP instead of at runtime (5bdb58b)

= 1.0.16 =
**Bug Fixes**
* build the relations admin UI with the DOM instead of HTML strings (3ac2b97)
* close information disclosure, SQL injection and CSRF holes (3d4fa95)

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



