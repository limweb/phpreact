<?php

// require_once __DIR__.'/database.php';
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Collection;

// Capsule::enableQuerylog();

class CommentBuilder extends Builder
{
    /**
     * Where clause for only approved comments
     * 
     * @return CommentBuilder
     */
    public function approved()
    {
        return $this->where('comment_approved', 1);
    }

}

class TermTaxonomyBuilder extends Builder
{
    private $category_slug;

    public function posts()
    {
        return $this->with('posts');
    }

    public function category()
    {
        return $this->where('taxonomy', 'category');
    }

    /**
     * Get only posts with a specific slug
     *
     * @param string slug
     * @return PostBuilder
     */
    public function slug( $category_slug=null )
    {
        if( !is_null($category_slug) and !empty($category_slug) ) {
            // set this category_slug to be used in with callback
            $this->category_slug = $category_slug;

            // exception to filter on slug from category
            $exception = function($query) {
                $query->where('slug', '=', $this->category_slug);
            };

            // load term to filter
            return $this->whereHas('term', $exception);
        }

        return $this;
    }
}


class PostBuilder extends Builder
{
    /**
     * Get only posts with a custom status
     * 
     * @param string $postStatus
     * @return PostBuilder
     */
    public function status($postStatus)
    {
        return $this->where('post_status', $postStatus);
    }

    /**
     * Get only published posts
     * 
     * @return PostBuilder
     */
    public function published()
    {
        return $this->status('publish');
    }

    /**
     * Get only posts from a custo post type
     * 
     * @param string $type
     * @return PostBuilder
     */
    public function type($type)
    {
        return $this->where('post_type', $type);
    }

    public function taxonomy($taxonomy, $term)
    {
        return $this->whereHas('taxonomies', function($query) use ($taxonomy, $term) {
            $query->where('taxonomy', $taxonomy)->whereHas('term', function($query) use ($term) {
                $query->where('slug', $term);
            });
        });
    }

    /**
     * Get only posts with a specific slug
     * 
     * @param string slug
     * @return PostBuilder
     */
    public function slug($slug)
    {
        return $this->where('post_name', $slug);
    }

    /**
     * Paginate the results
     * 
     * @param int $perPage
     * @param int $currentPage
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function paged($perPage = 10, $currentPage = 1)
    {
        $skip = $currentPage * $perPage - $perPage;
        return $this->skip($skip)->take($perPage)->get();
    }
}


/**
 * Author model
 *
 * @author Ashwin Sureshkumar<ashwin.sureshkumar@gmail.com>
 */
class Author extends Eloquent {

    protected $table = 'wp_users';
    protected $primaryKey = 'ID';
    protected $hidden = ['user_pass'];


    /**
     * Posts relationship
     *
     * @return PostMetaCollection
     */
    public function posts() {

        return $this->hasMany('Post', 'post_author');
    }
}


class Comment extends Eloquent
{
    protected $table = 'wp_comments';
    protected $primaryKey = 'comment_ID';

    /**
     * Post relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo('Post', 'comment_post_ID');
    }

    /**
     * Original relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function original()
    {
        return $this->belongsTo('Comment', 'comment_parent');
    }

    /**
     * Replies relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function replies()
    {
        return $this->hasMany('Comment', 'comment_parent');
    }

    /**
     * Verify if the current comment is approved
     *
     * @return bool
     */
    public function isApproved()
    {
        return $this->attributes['comment_approved'] == 1;
    }

    /**
     * Verify if the current comment is a reply from another comment
     *
     * @return bool
     */
    public function isReply()
    {
        return $this->attributes['comment_parent'] > 0;
    }

    /**
     * Verify if the current comment has replies
     *
     * @return bool
     */
    public function hasReplies()
    {
        return count($this->replies) > 0;
    }

    /**
     * Find a comment by post ID
     *
     * @param int $postId
     * @return Comment
     */
    public static function findByPostId($postId)
    {
        $instance = new static;
        return $instance->where('comment_post_ID', $postId)->get();
    }

    /**
     * Override the parent newQuery() to the custom CommentBuilder class
     *
     * @param bool $excludeDeleted
     * @return CommentBuilder
     */
    public function newQuery($excludeDeleted = true)
    {
        $builder = new CommentBuilder($this->newBaseQueryBuilder());
        $builder->setModel($this)->with($this->with);

        if ($excludeDeleted and $this->softDelete) {
            $builder->whereNull($this->getQualifiedDeletedAtColumn());
        }

        return $builder;
    }
}

class PostMetaCollection extends Collection
{
    protected $changedKeys = array();

    /**
     * Search for the desired key and return only the row that represent it
     * 
     * @param string $key
     * @return string
     */
    public function __get($key)
    {
        foreach ($this->items as $item) {
            if ($item->meta_key == $key) {
                return $item->meta_value;
            }
        }
    }

