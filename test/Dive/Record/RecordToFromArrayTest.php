<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Record;

use Dive\TestSuite\Record\Record;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 26.07.13
 */
class RecordToFromArrayTest extends TestCase
{

    /**
     * @dataProvider provideToFromArray
     * @param string $tableName
     * @param array  $data
     * @param bool   $deep
     * @param bool   $withMappedFields
     * @param array  $expected
     */
    public function testToFromArray($tableName, array $data, $deep, $withMappedFields, $expected)
    {
        $rm = self::createDefaultRecordManager();
        $table = $rm->getTable($tableName);
        $user = $table->createRecord();
        $user->fromArray($data, $deep, $withMappedFields);
        $actual = $user->toArray($deep, $withMappedFields);
        $this->assertEquals($expected, $actual);
    }


    /**
     * @return array[]
     */
    public function provideToFromArray()
    {
        $testCases = [];
        $testCases = array_merge($testCases, $this->provideToFromArrayOneToOneTestCases());
        $testCases = array_merge($testCases, $this->provideToFromArrayOneToManyTestCases());
        return $testCases;
    }


    /**
     * @return array[]
     */
    private function provideToFromArrayOneToOneTestCases()
    {
        $authorDefaultFields = self::getDefaultFields('author');
        $authorFields = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'doe@example.com'
        ];
        $authorMappedFields = ['initials' => 'jdo'];

        $userDefaultFields = self::getDefaultFields('user');
        $userFields = [
            'username' => 'John',
            'password' => 'secret',
            'id' => 1234
        ];
        $userMappedFields = ['column1' => 'foo', 'column2' => 'bar'];

        $userInputDataWithAuthor = $userFields + $userMappedFields + [
            'Author' => $authorFields + $authorMappedFields
            ];
        $authorInputDataWithUser = $authorFields + $authorMappedFields + [
            'User' => $userFields + $userMappedFields
            ];


        $testCases = [];

        // TEST user -> Author (referenced side)
        $testCases[] = [
            'user',
            $userInputDataWithAuthor,
            false,  // recursive flag
            false,  // map fields flag
            $userFields + $userDefaultFields
        ];
        $testCases[] = [
            'user',
            $userInputDataWithAuthor,
            false,  // recursive flag
            true,   // map fields flag
            $userFields + $userDefaultFields + $userMappedFields
        ];
        $testCases[] = [
            'user',
            $userInputDataWithAuthor,
            true,   // recursive flag
            false,  // map fields flag
            $userFields + $userDefaultFields + ['Author' => $authorFields + $authorDefaultFields]
        ];
        $testCases[] = [
            'user',
            $userInputDataWithAuthor,
            true,   // recursive flag
            true,   // map fields flag
            $userFields + $userDefaultFields + $userMappedFields + [
                'Author' => $authorFields + $authorDefaultFields + $authorMappedFields
            ]
        ];

        // TEST author -> User (owning side)
        $testCases[] = [
            'author',
            $authorInputDataWithUser,
            false,  // recursive flag
            false,  // map fields flag
            $authorFields + $authorDefaultFields
        ];
        $testCases[] = [
            'author',
            $authorInputDataWithUser,
            false,  // recursive flag
            true,   // map fields flag
            $authorFields + $authorDefaultFields + $authorMappedFields
        ];
        $testCases[] = [
            'author',
            $authorInputDataWithUser,
            true,   // recursive flag
            false,  // map fields flag
            $authorFields + $authorDefaultFields + ['User' => $userFields + $userDefaultFields]
        ];
        $testCases[] = [
            'author',
            $authorInputDataWithUser,
            true,   // recursive flag
            true,   // map fields flag
            $authorFields + $authorDefaultFields + $authorMappedFields + [
                'User' => $userFields + $userDefaultFields + $userMappedFields
            ]
        ];

