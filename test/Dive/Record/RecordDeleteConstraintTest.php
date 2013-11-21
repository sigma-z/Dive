<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Record;

use Dive\Platform\PlatformInterface;
use Dive\Record\Generator\RecordGenerator;
use Dive\TestSuite\TableRowsProvider;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 15.11.13
 */
class RecordOneToManyDeleteTest extends TestCase
{

    const NOT_REFERENCED = 0;
    const REFERENCED = 1;
    const NESTED_REFERENCED = 2;

    /** @var array */
    private static $tableRows = array();

    /** @var RecordGenerator */
    private static $recordGenerator;


    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$tableRows = TableRowsProvider::provideTableRows();
        $rm = self::createDefaultRecordManager();
        self::$recordGenerator = self::saveTableRows($rm, self::$tableRows);
    }


//    /**
//     * @param string $side
//     * @dataProvider provideRelationSides
//     */
//    public function testDeleteOnNonSavedRecordsOwningSide($side)
//    {
//        $rm = self::createDefaultRecordManager();
//
//        /** @var User $user */
//        $user = $rm->getRecord('user', array());
//        /** @var Author $author */
//        $author = $rm->getRecord('author', array());
//        /** @var Article $article */
//        $article = $rm->getRecord('article', array());
//        $author->Article[] = $article;
//        $user->Author = $author;
//
//        if ($side == self::RELATION_SIDE_REFERENCED) {
//            $recordToDelete = $author;
//        }
//        else {
//            $recordToDelete = $article;
//        }
//        $rm->delete($recordToDelete);
//        $this->assertRecordIsNotScheduledForDelete($author);
//        $this->assertRecordIsNotScheduledForDelete($user);
//    }
    /**
     * @dataProvider provideDelete
     * @param string $tableName
     * @param string $key
     * @param int    $referenced
     * @param string $constraint
     * @param string $nestedConstraint
     */
    public function testDelete($tableName, $key, $referenced, $constraint, $nestedConstraint)
    {
        $pk = self::$recordGenerator->getRecordIdFromMap($tableName, $key);
        $rm = $this->getRecordManagerWithOverWrittenConstraints($tableName, $constraint, $nestedConstraint);
        $record = $rm->getTable($tableName)->findByPk($pk);
        $this->assertInstanceOf('\Dive\Record', $record);

        $isConstraintRestricted = self::isRestrictedConstraint($constraint);
        if (($referenced == self::REFERENCED || $referenced == self::NESTED_REFERENCED) && $isConstraintRestricted) {
            $this->setExpectedException('\Dive\UnitOfWork\UnitOfWorkException');
        }
        if ($referenced == self::NESTED_REFERENCED && $constraint == PlatformInterface::CASCADE) {
            $isNestedConstraintRestricted = self::isRestrictedConstraint($nestedConstraint);
            if ($isNestedConstraintRestricted) {
                $this->setExpectedException('\Dive\UnitOfWork\UnitOfWorkException');
            }
        }
        $rm->delete($record);
    }


    /**
     * TODO define more tests
     * @return array[]
     */
    public function provideDelete()
    {
        $testCases = array();

        //                   tableName      alias           referenced
        $testCases[] = array('user',        'JohnD',        self::NESTED_REFERENCED);

        $combinedTestCases = array();
        $combinedConstraints = $this->getCombinedConstraints();
        foreach ($testCases as $testCase) {
            foreach ($combinedConstraints as $constraintCombination) {
                $combinedTestCases[] = array_merge($testCase, $constraintCombination);
            }
        }
        return $combinedTestCases;
    }


    /**
     * @return array[]
     */
    public function getCombinedConstraints()
    {
        $constraints = array(
            PlatformInterface::CASCADE,
            PlatformInterface::SET_NULL,
            PlatformInterface::NO_ACTION,
            PlatformInterface::RESTRICT
        );

        $combinedConstraints = array();
        foreach ($constraints as $constraint) {
            foreach ($constraints as $nestedConstraint) {
                $combinedConstraints[] = array($constraint, $nestedConstraint);
            }
        }
        return $combinedConstraints;
    }


    /**
     * @param  string $tableName
     * @param  string $constraint
     * @param  string $nestedConstraint
     * @return \Dive\RecordManager
     */
    protected function getRecordManagerWithOverWrittenConstraints($tableName, $constraint, $nestedConstraint)
    {
        $nestedRelatedTableNames = array();
        $schemaDefinition = self::getSchemaDefinition();
        foreach ($schemaDefinition['relations'] as &$relation) {
            if ($relation['refTable'] == $tableName) {
                $relation['onDelete'] = $constraint;
                if ($relation['owningTable'] != $tableName) {
                    $nestedRelatedTableNames[] = $relation['owningTable'];
                }
            }
        }
        foreach ($schemaDefinition['relations'] as &$relation) {
            if (in_array($relation['refTable'], $nestedRelatedTableNames)) {
                $relation['onDelete'] = $nestedConstraint;
            }
        }
        return self::createDefaultRecordManager($schemaDefinition);
    }


    /**
     * @param  string $constraint
     * @return bool
     */
    private static function isRestrictedConstraint($constraint)
    {
        return in_array($constraint, array(PlatformInterface::RESTRICT, PlatformInterface::NO_ACTION));
    }

}
