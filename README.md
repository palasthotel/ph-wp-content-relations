# Content Relations

You can add typed relations between posts with a new meta box in post editor. The plugin is available on [WordPress.org](https://wordpress.org/plugins/content-relations/)

## Use in Theme

You can use the following functions in theme. **Wrap with if function_exists.**

---

### Get all relations related to the post ID.


```php
$relatioins = ph_content_relations_get_relations_by_post_id($post_id)
```

**Parameters:**


_$post_id_ 

ID of the post we want relations for.


**Return**

_$source_id_ 

Post ID of source post. (The Post in which meta field the relation was created)
 
_$target_id_ 

Post ID of related target.

_$type_ 

Relation type slug.
 
_$weight_ 

Weight of relation in list. (Used for sorting) 

_post_title_  

Title of related post.
 
_post_type_ 

Post type of related post.

---

### Get all relations of a type by the post ID.

**Parameters:**

```php
$relations = ph_content_relations_get_relations_by_post_id_and_type($post_id, $relation_type, $source_only = true);
```

_post_id_ 

ID of the post we want relations for.

_type_slug_
 
Slug of relation type. Default: true

_source_only_ 

get only relations where post ID is source or all relations.


**Return**

_source_id_ 

Post ID of source post. (The Post in which meta field the relation was created)
 
_target_id_ 

Post ID of related target.

_type_ 

Relation type slug.
 
_weight_ 

Weight of relation in list. (Used for sorting) 

_post_title_ 

Title of related post.
 
_post_type_ 

Post type of related post.