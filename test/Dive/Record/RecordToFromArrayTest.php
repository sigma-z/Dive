<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Record;

use Dive\RecordManager;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 26.07.13
 */
class RecordToFromArrayTest extends TestCase
{

    /**
     * @dataProvider provideToFromArray
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


    public function provideToFromArray()
    {
        $testCases = array();
        $testCases = array_merge($testCases, $this->provideToFromArrayOneToOneTestCases());
        $testCases = array_merge($testCases, $this->provideToFromArrayOneToManyTestCases());
        return $testCases;
    }


    private function provideToFromArrayOneToOneTestCases()
    {
        $authorDefaultFields = self::getDefaultFields('author');
        $authorFields = array(
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'doe@example.com'
        );
        $authorMappedFields = array('initials' => 'jdo');

        $userDefaultFields = self::getDefaultFields('user');
        $userFields = array(
            'username' => 'John',
            'password' => 'secret',
            'id' => 1234
        );
        $userMappedFields = array('column1' => 'foo', 'column2' => 'bar');

        $userInputDataWithAuthor = $userFields + $userMappedFields + array(
            'Author' => $authorFields + $authorMappedFields
        );
        $authorInputDataWithUser = $authorFields + $authorMappedFields + array(
            'User' => $userFields + $userMappedFields
        );


        $testCases = array();

        // TEST user -> Author (referenced side)
        $testCases[] = array(
            'user',
            $userInputDataWithAuthor,
            false,  // recursive flag
            false,  // map fields flag
            $userFields + $userDefaultFields
        );
        $testCases[] = array(
            'user',
            $userInputDataWithAuthor,
            false,  // recursive flag
            true,   // map fields flag
            $userFields + $userDefaultFields + $userMappedFields
        );
        $testCases[] = array(
            'user',
            $userInputDataWithAuthor,
            true,   // recursive flag
            false,  // map fields flag
            $userFields + $userDefaultFields + array('Author' => $authorFields + $authorDefaultFields)
        );
        $testCases[] = array(
            'user',
            $userInputDataWithAuthor,
            true,   // recursive flag
            true,   // map fields flag
            $userFields + $userDefaultFields + $userMappedFields + array(
                'Author' => $authorFields + $authorDefaultFields + $authorMappedFields
            )
        );

        // TEST author -> User (owning side)
        $testCases[] = array(
            'author',
            $authorInputDataWithUser,
            false,  // recursive flag
            false,  // map fields flag
            $authorFields + $authorDefaultFields
        );
        $testCases[] = array(
            'author',
            $authorInputDataWithUser,
            false,  // recursive flag
            true,   // map fields flag
            $authorFields + $authorDefaultFields + $authorMappedFields
        );
        $testCases[] = array(
            'author',
            $authorInputDataWithUser,
            true,   // recursive flag
            false,  // map fields flag
            $authorFields + $authorDefaultFields + array('User' => $userFields + $userDefaultFields)
        );
        $testCases[] = array(
            'author',
            $authorInputDataWithUser,
            true,   // recursive flag
            true,   // map fields flag
            $authorFields + $authorDefaultFields + $authorMappedFields + array(
                'User' => $userFields + $userDefaultFields + $userMappedFields
            )
        );

        return $testCases;
    }


    private function provideToFromArrayOneToManyTestCases()
    {
        $authorDefaultFields = self::getDefaultFields('author');

        $authorOneFields = array(
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'doe@example.com'
        );
        $authorOneMappedFields = array('initials' => 'jdo');

        $authorTwoFields = array(
            'firstname' => 'Larry',
            'lastname' => 'Potter',
            'email' => 'lpt@example.com'
        );
        $authorTwoMappedFields = array('initials' => 'lpt');

        $authorThreeFields = array(
            'firstname' => 'Sue',
            'lastname' => 'Tiger',
            'email' => 'sti@example.com'
        );
        $authorThreeMappedFields = array('initials' => 'sti');


        $authorInputDataWithEditor = $authorOneFields + $authorOneMappedFields + array(
            'Editor' => array_merge($authorTwoMappedFields, $authorTwoFields)
        );

        $editorInputDataWithAuthor = $authorOneFields + $authorOneMappedFields + array(
            'Author' => array(
                array_merge($authorTwoMappedFields, $authorTwoFields),
                array_merge($authorThreeMappedFields, $authorThreeFields)
            )
        );

        $testCases = array();

        // TEST author -> Editor (owning side)
        $testCases[] = array(
            'author',
            $authorInputDataWithEditor,
            false,  // recursive flag
            false,  // map fields flag
            array_merge($authorDefaultFields, $authorOneFields)
        );
        $testCases[] = array(
            'author',
            $authorInputDataWithEditor,
            false,  // recursive flag
            true,   // map fields flag
            array_merge($authorDefaultFields, $authorOneFields, $authorOneMappedFields)
        );
        $testCases[] = array(
            'author',
            $authorInputDataWithEditor,
            true,   // recursive flag
            false,  // map fields flag
            $authorOneFields + $authorDefaultFields + array(
                'Editor' => array_merge($authorDefaultFields, $authorTwoFields)
            )
        );
        $testCases[] = array(
            'author',
            $authorInputDataWithEditor,
            true,   // recursive flag
            true,   // map fields flag
            $authorOneFields + $authorDefaultFields + $authorOneMappedFields + array(
                'Editor' => array_merge($authorDefaultFields, $authorTwoFields, $authorTwoMappedFields)
            )
        );

        // TEST on author table
        $testCases[] = array(
            'author',
            $editorInputDataWithAuthor,
            false,  // recursive flag
            false,  // map fields flag
            $authorOneFields + $authorDefaultFields
        );
        $testCases[] = array(
            'author',
            $editorInputDataWithAuthor,
            false,  // recursive flag
            true,   // map fields flag
            array_merge($authorDefaultFields, $authorOneFields, $authorOneMappedFields)
        );

        $testCases[] = array(
            'author',
            $editorInputDataWithAuthor,
            true,   // recursive flag
            false,  // map fields flag
            $authorOneFields + $authorDefaultFields + array(
                'Author' => array(
                    array_merge($authorDefaultFields, $authorTwoFields),
                    array_merge($authorDefaultFields, $authorThreeFields)
                )
            )
        );
        $testCases[] = array(
            'author',
            $editorInputDataWithAuthor,
            true,   // recursive flag
            true,   // map fields flag
            array_merge($authorDefaultFields, $authorOneFields, $authorOneMappedFields) + array(
                'Author' => array(
                    array_merge($authorDefaultFields, $authorTwoFields, $authorTwoMappedFields),
                    array_merge($authorDefaultFields, $authorThreeFields, $authorThreeMappedFields)
                )
            )
        );

        return $testCases;
    }


    private static function getDefaultFields($tableName)
    {
        $schema = self::getSchema();

        $defaultFields = array();
        $authorFields = $schema->getTableFields($tableName);
        foreach ($authorFields as $fieldName => $fieldData) {
            $defaultFields[$fieldName] = isset($fieldData['default']) ? $fieldData['default'] : null;
        }
        return $defaultFields;
    }


}