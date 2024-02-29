<?php return array (
  'unittest-post-content-4d7fd1e4' => 
  array (
    'uuid' => 'unittest-post-content-4d7fd1e4',
    'handle' => 'UnitTest\\PostContent',
    'type' => 'mixin',
    'name' => 'Blog Post Content',
    'fields' => 
    array (
      'author' => 
      array (
        'label' => 'Author',
        'type' => 'entries',
        'maxItems' => 1,
        'source' => 'UnitTest\\Author',
        'column' => 'invisible',
      ),
      'categories' => 
      array (
        'label' => 'Categories',
        'type' => 'entries',
        'source' => 'UnitTest\\Category',
        'displayMode' => 'taglist',
        'column' => 
        array (
          'relation' => 'categories',
          'relationCount' => true,
          'type' => 'number',
        ),
      ),
    ),
    'handleSlug' => 'unit_test_post_content',
  ),
);