    public function __set($key, $value)
    {
        $this->changedKeys[] = $key;

        foreach ($this->items as $item) {
            if ($item->meta_key == $key) {
                $item->meta_value = $value;
                return;
            }
        }

        $item = new PostMeta(array(
            'meta_key' => $key,
            'meta_value' => $value,
        ));

        $this->push($item);
    }

    public function save($postId)
    {
        $this->each(function($item) use ($postId) {
            if (in_array($item->meta_key, $this->changedKeys)) {
                $item->post_id = $postId;
                $item->save();
            }
        });
    }

}

class PostMeta extends Eloquent
{
    protected $table = 'wp_postmeta';
    protected $primaryKey = 'meta_id';
    public $timestamps = false;
    protected $fillable = array('meta_key', 'meta_value', 'post_id');

    /**
     * Post relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo('Post');
    }

    /**
     * Override newCollection() to return a custom collection
     *
     * @param array $models
     * @return PostMetaCollection
     */
    public function newCollection(array $models = array())
    {
        return new PostMetaCollection($models);
    }
}


class Post extends Eloquent
{
    const CREATED_AT = 'post_date';
    const UPDATED_AT = 'post_modified';

    protected $table = 'wp_posts';
    protected $primaryKey = 'ID';
    protected $dates = ['post_date', 'post_date_gmt', 'post_modified', 'post_modified', 'post_modified_gmt'];
    protected $with = array('meta');

    /**
     * Meta data relationship
     *
     * @return PostMetaCollection
     */
    public function meta()
    {
        return $this->hasMany('PostMeta', 'post_id');
    }

    public function fields()
    {
        return $this->meta();
    }

    /**
     * Taxonomy relationship
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function taxonomies()
    {
        return $this->belongsToMany('TermTaxonomy', 'term_relationships', 'object_id', 'term_taxonomy_id');
    }

    /**
     * Comments relationship
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function comments()
    {
        return $this->hasMany('Comment', 'comment_post_ID');
    }

    /**
    *   Author relationship
    * 
    *   @return Illuminate\Database\Eloquent\Collection
    */
    public function author(){

        return $this->belongsTo('Author', 'ID');

    }

    /**
     * Get attachment
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function attachment()
    {
        return $this->hasMany('Post', 'post_parent')->where('post_type', 'attachment');
    }


    /**
     * Get revisions from post
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function revision()
    {
        return $this->hasMany('Post', 'post_parent')->where('post_type', 'revision');
    }

    /**
     * Overriding newQuery() to the custom PostBuilder with some interesting methods
     *
     * @param bool $excludeDeleted
     * @return PostBuilder
     */
    public function newQuery($excludeDeleted = true)
    {
        $builder = new PostBuilder($this->newBaseQueryBuilder());
        $builder->setModel($this)->with($this->with);
        $builder->orderBy('post_date', 'desc');

        if (isset($this->postType) and $this->postType) {
            $builder->type($this->postType);
        }

        if ($excludeDeleted and $this->softDelete) {
            $builder->whereNull($this->getQualifiedDeletedAtColumn());
        }

        return $builder;
    }

    /**
     * Magic method to return the meta data like the post original fields
     *
     * @param string $key
     * @return string
     */
    public function __get($key)
    {
        if (!isset($this->$key)) {
            if (isset($this->meta()->get()->$key)) {
                return $this->meta()->get()->$key;
            }
        }

        return parent::__get($key);
    }

    public function save(array $options = array())
    {
        if (isset($this->attributes[$this->primaryKey])) {
            $this->meta->save($this->attributes[$this->primaryKey]);
        }

        return parent::save($options);
    }

    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $instance = new $related;
        $instance->setConnection($this->getConnection()->getName());

        $localKey = $localKey ?: $this->getKeyName();

        return new HasMany($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey);
    }

    public function belongsToMany($related, $table = null, $foreignKey = null, $otherKey = null, $relation = null)
    {
        if (is_null($relation))
        {
            $relation = $this->getBelongsToManyCaller();
        }

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $instance = new $related;
        $instance->setConnection($this->getConnection()->getName());

        $otherKey = $otherKey ?: $instance->getForeignKey();

        if (is_null($table))
        {
            $table = $this->joiningTable($related);
        }

        $query = $instance->newQuery();

        return new BelongsToMany($query, $this, $table, $foreignKey, $otherKey, $relation);
    }

}



class Term extends Eloquent
{
    protected $table = 'wp_terms';
    protected $primaryKey = 'term_id';
}


class TermRelationship extends Model
{
    protected $table = 'wp_term_relationships';
    protected $primaryKey = array('object_id', 'term_taxonomy_id');

    public function post()
    {
        return $this->belongsTo('Post', 'object_id');
    }

    public function taxonomy()
    {
        return $this->belongsTo('TermTaxonomy', 'term_taxonomy_id');
    }
}



