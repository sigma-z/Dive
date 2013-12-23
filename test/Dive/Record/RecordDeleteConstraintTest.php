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
use Dive\Record;
use Dive\RecordManager;
use Dive\TestSuite\ConstraintTestCase;
use Dive\TestSuite\Model\Article;
use Dive\TestSuite\Model\Author;
use Dive\TestSuite\Model\User;
use Dive\UnitOfWork\UnitOfWork;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 15.11.13
 */
class RecordDeleteConstraintTest extends ConstraintTestCase
{

    /**
     * @param string $tableName
     * @throws \Exception
     * @dataProvider provideDeleteOnNonSavedRecords
     */
    public function testDeleteOnNonSavedRecords($tableName)
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

        $this->assertScheduledOperationsForCommit($rm, 0, 0);
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
     * @dataProvider provideDeleteRestrictedConstraint
     * @expectedException \Dive\UnitOfWork\UnitOfWorkException
     *
     * @param string $tableName
     * @param string $recordKey
     * @param array  $constraints
     */
    public function testDeleteRestrictedConstraint($tableName, $recordKey, array $constraints)
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraints($tableName, 'onDelete', $constraints);
        $table = $rm->getTable($tableName);
        $record = $this->getGeneratedRecord(self::$recordGenerator, $table, $recordKey);
        $rm->delete($record);
    }


    /**
     * @return array[]
     */
    public function provideDeleteRestrictedConstraint()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'constraints' => array(PlatformInterface::RESTRICT)
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'constraints' => array(PlatformInterface::CASCADE, PlatformInterface::RESTRICT)
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'constraints' => array(PlatformInterface::CASCADE, PlatformInterface::CASCADE, PlatformInterface::RESTRICT)
        );

        $testCases[] = array(
            'tableName' => 'author',
            'recordKey' => 'John Doe',
            'constraints' => array(PlatformInterface::RESTRICT)
        );

        $testCases[] = array(
            'tableName' => 'tag',
            'recordKey' => 'News',
            'constraints' => array(PlatformInterface::RESTRICT)
        );

        $testCases[] = array(
            'tableName' => 'article',
            'recordKey' => 'DiveORM released',
            'constraints' => array(PlatformInterface::RESTRICT)
        );

        return $testCases;
    }


    /**
     * @dataProvider provideDeleteCascadeConstraint
     *
     * @param string $tableName
     * @param string $recordKey
     * @param int    $expectedCountScheduledDeletes
     */
    public function testDeleteCascadeConstraint(
        $tableName, $recordKey, $expectedCountScheduledDeletes
    )
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraints(
            $tableName, 'onDelete', array(PlatformInterface::CASCADE)
        );
        $table = $rm->getTable($tableName);
        $record = $this->getGeneratedRecord(self::$recordGenerator, $table, $recordKey);
        $rm->delete($record);

        $this->assertScheduledOperationsForCommit($rm, $expectedCountScheduledDeletes, 0);
    }


    /**
     * @return array[]
     */
    public function provideDeleteCascadeConstraint()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'expectedCountScheduledDeletes' => 7
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JamieTK',
            'expectedCountScheduledDeletes' => 9
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'AdamE',
            'expectedCountScheduledDeletes' => 3
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'BartS',
            'expectedCountScheduledDeletes' => 3
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'CelineH',
            'expectedCountScheduledDeletes' => 1
        );

        return $testCases;
    }


    /**
     * @dataProvider provideDeleteSetNullConstraint
     *
     * @param string $tableName
     * @param string $recordKey
     * @param array  $constraints
     * @param int    $expectedCountScheduledDeletes
     * @param int    $expectedCountScheduledSaves
     */
    public function testDeleteSetNullConstraint(
        $tableName, $recordKey, array $constraints, $expectedCountScheduledDeletes, $expectedCountScheduledSaves
    )
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraints($tableName, 'onDelete', $constraints);
        $table = $rm->getTable($tableName);
        $record = $this->getGeneratedRecord(self::$recordGenerator, $table, $recordKey);
        $rm->delete($record);

        $this->assertScheduledOperationsForCommit($rm, $expectedCountScheduledDeletes, $expectedCountScheduledSaves);
    }


    /**
     * @return array[]
     */
    public function provideDeleteSetNullConstraint()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'constraints' => array(PlatformInterface::SET_NULL),
            'expectedCountScheduledDeletes' => 1,
            'expectedCountScheduledSaves' => 1
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'constraints' => array(PlatformInterface::CASCADE, PlatformInterface::SET_NULL),
            'expectedCountScheduledDeletes' => 2,
            'expectedCountScheduledSaves' => 2
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'constraints' => array(PlatformInterface::CASCADE, PlatformInterface::CASCADE, PlatformInterface::SET_NULL),
            'expectedCountScheduledDeletes' => 4,
            'expectedCountScheduledSaves' => 3
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JamieTK',
            'constraints' => array(PlatformInterface::CASCADE, PlatformInterface::SET_NULL),
            'expectedCountScheduledDeletes' => 4,
            'expectedCountScheduledSaves' => 1
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JamieTK',
            'constraints' => array(PlatformInterface::CASCADE, PlatformInterface::CASCADE, PlatformInterface::SET_NULL),
            'expectedCountScheduledDeletes' => 5,
            'expectedCountScheduledSaves' => 4
        );

        return $testCases;
    }


    /**
     * @param RecordManager $rm
     * @param int           $expectedCountScheduledDeletes
     * @param int           $expectedCountScheduledSaves
     */
    private function assertScheduledOperationsForCommit($rm, $expectedCountScheduledDeletes, $expectedCountScheduledSaves)
    {
        /** @var UnitOfWork $unitOfWork */
        $unitOfWork = self::readAttribute($rm, 'unitOfWork');
        /** @var string[] $scheduledForCommit */
        $scheduledForCommit = self::readAttribute($unitOfWork, 'scheduledForCommit');
        $actualCountScheduledDeletes = 0;
        $actualCountScheduledSaves = 0;
        foreach ($scheduledForCommit as $operation) {
            if ($operation == UnitOfWork::OPERATION_DELETE) {
                $actualCountScheduledDeletes++;
            }
            else if ($operation == UnitOfWork::OPERATION_SAVE) {
                $actualCountScheduledSaves++;
            }
        }
        $this->assertEquals($expectedCountScheduledDeletes, $actualCountScheduledDeletes);
        $this->assertEquals($expectedCountScheduledSaves, $actualCountScheduledSaves);
    }
}
