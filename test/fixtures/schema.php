<?php

return [
    'tables' => [
        'author' => [
            'fields' => [
                'id'    => [
                    'primary'   => true,
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'autoIncrement' => true
                ],
                'firstname'  => [
                    'type'      => 'string',
                    'length'    => 64,
                    'nullable'  => true
                ],
                'lastname'  => [
                    'type'      => 'string',
                    'length'    => 64
                ],
                'email' => [
                    'type'      => 'string',
                    'length'    => 255
                ],
                'user_id' => [
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'foreign'   => 'user.id'
                ],
                'editor_id' => [
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'nullable'  => true,
                    'foreign'   => 'author.id'
                ]
            ],
            'indexes' => [
                'UNIQUE' => [
                    'type' => 'unique',
                    'fields' => [
                        'firstname',
                        'lastname'
                    ]
                ],
                'UQ_user_id' => [
                    'type' => 'unique',
                    'fields' => [
                        'user_id'
                    ]
                ]
            ],
            'behaviors' => [
                [
                    'class' => 'DelegateBehavior',
                    'config' => [
                        'delegateToRelation' => 'User'
                    ],
                    'instanceShared' => true
                ]
            ],
            'recordClass' => '\Dive\TestSuite\Model\Author'
        ],
        'user' => [
            'fields' => [
                'id'    => [
                    'primary'   => true,
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'autoIncrement' => true
                ],
                'username'  => [
                    'type'      => 'string',
                    'length'    => 64
                ],
                'password'  => [
                    'type'      => 'string',
                    'length'    => 32
                ]
            ],
            'indexes' => [
                'UNIQUE' => [
                    'type' => 'unique',
                    'fields' => [
                        'username'
                    ]
                ]
            ],
            'recordClass' => '\Dive\TestSuite\Model\User'
        ],
        'donation' => [
            'fields' => [
                'id' => [
                    'primary' => true,
                    'type' => 'integer',
                    'length' => 10,
                    'unsigned' => true,
                    'autoIncrement' => true
                ],
                'article_id' => [
                    'type' => 'integer',
                    'length' => 10,
                    'unsigned' => true,
                    'nullable' => true,
                ],
                'author_id' => [
                    'type' => 'integer',
                    'length' => 10,
                    'unsigned' => true,
                    'nullable' => true,
                ],
                'comment_id' => [
                    'type' => 'integer',
                    'length' => 10,
                    'unsigned' => true,
                    'nullable' => true,
                ],
                'is_cancelled' => [
                    'type' => 'integer',
                    'length' => 1,
                    'unsigned' => true,
                    'default' => '0'
                ],
                'value' => [
                    'type' => 'decimal',
                    'scale' => 2,
                    'length' => 14,
                    'nullable' => true
                ]
            ],
            'recordClass' => '\Dive\TestSuite\Model\Donation'
        ],
        'article' => [
            'fields' => [
                'id'    => [
                    'primary'   => true,
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'autoIncrement' => true
                ],
                'author_id' => [
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'foreign'   => 'author.id'
                ],
                'is_published' => [
                    'type'      => 'boolean',
                    'length'    => 1,
                    'unsigned'  => true,
                    'default'   => '0'
                ],
                'title' => [
                    'type'      => 'string',
                    'length'    => 64
                ],
                'teaser' => [
                    'type'      => 'string',
                    'length'    => 255
                ],
                'text' => [
                    'type'      => 'string',
                    'length'    => 2000
                ],
                'created_on' => [
                    'type'      => 'datetime',
                    'nullable'  => true
                ],
                'saved_on' => [
                    'type'      => 'datetime',
                    'nullable'  => true
                ],
                'changed_on' => [
                    'type'      => 'datetime',
                    'nullable'  => true
                ]
            ],
            'behaviors' => [
                [
                    'class' => 'TimestampableBehavior',
                    'instanceShared' => true,
                    'config' => [
                        'onInsert' => 'created_on',
                        'onSave' => 'saved_on',
                        'onUpdate' => 'changed_on'
                    ]
                ]
            ],
            'recordClass' => '\Dive\TestSuite\Model\Article'
        ],
        'comment' => [
            'fields' => [
                'id'    => [
                    'primary'   => true,
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'autoIncrement' => true
                ],
                'user_id' => [
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'foreign'   => 'user.id'
                ],
                'article_id' => [
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'foreign'   => 'article.id'
                ],
                'title' => [
                    'type'      => 'string',
                    'length'    => 64
                ],
                'text' => [
                    'type'      => 'string',
                    'length'    => 2000
                ],
                'datetime' => [
                    'type'      => 'datetime'
                ],
                'comment_id'    => [
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'foreign'   => 'comment.id',
                    'nullable' => true
                ]
            ],
            'recordClass' => '\Dive\TestSuite\Model\Comment'
        ],
        'tag' => [
            'fields' => [
                'id'    => [
                    'primary'   => true,
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'autoIncrement' => true
                ],
                'name' => [
                    'type'      => 'string',
                    'length'    => 64
                ]
            ],
            'indexes' => [
                'UNIQUE' => [
                    'type' => 'unique',
                    'fields' => [
                        'name'
                    ]
                ]
            ],
            'recordClass' => '\Dive\TestSuite\Model\Tag'
        ],
        'tree_node' => [
            'fields' => [
                'id'    => [
                    'primary'   => true,
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'autoIncrement' => true
                ],
                'name' => [
                    'type'      => 'string',
                    'length'    => 64
                ],
                'tree_node_id'    => [
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'nullable'  => true,
                    'foreign'   => 'tree_node.id'
                ],
            ]
        ],
        'article2tag' => [
            'fields' => [
                'article_id' => [
                    'primary'   => true,
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'foreign'   => 'article.id'
                ],
                'tag_id' => [
                    'primary'   => true,
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'foreign'   => 'tag.id'
                ]
            ],
            'recordClass' => '\Dive\TestSuite\Model\Article2tag'
        ],
        'data_types' => [
            'fields' => [
                'id' => [
                    'primary'   => true,
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'autoIncrement' => true
                ],
                't_boolean' => [
                    'type' => 'boolean',
                    'nullable' => true
                ],
                't_integer_signed' => [
                    'type' => 'integer',
                    'length' => 5,
                    'nullable' => true
                ],
                't_integer_unsigned' => [
                    'type' => 'integer',
                    'length' => 5,
                    'unsigned' => true,
                    'nullable' => true
                ],
                't_integer_unsigned_zerofilled' => [
                    'type' => 'integer',
                    'length' => 5,
                    'zerofill' => true,
                    'unsigned' => true,
                    'nullable' => true
                ],
                't_decimal_signed' => [
                    'type' => 'decimal',
                    'length' => 13,
                    'scale' => 2,
                    'nullable' => true
                ],
                't_decimal_unsigned' => [
                    'type' => 'decimal',
                    'length' => 13,
                    'scale' => 2,
                    'unsigned' => true,
                    'nullable' => true
                ],
                't_string' => [
                    'type' => 'string',
                    'length' => 255,
                    'nullable' => true
                ],
                't_datetime' => [
                    'type' => 'datetime',
                    'nullable' => true
                ],
                't_date' => [
                    'type' => 'date',
                    'nullable' => true
                ],
                't_time' => [
                    'type' => 'time',
                    'nullable' => true
                ],
                't_timestamp' => [
                    'type' => 'timestamp',
                    'nullable' => true
                ],
                't_blob' => [
                    'type' => 'blob',
                    'nullable' => true
                ],
                't_enum' => [
                    'type' => 'enum',
                    'values' => ['123', 'abc', 'zyx', '0987'],
                    'nullable' => true
                ]
            ]
        ],
        'unique_constraint_test' => [
            'fields' => [
                'id' => [
                    'primary'   => true,
                    'type'      => 'integer',
                    'length'    => 10,
                    'unsigned'  => true,
                    'autoIncrement' => true
                ],
                'single_unique' => [
                    'type'      => 'string',
                    'length'    => 10,
                    'nullable'  => true
                ],
                'single_unique_null_constrained' => [
                    'type'      => 'string',
                    'length'    => 10,
                    'nullable'  => true
                ],
                'composite_unique1' => [
                    'type'      => 'string',
                    'length'    => 10,
                    'nullable'  => true
                ],
                'composite_unique2' => [
                    'type'      => 'string',
                    'length'    => 10,
                    'nullable'  => true
                ],
                'composite_unique_null_constrained1' => [
                    'type'      => 'string',
                    'length'    => 10,
                    'nullable'  => true
                ],
                'composite_unique_null_constrained2' => [
                    'type'      => 'string',
                    'length'    => 10,
                    'nullable'  => true
                ]
            ],
            'indexes' => [
                'UQ_1' => [
                    'type' => 'unique',
                    'fields' => [
                        'single_unique'
                    ],
                    'nullConstrained' => false
                ],
                'UQ_2' => [
                    'type' => 'unique',
                    'fields' => [
                        'single_unique_null_constrained'
                    ],
                    'nullConstrained' => true
                ],
                'UQ_3' => [
                    'type' => 'unique',
                    'fields' => [
                        'composite_unique1',
                        'composite_unique2'
                    ],
                    'nullConstrained' => false
                ],
                'UQ_4' => [
                    'type' => 'unique',
                    'fields' => [
                        'composite_unique_null_constrained1',
                        'composite_unique_null_constrained2'
                    ],
                    'nullConstrained' => true
                ]
            ]
        ]
    ],
    'relations' => [
        'article.author_id' => [
            'owningAlias' => 'Article',
            'owningField' => 'author_id',
            'owningTable' => 'article',
            'refAlias' => 'Author',
            'refField' => 'id',
            'refTable' => 'author',
            'type' => '1-m',
            'onUpdate' => 'CASCADE',
            'onDelete' => 'RESTRICT'
        ],
        'author.user_id' => [
            'owningAlias' => 'Author',
            'owningField' => 'user_id',
            'owningTable' => 'author',
            'refAlias' => 'User',
            'refField' => 'id',
            'refTable' => 'user',
            'type' => '1-1',
            'onUpdate' => 'CASCADE',
            'onDelete' => 'CASCADE'
        ],
        'author.editor_id' => [
            'owningAlias' => 'Author',
            'owningField' => 'editor_id',
            'owningTable' => 'author',
            'refAlias' => 'Editor',
            'refField' => 'id',
            'refTable' => 'author',
            'type' => '1-m',
            'onUpdate' => 'CASCADE',
            'onDelete' => 'SET NULL'
        ],
        'author_user_view.user_id' => [
            'owningAlias' => 'AuthorUserView',
            'owningField' => 'user_id',
            'owningTable' => 'author_user_view',
            'refAlias' => 'User',
            'refField' => 'id',
            'refTable' => 'user',
            'type' => '1-1',
            'onUpdate' => 'RESTRICT',
            'onDelete' => 'RESTRICT'
        ],
        'comment.user_id' => [
            'owningAlias' => 'Comment',
            'owningField' => 'user_id',
            'owningTable' => 'comment',
            'refAlias' => 'User',
            'refField' => 'id',
            'refTable' => 'user',
            'type' => '1-m',
            'onUpdate' => 'CASCADE',
            'onDelete' => 'CASCADE'
        ],
        'comment.article_id' => [
            'owningAlias' => 'Comment',
            'owningField' => 'article_id',
            'owningTable' => 'comment',
            'refAlias' => 'Article',
            'refField' => 'id',
            'refTable' => 'article',
            'type' => '1-m',
            'onUpdate' => 'CASCADE',
            'onDelete' => 'CASCADE'
        ],
        'comment.comment_id' => [
            'owningAlias' => 'Parent',
            'owningField' => 'comment_id',
            'owningTable' => 'comment',
            'refAlias' => 'Children',
            'refField' => 'id',
            'refTable' => 'comment',
            'type' => '1-1',
            'onUpdate' => 'CASCADE',
            'onDelete' => 'CASCADE'
        ],
        'tree_node.tree_node_id' => [
            'owningAlias' => 'Parent',
            'owningField' => 'tree_node_id',
            'owningTable' => 'tree_node',
            'refAlias' => 'Children',
            'refField' => 'id',
            'refTable' => 'tree_node',
            'type' => '1-1',
            'onUpdate' => 'CASCADE',
            'onDelete' => 'CASCADE'
        ],
        'article2tag.article_id' => [
            'owningAlias' => 'Article2tagHasMany',
            'owningField' => 'article_id',
            'owningTable' => 'article2tag',
            'refAlias' => 'Article',
            'refField' => 'id',
            'refTable' => 'article',
            'type' => '1-m',
            'onUpdate' => 'CASCADE',
            'onDelete' => 'CASCADE'
        ],
        'article2tag.tag_id' => [
            'owningAlias' => 'Article2tagHasMany',
            'owningField' => 'tag_id',
            'owningTable' => 'article2tag',
            'refAlias' => 'Tag',
            'refField' => 'id',
            'refTable' => 'tag',
            'type' => '1-m',
            'onUpdate' => 'CASCADE',
            'onDelete' => 'CASCADE'
        ]
    ],
    'views' => [
        'author_user_view' => [
            'fields' => [
                'id' => [
                    'primary' => true,
                    'type' => 'integer',
                    'nullable' => true
                ],
                'firstname' => [
                    'type' => 'string',
                    'length' => 64,
                    'nullable' => true
                ],
                'lastname' => [
                    'type' => 'string',
                    'length' => 64,
                    'nullable' => true
                ],
                'email' => [
                    'type' => 'string',
                    'length' => 255,
                    'nullable' => true
                ],
                'user_id' => [
                    'type' => 'integer',
                    'length' => 10,
                    'unsigned' => true,
                    'foreign' => 'user.id'
                ],
                'username' => [
                    'type' => 'string',
                    'length' => 64,
                    'nullable' => true
                ],
                'password' => [
                    'type' => 'string',
                    'length' => 32,
                    'nullable' => true
                ]
            ],
            'recordClass' => '\Dive\TestSuite\Model\AuthorUserView',
            'sqlStatement' => 'SELECT a.id, a.firstname, a.lastname, a.email, u.id AS user_id, u.username, u.password FROM author a LEFT JOIN user u ON a.user_id = u.id'
        ]
    ],
    'baseRecordClass' => '\\Dive\\TestSuite\\Record\\Record'
];