class TermTaxonomy extends Model
{
    protected $table = 'wp_term_taxonomy';
    protected $primaryKey = 'term_taxonomy_id';
    protected $with = array('term');

    public function term()
    {
        return $this->belongsTo('Term', 'term_id');
    }

    public function parentTerm()
    {
        return $this->belongsTo('TermTaxonomy', 'parent');
    }

    public function posts()
    {
        return $this->belongsToMany('Post', 'term_relationships', 'term_taxonomy_id', 'object_id');
    }

    /**
     * Overriding newQuery() to the custom TermTaxonomyBuilder with some interesting methods
     *
     * @param bool $excludeDeleted
     * @return TermTaxonomyBuilder
     */
    public function newQuery($excludeDeleted = true)
    {
        $builder = new TermTaxonomyBuilder($this->newBaseQueryBuilder());
        $builder->setModel($this)->with($this->with);

        if( isset($this->taxonomy) and !empty($this->taxonomy) and !is_null($this->taxonomy) )
            $builder->where('taxonomy', $this->taxonomy);

        return $builder;
    }

    /**
     * Magic method to return the meta data like the post original fields
     *
     * @param string $key
     * @return string
     */
    public function __get($key)
    {
        if (!isset($this->$key)) {
            if (isset($this->term->$key)) {
                return $this->term->$key;
            }
        }

        return parent::__get($key);
    }
}


class Page extends Post
{
    protected $postType = 'page';
}


class WCategory extends TermTaxonomy
{
    /**
     * Used to set the post's type
     */
    protected $taxonomy = 'category';
}


// ### Posts

//     // All published posts
    // $posts = Post::published()->get();  
    // $posts = Post::status('publish')->get();
    // dump($posts);

//     // A specific post
    // $post = Post::find(4);
    // echo $post->post_title;

// You can retrieve meta data from posts too.

//     // Get a custom meta value (like 'link' or whatever) from a post (any type)
    // $post = Post::find(2);
    // echo $post->meta->link; // OR
    // echo $post->fields->link;
    // echo $post->link; // OR

// Updating post custom fields:

    // $post = Post::find(1);
    // $post->meta->username = 'juniorgrossi';
    // $post->meta->url = 'http://grossi.io';
    // $post->save();

// Inserting custom fields:

//     $post = new Post;
//     $post->save();

//     $post->meta->username = 'juniorgrossi';
//     $post->meta->url = 'http://grossi.io';
//     $post->save();

// ### Custom Post Type

// You can work with custom post types too. You can use the `type(string)` method or create your own class.

//     // using type() method
    // $videos = Post::type('video')->status('publish')->get();

//     // using your own class
    // class Video extends Post
    // {
    //     protected $postType = 'video';
    // }

    // $videos = Video::status('publish')->get();

    // echo $videos;

// Custom post types and meta data:

//     // Get 3 posts with custom post type (store) and show its title
//     $stores = Post::type('store')->status('publish')->take(3)->get();
//     foreach ($stores as $store) {
//         $storeAddress = $store->address; // option 1
//         $storeAddress = $store->meta->address; // option 2
//         $storeAddress = $store->fields->address; // option 3
//     }

// ### Taxonomies

// You can get taxonomies for a specific post like:

//     $post = Post::find(1);
//     $taxonomy = $post->taxonomies()->first();
//     echo $taxonomy->taxonomy;

// Or you can search for posts using its taxonomies:

//     $post = Post::taxonomy('category', 'php')->first();

// ### Pages

// Pages are like custom post types. You can use `Post::type('page')` or the `Page` class.

//     // Find a page by slug
//     $page = Page::slug('about')->first(); // OR
//     $page = Post::type('page')->slug('about')->first();
//     echo $page->post_title;

// ### Categories & Taxonomies

// Get a category or taxonomy or load posts from a certain category. There are multiple ways
// to achief it.

//     // all categories
//     $cat = Taxonomy::category()->slug('uncategorized')->posts()->first();
//     echo "<pre>"; print_r($cat->name); echo "</pre>";

//     // only all categories and posts connected with it
//     $cat = Taxonomy::where('taxonomy', 'category')->with('posts')->get();
//     $cat->each(function($category) {
//         echo $category->name;
//     });

//     // clean and simple all posts from a category
//     $cat = Category::slug('uncategorized')->posts()->first();
//     $cat->posts->each(function($post) {
//         echo $post->post_title;
//     });


// ### Attachment and Revision

// Getting the attachment and/or revision from a `Post` or `Page`.

    // $page = Page::slug('about')->with('attachment')->first();
    // // get feature image from page or post
    // print_r($page->attachment);

//     $post = Post::slug('test')->with('revision')->first();
//     // get all revisions from a post or page
//     print_r($post->revision);


// ## TODO

// I'm already working with Wordpress comments integration.

// ## Licence

// Corcel is licensed under the MIT license.  

// print_r(Capsule::getQueryLog());