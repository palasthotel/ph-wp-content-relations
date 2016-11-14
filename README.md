## Content Relations

You can add typed relations between posts with a new meta box in post editor. The plugin is available on [WordPress.org](https://wordpress.org/plugins/content-relations/)

### Use in Theme

You can use the following functions in theme. **Wrap with if function_exists.**

---

Get all relations related to the post ID.

**Params:**

post_id: ID of the post we want relations for.


```php
$relatioins = ph_content_relations_get_relations_by_post_id($post_id)
```

**Returns Array of Objects:**

source_id: Post ID of source post. (The Post in which meta field the relation was created)
 
target_id: Post ID of related target.

type: Relation type slug.
 
weight: Weight of relation in list. (Used for sorting) 

post_title: Title of related post.
 
post_type: Post type of related post.

---

Get all relations of a type by the post ID.

Params:

post_id: ID of the post we want relations for.

type_slug: Slug of relation type.

source_only: get only relations where post ID is source or all relations.

```php
$relations = ph_content_relations_get_relations_by_post_id_and_type($post_id, $relation_type, $source_only = true);
```

**Returns Array of Objects:**

source_id: Post ID of source post. (The Post in which meta field the relation was created)
 
target_id: Post ID of related target.

type: Relation type slug.
 
weight: Weight of relation in list. (Used for sorting) 

post_title: Title of related post.
 
post_type: Post type of related post.