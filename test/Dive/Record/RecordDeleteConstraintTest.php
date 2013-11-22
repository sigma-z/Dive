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
use Dive\Record;
use Dive\RecordManager;
use Dive\TestSuite\Constraint\RecordScheduleConstraint;
use Dive\TestSuite\Model\Article;
use Dive\TestSuite\Model\Author;
use Dive\TestSuite\Model\User;
use Dive\TestSuite\TableRowsProvider;
use Dive\TestSuite\TestCase;
use Dive\UnitOfWork\UnitOfWork;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 15.11.13
 */
class RecordDeleteConstraintTest extends TestCase
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


    /**
     * @param string $tableName
     * @throws \Exception
     * @dataProvider provideDeleteOnNonSavedRecords
     */
    public function testDeleteOnNonSavedRecordsOwningSide($tableName)
    {
        $rm = self::createDefaultRecordManager();

        /** @var User $user */
        $user = $rm->getRecord('user', array());
        /** @var Author $author */
        $author = $rm->getRecord('author', array());
        /** @var Article $article */
        $article = $rm->getRecord('article', array());
        $author->Article[] = $article;
        $user->Author = $author;

        switch ($tableName) {
            case 'user':
                $recordToDelete = $user;
                break;

            case 'author':
                $recordToDelete = $author;
                break;

            case 'article':
                $recordToDelete = $article;
                break;

            default:
                throw new \Exception("Test for $tableName is not supported!");
        }

        $rm->delete($recordToDelete);
        $this->assertRecordIsNotScheduledForDelete($author);
        $this->assertRecordIsNotScheduledForDelete($user);
        $this->assertRecordIsNotScheduledForDelete($article);
    }


    /**
     * @return array
     */
    public function provideDeleteOnNonSavedRecords()
    {
        return array(
            array('user'),
            array('author'),
            array('article'),
        );
    }


    /**
     * @dataProvider provideDelete
     * @param string $tableName
     * @param string $recordKey
     * @param array  $referencesByLevel
     * @param array  $constraints
     */
    public function testDelete(
        $tableName,
        $recordKey,
        array $referencesByLevel,
        array $constraints
    )
    {
        $pk = self::$recordGenerator->getRecordIdFromMap($tableName, $recordKey);
        $rm = $this->getRecordManagerWithOverWrittenConstraints($tableName, $constraints);
        $record = $rm->getTable($tableName)->findByPk($pk);
        $this->assertInstanceOf('\Dive\Record', $record);

        $constraint = $constraints[0];

        $isConstraintRestricted = self::isRestrictedConstraint($constraint);
        $expectException = false;
        if ($isConstraintRestricted && ($referencesByLevel[0] > 0 || $referencesByLevel[1] > 0)) {
            $expectException = true;
            $this->setExpectedException('\Dive\UnitOfWork\UnitOfWorkException');
        }
        if ($referencesByLevel[1] > 0 && ($constraint == PlatformInterface::CASCADE)) {
            $nestedConstraint = $constraints[1];
            $isNestedConstraintRestricted = self::isRestrictedConstraint($nestedConstraint);
            if ($isNestedConstraintRestricted) {
                $expectException = true;
            }
        }

        if ($expectException) {
            $this->setExpectedException('\Dive\UnitOfWork\UnitOfWorkException');
        }
        $rm->delete($record);

        $expectedCounts = self::getExpectedScheduledOperationForCommitCount($constraints, $referencesByLevel);
        $this->assertScheduledOperationForCommitCount($expectedCounts, $rm);
    }


    /**
     * TODO define more tests
     * NOTE records are referenced by TableRowsProvider::provideTableRows()
     *
     * @return array[]
     */
    public function provideDelete()
    {
        $testCases = array();

        //
        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'referencesByLevel' => array(1, 2, 3)
        );
        $testCases[] = array(
            'tableName' => 'author',
            'recordKey' => 'John Doe',
            'referencesByLevel' => array(2, 3)
        );

        $combinedTestCases = array();
        $combinedConstraints = $this->getCombinedConstraints();
        foreach ($testCases as $testCase) {
            foreach ($combinedConstraints as $constraintCombination) {
                $combinedTestCases[] = array_merge($testCase, array($constraintCombination));
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
     * @param  array  $constraints
     * @return \Dive\RecordManager
     */
    protected function getRecordManagerWithOverWrittenConstraints($tableName, array $constraints)
    {
        $schemaDefinition = self::getSchemaDefinition();
        self::processSchemaConstraints($schemaDefinition, $tableName, 'onDelete', $constraints);
        return self::createDefaultRecordManager($schemaDefinition);
    }


    /**
     * @param array  $schemaDefinition
     * @param string $tableName
     * @param string $constraintType
     * @param array  $constraints
     * @param array  $processedTables
     */
    private static function processSchemaConstraints(
        &$schemaDefinition,
        $tableName,
        $constraintType,
        array $constraints,
        array &$processedTables = array()
    )
    {
        if (in_array($tableName, $processedTables) || empty($constraints)) {
            return;
        }

        $constraint = array_shift($constraints);
        if ($constraint == PlatformInterface::CASCADE && empty($constraints)) {
            $constraints[] = PlatformInterface::CASCADE;
        }
        $processedTables[] = $tableName;
        foreach ($schemaDefinition['relations'] as &$relation) {
            if ($relation['refTable'] == $tableName) {
                $relation[$constraintType] = $constraint;
                if ($relation['owningTable'] != $tableName) {
                    self::processSchemaConstraints(
                        $schemaDefinition, $relation['owningTable'], $constraintType, $constraints, $processedTables
                    );
                }
            }
        }
    }


    /**
     * @param  string $constraint
     * @return bool
     */
    private static function isRestrictedConstraint($constraint)
    {
        return in_array($constraint, array(PlatformInterface::RESTRICT, PlatformInterface::NO_ACTION));
    }


    /**
     * @param Record $record
     * @param string $message
     */
    protected function assertRecordIsScheduledForDelete(Record $record, $message = '')
    {
        self::assertThat($record, self::isScheduledFor(UnitOfWork::OPERATION_DELETE), $message);
    }


    /**
     * @param  string $operation
     * @return RecordScheduleConstraint
     */
    private static function isScheduledFor($operation)
    {
        return new RecordScheduleConstraint($operation);
    }


    /**
     * @param Record $record
     * @param string $message
     */
    protected function assertRecordIsNotScheduledForDelete(Record $record, $message = '')
    {
        self::assertThat($record, self::logicalNot(self::isScheduledFor(UnitOfWork::OPERATION_DELETE)), $message);
    }


    /**
     * @param array         $expectedCounts
     * @param RecordManager $rm
     * @param string        $message
     * @throws \PHPUnit_Framework_Exception
     */
    protected function assertScheduledOperationForCommitCount(array $expectedCounts, RecordManager $rm, $message = '')
    {
        if (empty($expectedCounts)) {
            throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'not empty array');
        }
        /** @var UnitOfWork $unitOfWork */
        $unitOfWork = self::readAttribute($rm, 'unitOfWork');
        /** @var string[] $scheduledForCommit */
        $scheduledForCommit = self::readAttribute($unitOfWork, 'scheduledForCommit');
        $operationCounts = array();
        foreach ($expectedCounts as $operation => $count) {
            $operationCounts[$operation] = 0;
        }
        foreach ($scheduledForCommit as $operation) {
            if (isset($operationCounts[$operation])) {
                $operationCounts[$operation]++;
            }
        }

        $this->assertEquals($expectedCounts, $operationCounts, $message);
    }


    /**
     * @param  array $constraints
     * @param  array $referencesByLevel
     * @return array
     */
    private function getExpectedScheduledOperationForCommitCount(array $constraints, array $referencesByLevel)
    {
        $constraint = $constraints[0];
        $nestedConstraint = $constraints[1];

        $expectedCounts = array(
            UnitOfWork::OPERATION_DELETE => 0,
            UnitOfWork::OPERATION_SAVE => 0
        );
        if ($constraint == PlatformInterface::CASCADE) {
            $expectedCounts[UnitOfWork::OPERATION_DELETE] += $referencesByLevel[0] + 1;
            if ($nestedConstraint == PlatformInterface::CASCADE) {
                $expectedCounts[UnitOfWork::OPERATION_DELETE] = array_sum($referencesByLevel) + 1;
            }
            else if ($nestedConstraint == PlatformInterface::SET_NULL) {
                $expectedCounts[UnitOfWork::OPERATION_SAVE] += $referencesByLevel[1];
            }
        }
        else if ($constraint == PlatformInterface::SET_NULL) {
            $expectedCounts[UnitOfWork::OPERATION_SAVE] += $referencesByLevel[0];
            $expectedCounts[UnitOfWork::OPERATION_DELETE] += 1;
        }
        return $expectedCounts;
    }

}
