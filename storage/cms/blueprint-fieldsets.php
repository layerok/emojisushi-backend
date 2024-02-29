<?php return array (
  'unittest-category-b022a74b' => 
  array (
    'name' => 'Category',
    'handle' => 'UnitTest\\Category',
    'contentUuid' => 'unittest-category-b022a74b',
    'fields' => 
    array (
      'is_featured' => 
      array (
        'label' => 'Featured',
        'type' => 'checkbox',
      ),
      'description' => 
      array (
        'label' => 'Description',
        'type' => 'text',
        'column' => 
        array (
          'sortableDefault' => true,
        ),
      ),
      'posts' => 
      array (
        'type' => 'entries',
        'source' => 'UnitTest\\Post',
        'inverse' => 'categories',
        'hidden' => true,
      ),
    ),
    'columns' => NULL,
    'scopes' => NULL,
    'validation' => NULL,
  ),
  'unittest-author-f28b6604' => 
  array (
    'name' => 'Author',
    'handle' => 'UnitTest\\Author',
    'contentUuid' => 'unittest-author-f28b6604',
    'fields' => 
    array (
      'avatar' => 
      array (
        'label' => 'Avatar',
        'type' => 'mediafinder',
        'mode' => 'image',
      ),
      'role' => 
      array (
        'label' => 'Role',
        'type' => 'text',
      ),
    ),
    'columns' => NULL,
    'scopes' => NULL,
    'validation' => NULL,
  ),
  'unittest-post-edcd102e:regular_post' => 
  array (
    'name' => 'Regular Post',
    'handle' => 'regular_post',
    'contentUuid' => 'unittest-post-edcd102e',
    'columns' => NULL,
    'scopes' => NULL,
    'validation' => NULL,
    'fields' => 
    array (
      'content' => 
      array (
        'label' => 'Content',
        'type' => 'richeditor',
      ),
      '_blog_post_content' => 
      array (
        'type' => 'mixin',
        'source' => 'UnitTest\\PostContent',
      ),
    ),
  ),
  'unittest-post-edcd102e:markdown_post' => 
  array (
    'name' => 'Markdown Post',
    'handle' => 'markdown_post',
    'contentUuid' => 'unittest-post-edcd102e',
    'columns' => NULL,
    'scopes' => NULL,
    'validation' => NULL,
    'fields' => 
    array (
      'content' => 
      array (
        'label' => 'Content',
        'type' => 'markdown',
      ),
      '_blog_post_content' => 
      array (
        'type' => 'mixin',
        'source' => 'UnitTest\\PostContent',
      ),
    ),
  ),
);