        return $testCases;
    }


    /**
     * @return array[]
     */
    private function provideToFromArrayOneToManyTestCases()
    {
        $authorDefaultFields = self::getDefaultFields('author');

        $authorOneWithFields = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'doe@example.com'
        ];
        $authorOneWithMappedFields = ['initials' => 'jdo'];

        $authorTwoWithFields = [
            'firstname' => 'Larry',
            'lastname' => 'Potter',
            'email' => 'lpt@example.com'
        ];
        $authorTwoWithMappedFields = ['initials' => 'lpt'];

        $authorThreeFields = [
            'firstname' => 'Sue',
            'lastname' => 'Tiger',
            'email' => 'sti@example.com'
        ];
        $authorThreeWithMappedFields = ['initials' => 'sti'];


        $authorInputDataWithEditor = $authorOneWithFields + $authorOneWithMappedFields + [
            'Editor' => array_merge($authorTwoWithMappedFields, $authorTwoWithFields)
            ];

        $editorInputDataWithAuthor = $authorOneWithFields + $authorOneWithMappedFields + [
            'Author' => [
                array_merge($authorTwoWithMappedFields, $authorTwoWithFields),
                array_merge($authorThreeWithMappedFields, $authorThreeFields)
            ]
            ];

        $testCases = [];

        // TEST author -> Editor (owning side)
        $testCases[] = [
            'author',
            $authorInputDataWithEditor,
            false,  // recursive flag
            false,  // map fields flag
            array_merge($authorDefaultFields, $authorOneWithFields)
        ];
        $testCases[] = [
            'author',
            $authorInputDataWithEditor,
            false,  // recursive flag
            true,   // map fields flag
            array_merge($authorDefaultFields, $authorOneWithFields, $authorOneWithMappedFields)
        ];
        $testCases[] = [
            'author',
            $authorInputDataWithEditor,
            true,   // recursive flag
            false,  // map fields flag
            $authorOneWithFields + $authorDefaultFields + [
                'Editor' => array_merge($authorDefaultFields, $authorTwoWithFields, ['Author' => [false]])
            ]
        ];
        $testCases[] = [
            'author',
            $authorInputDataWithEditor,
            true,   // recursive flag
            true,   // map fields flag
            $authorOneWithFields + $authorDefaultFields + $authorOneWithMappedFields + [
                'Editor' => array_merge(
                    $authorDefaultFields,
                    $authorTwoWithFields,
                    $authorTwoWithMappedFields,
                    ['Author' => [false]]
                )
            ]
        ];

        // TEST on author table
        $testCases[] = [
            'author',
            $editorInputDataWithAuthor,
            false,  // recursive flag
            false,  // map fields flag
            $authorOneWithFields + $authorDefaultFields
        ];
        $testCases[] = [
            'author',
            $editorInputDataWithAuthor,
            false,  // recursive flag
            true,   // map fields flag
            array_merge($authorDefaultFields, $authorOneWithFields, $authorOneWithMappedFields)
        ];

        $testCases[] = [
            'author',
            $editorInputDataWithAuthor,
            true,   // recursive flag
            false,  // map fields flag
            $authorOneWithFields + $authorDefaultFields + [
                'Author' => [
                    array_merge($authorDefaultFields, $authorTwoWithFields),
                    array_merge($authorDefaultFields, $authorThreeFields)
                ]
            ]
        ];
        $testCases[] = [
            'author',
            $editorInputDataWithAuthor,
            true,   // recursive flag
            true,   // map fields flag
            array_merge($authorDefaultFields, $authorOneWithFields, $authorOneWithMappedFields) + [
                'Author' => [
                    array_merge($authorDefaultFields, $authorTwoWithFields, $authorTwoWithMappedFields),
                    array_merge($authorDefaultFields, $authorThreeFields, $authorThreeWithMappedFields)
                ]
            ]
        ];

        return $testCases;
    }


    /**
     * @dataProvider provideFromArrayOneToOneNoneExistingRecords
     * @param string $tableName
     * @param array  $recordGraph
     * @param string $relationName
     */
    public function testFromArrayOneToOneNoneExistingRecords($tableName, array $recordGraph, $relationName)
    {
        $rm = self::createDefaultRecordManager();
        $table = $rm->getTable($tableName);
        /** @var Record $record */
        $record = $table->createRecord();
        $record->fromArray($recordGraph);

        $relation = $table->getRelation($relationName);
        $expectedNotNull = isset($recordGraph[$relationName]);
        $relatedByRelation = $relation->getReferenceFor($record, $relationName);
        $relatedByRecord = $record->get($relationName);

        $this->assertNotNull($expectedNotNull);
        $this->assertEquals($relatedByRelation, $relatedByRecord);

        if ($expectedNotNull) {
            $this->assertRelationReferences($record, $relationName, [$relatedByRelation]);
        }
    }


    /**
     * @return array[]
     */
    public function provideFromArrayOneToOneNoneExistingRecords()
    {
        $testCases = [];

        $testCases[] = [
            'tableName' => 'user',
            'recordGraph' => [
                'username' => 'CarlH',
                'password' => 'my-secret',
                'Author' => [
                    'firstname' => 'Carl',
                    'lastname' => 'Hanson',
                    'email' => 'c.hanson@example.com'
                ]
            ],
            'relationName' => 'Author'
        ];

        $testCases[] = [
            'tableName' => 'user',
            'recordGraph' => [
                'username' => 'CarlH',
                'password' => 'my-secret',
                'Author' => [
                    'firstname' => 'Carl',
                    'lastname' => 'Hanson',
                    'email' => 'c.hanson@example.com'
                ]
            ],
            'relationName' => 'Author'
        ];

        $testCases[] = [
            'tableName' => 'author',
            'recordGraph' => [
                'firstname' => 'Carl',
                'lastname' => 'Hanson',
                'email' => 'c.hanson@example.com'
            ],
            'relationName' => 'User'
        ];

        $testCases[] = [
            'tableName' => 'user',
            'recordGraph' => [
                'username' => 'CarlH',
                'password' => 'my-secret'
            ],
            'relationName' => 'Author'
        ];

        return $testCases;
    }


    public function testFromArrayExistingRecords()
    {
        $tableRows = [];
        $tableRows['user'] = ['JohnD', 'BartS'];
        $tableRows['author'] = [
            'John Doe' => [
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'j.doe@example.com',
                'User' => 'JohnD'
            ],
        ];
        $tableRows['article'] = [
            'DiveORM released' => [
                'title' => 'Dive ORM Framework released',
                'Author' => 'John Doe',
                'is_published' => true,
                'datetime' => '2013-01-10 13:48:00',
                'Comment' => [
                    'DiveORM released#1' => [
                        'title' => 'Can\'t wait to see more of this...',
                        'User' => 'BartS',
                        'datetime' => '2013-01-15 17:25:00'
                    ]
                ]
            ]
        ];

        $rm = self::createDefaultRecordManager();
        $recordGenerator = self::saveTableRows($rm, $tableRows);

        $recordGraph = [
            Record::FROM_ARRAY_EXISTS_KEY => true,
            'id' => $recordGenerator->getRecordIdFromMap('article', 'DiveORM released'),
            'Author' => [
                Record::FROM_ARRAY_EXISTS_KEY => true,
                'id' => $recordGenerator->getRecordIdFromMap('author', 'John Doe'),
            ],
            'Comment' => [
                [
                    Record::FROM_ARRAY_EXISTS_KEY => true,
                    'id' => $recordGenerator->getRecordIdFromMap('comment', 'DiveORM released#1'),
                ]
            ]
        ];

        $rm = self::createDefaultRecordManager();
        $articleTable = $rm->getTable('article');
        $article = $articleTable->createRecord();
        $article->fromArray($recordGraph);

        $this->assertCount(1, $articleTable->getRepository());
        $this->assertCount(0, $rm->getTable('user')->getRepository());
        $this->assertCount(1, $rm->getTable('author')->getRepository());
        $this->assertCount(1, $rm->getTable('comment')->getRepository());
    }


    /**
     * @param  string $tableName
     * @return array
     */
    private static function getDefaultFields($tableName)
    {
        $schema = self::getSchema();

        $defaultFields = [];
        $authorFields = $schema->getTableFields($tableName);
        foreach ($authorFields as $fieldName => $fieldData) {
            $defaultFields[$fieldName] = isset($fieldData['default']) ? $fieldData['default'] : null;
        }
        return $defaultFields;
    }


}
