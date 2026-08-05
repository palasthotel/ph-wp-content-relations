# Content Relations (WordPress-Plugin)

With this plugin you can add typed, ordered relations between posts - editable from the block editor's document sidebar, the classic editor's meta box, or the Tools screen - and query them back with a `WP_Query` extension, a REST field, or a Query Loop block variation. The plugin is available on [WordPress.org](https://wordpress.org/plugins/content-relations/) (slug `content-relations`; this repository is `ph-wp-content-relations`).

## Repository layout

`public/` is exactly what ships to wordpress.org; everything else is repository-only.
`ph-content-relations.php` in the root is a development wrapper that loads `public/`, so
the whole repository can be symlinked into `wp-content/plugins` during development.

Releases are cut by release-please from conventional commits and deployed to the
wordpress.org SVN by GitHub Actions — see [.github/WORKFLOWS.md](.github/WORKFLOWS.md).
Contribution rules and the local setup are in [CONTRIBUTING.md](CONTRIBUTING.md).

## Editing relations

- **Block editor**: a *Content Relations* panel in the document sidebar, grouped by
  relation type. Saves with the rest of the post, the way a taxonomy does - no separate
  save button.
- **Classic editor**: the same editor (`src/shared/RelationsEditor.jsx`), in a meta box
  instead of the sidebar.
- **Tools → Content Relations**: every relation type in a sortable, searchable
  `WP_List_Table`. Opening a type there lets you reorder or remove its relations across
  every post that uses it, grouped by source post.

All three write through the same paths: the block editor and meta box via the
`content_relations_edit` REST field (`classes/rest-editor.php`), the Tools screen's
per-type editor via `/content-relations/v1/reorder`.

## Showing relations on the front end

- **Related content block**: a `core/query` variation (`src/query-loop/QueryLoopVariation.jsx`,
  `classes/query-loop.php`) that lists the post it's placed on's own related posts under
  one chosen type, in their saved order (`post__in` + `orderby=post__in`) - drop it in
  wherever a "related articles" section belongs.
- **REST**: a read-only `content_relations` field on every post type's REST response,
  carrying both directions of a relation.
- **`WP_Query` extension**: see below.

## Filters

Disable content relations meta box for some posts.

```
add_filter('content_relations_add_meta_box', function($doIt, $post_type, $post){
	return false;
}, 10, 3) 
```

## WP_Query Extension

You can use the ```content_relations``` argument in ```new WP_Query($args)``` to get related posts.

```php
$query = new WP_Query(array(
	...
	"content_relations" => array(
		"from" => $post_id,
		"to" => $post_id,
		"with" => $post_id,
		"type"=> String|Array of strings
	)
));
```

_from_ ==> Get post relations created on post edit $post_id page

_to_ ==> Get post relations created on related posts edit page

_with_ ==> Get _from_ AND _to_ related posts

_type_ ==> Get only posts with specified type or types

## Use in Theme

You can use the following functions in theme. **Wrap with if function_exists.**

---

### Get relation store object

```php
$store = content_relations_get_store($post_id);
```

**Parameters:**


_$post_id_ ==> ID of the post we want relations for.

### Get all relations related to the post ID.


```php
$relatioins = content_relations_get_relations_by_post_id($post_id)
```

**Parameters:**


_$post_id_ ==> ID of the post we want relations for.


**Return**

Array of objects as follows:

_source_id_ ==> Post ID of source post. (The Post in which meta field the relation was created)
 
_target_id_ ==> Post ID of related target.

_type_ ==> Relation type slug.
 
_weight_ ==> Weight of relation in list. (Used for sorting) 

_post_title_ ==> Title of related post.
 
_post_type_ ==> Post type of related post.

---

### Get all relations of a type by the post ID.

**Parameters:**

```php
$relations = content_relations_get_relations_by_post_id_and_type($post_id, $relation_type, $source_only = true);
```

_$post_id_ ==> ID of the post we want relations for.

_$type_slug_ ==> Slug of relation type. Default: true

_$source_only_ ==> Get only relations where post ID is source or all relations.


**Return**

Array of objects as follows:

_source_id_ ==> Post ID of source post. (The Post in which meta field the relation was created)
 
_target_id_ ==> Post ID of related target.

_type_ ==> Relation type slug.
 
_weight_ ==> Weight of relation in list. (Used for sorting) 

_post_title_ ==> Title of related post.
 
_post_type_ ==> Post type of related post.
