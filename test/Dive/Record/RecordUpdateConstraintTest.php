<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Record;

use Dive\Collection\RecordCollection;
use Dive\Platform\PlatformInterface;
use Dive\Record\Generator\RecordGenerator;
use Dive\Record;
use Dive\Table;
use Dive\TestSuite\Constraint\RecordScheduleConstraint;
use Dive\TestSuite\TableRowsProvider;
use Dive\TestSuite\TestCase;
use Dive\UnitOfWork\UnitOfWork;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 05.12.13
 * @TODO refactor class
 */
class RecordUpdateConstraintTest extends TestCase
{

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
//     * @param string $tableName
//     * @throws \Exception
//     * @dataProvider provideSaveOnNonSavedRecords
//     */
//    public function testSaveOnNonSavedRecords($tableName)
//    {
//        $rm = self::createDefaultRecordManager();
//
//        $userTable = $rm->getTable('user');
//        /** @var User $user */
//        $user = $this->getRecordWithRandomData($userTable, array('username' => 'CliffE'));
//
//        $authorTable = $rm->getTable('author');
//        /** @var Author $author */
//        $author = $this->getRecordWithRandomData($authorTable, array('firstname' => 'Cliff', 'lastname' => 'Eastwood'));
//
//        $articleTable = $rm->getTable('article');
//        /** @var Article $article */
//        $article = $this->getRecordWithRandomData($articleTable, array('title' => 'Cliff was here'));
//
//        $author->Article[] = $article;
//        $user->Author = $author;
//
//        switch ($tableName) {
//            case 'user':
//                $recordToSave = $user;
//                break;
//
//            case 'author':
//                $recordToSave = $author;
//                break;
//
//            case 'article':
//                $recordToSave = $article;
//                break;
//
//            default:
//                throw new \Exception("Test for $tableName is not supported!");
//        }
//
//        $rm->save($recordToSave);
//        $this->assertRecordIsScheduledForSave($user);
//        $this->assertRecordIsScheduledForSave($author);
//        $this->assertRecordIsScheduledForSave($article);
//    }


    /**
     * @return array
     */
    public function provideSaveOnNonSavedRecords()
    {
        return array(
            array('user'),
            array('author'),
            array('article'),
        );
    }


    /**
     * @dataProvider provideUpdateRestrictedConstraint
     * @param string $tableName
     * @param string $recordKey
     * @param array  $relationsToLoad
     * @param array  $constraints
     */
    public function testUpdateRestrictedConstraint($tableName, $recordKey, array $relationsToLoad, array $constraints)
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraints($tableName, $constraints);
        $table = $rm->getTable($tableName);

        $record = $this->getGeneratedRecord(self::$recordGenerator, $table, $recordKey);
        $record->loadReferences($relationsToLoad);
        $this->modifyRecordGraphConstraintFields($record);

        $this->setExpectedException('\\Dive\\UnitOfWork\\UnitOfWorkException');
        $rm->save($record);
    }


    /**
     * @return array[]
     */
    public function provideUpdateRestrictedConstraint()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'relationsToLoad' => array('Author' => array()),
            'constraints' => array(PlatformInterface::RESTRICT)
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'relationsToLoad' => array('Author' => array()),
            'constraints' => array(PlatformInterface::RESTRICT)
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'relationsToLoad' => array('Author' => array()),
            'constraints' => array(PlatformInterface::CASCADE, PlatformInterface::RESTRICT)
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'relationsToLoad' => array(
                'Author' => array(
                    'Article' => array(
                        'Comment' => array()
                    )
                )
            ),
            'constraints' => array(PlatformInterface::CASCADE, PlatformInterface::CASCADE, PlatformInterface::RESTRICT)
        );

        return $testCases;
    }


    /**
     * @dataProvider provideUpdateCascadeConstraint
     * @param string $tableName
     * @param string $recordKey
     * @param array  $relationsToLoad
     * @param array  $constraints
     * @param int    $expectedCountScheduledForSave
     */
    public function testUpdateCascadeConstraint(
        $tableName, $recordKey, array $relationsToLoad, array $constraints, $expectedCountScheduledForSave
    )
    {
        $this->markTestIncomplete();

        $rm = $this->getRecordManagerWithOverWrittenConstraints($tableName, $constraints);
        $table = $rm->getTable($tableName);

        $record = $this->getGeneratedRecord(self::$recordGenerator, $table, $recordKey);
        $record->loadReferences($relationsToLoad);
        $this->modifyRecordGraphConstraintFields($record);

        $rm->save($record);

        /** @var UnitOfWork $unitOfWork */
        $unitOfWork = self::readAttribute($rm, 'unitOfWork');
        /** @var string[] $scheduledForCommit */
        $scheduledForCommit = self::readAttribute($unitOfWork, 'scheduledForCommit');
        $actualCountScheduledForSave = 0;
        foreach ($scheduledForCommit as $operation) {
            if ($operation == UnitOfWork::OPERATION_SAVE) {
                $actualCountScheduledForSave++;
            }
        }
        $this->assertEquals($expectedCountScheduledForSave, $actualCountScheduledForSave);
    }


    /**
     * @return array[]
     */
    public function provideUpdateCascadeConstraint()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'relationsToLoad' => array('Author' => array()),
            'constraints' => array(PlatformInterface::CASCADE),
            'expectedCountScheduledForSave' => 4
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'relationsToLoad' => array(),
            'constraints' => array(PlatformInterface::CASCADE),
            'expectedCountScheduledForSave' => 2
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'relationsToLoad' => array(
                'Author' => array(
                    'Article' => array()
                )
            ),
            'constraints' => array(PlatformInterface::CASCADE),
            'expectedCountScheduledForSave' => 7
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JamieTK',
            'relationsToLoad' => array(
                'Author' => array(
                    'Article' => array()
                )
            ),
            'constraints' => array(PlatformInterface::CASCADE),
            'expectedCountScheduledForSave' => 9
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JamieTK',
            'relationsToLoad' => array(
                'Author' => array(
                    'Article' => array(
                        'Article2tagHasMany' => array(
                            'Tag' => array()
                        )
                    )
                )
            ),
            'constraints' => array(PlatformInterface::CASCADE),
            'expectedCountScheduledForSave' => 9
        );

        return $testCases;
    }


//    /**
//     * @dataProvider provideSave
//     * @param string $tableName
//     * @param array  $saveGraph
//     * @param array  $constraints
//     */
//    public function testSave($tableName, array $saveGraph, array $constraints)
//    {
//        $rm = $this->getRecordManagerWithOverWrittenConstraints($tableName, $constraints);
//        $table = $rm->getTable($tableName);
//        $record = $this->getRecordBySaveGraph($table, $saveGraph);
//
//        $rm->save($record);
//    }


    /**
     * NOTE records are referenced by TableRowsProvider::provideTableRows()
     *
     * @return array[]
     */
    public function provideSave()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'user',
            'saveGraph' => array(
                'recordKey' => 'JohnD',
                'Author' => array(
                    'recordKey' => 'John Doe',
                    'email' => 'Johnny@example.com'
                )
            )
        );

        $combinedTestCases = array();
        $combinedConstraints = $this->getCombinedConstraints();
        foreach ($testCases as $testCase) {
            foreach ($combinedConstraints as $constraintCombination) {
                $testCase['constraints'] = $constraintCombination;
                $combinedTestCases[] = $testCase;
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
            //PlatformInterface::NO_ACTION,
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
        self::processSchemaConstraints($schemaDefinition, $tableName, 'onUpdate', $constraints);
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


//    /**
//     * @param  Table $table
//     * @param  array $saveGraph
//     * @return Record
//     */
//    private function getRecordBySaveGraph(Table $table, array $saveGraph)
//    {
//        $rm = $table->getRecordManager();
//        if (isset($saveGraph['recordKey'])) {
//            $tableName = $table->getTableName();
//            $record = $this->getGeneratedRecord($rm, $tableName, $saveGraph['recordKey']);
//        }
//        else {
//            $record = $table->createRecord();
//        }
//
//        foreach ($saveGraph as $key => $value) {
//            if ($table->hasField($key)) {
//                $record->set($key, $value);
//            }
//            else if ($table->hasRelation($key)) {
//                $relation = $table->getRelation($key);
//                $relatedTable = $relation->getJoinTable($rm, $key);
//
//                // to-one
//                if ($relation->isReferencedSide($key) || $relation->isOneToOne()) {
//                    $value = $value !==  null ? $this->getRecordBySaveGraph($relatedTable, $value) : $value;
//                    $record->set($key, $value);
//                }
//                // to-many
//                else if (!empty($value)) {
//                    $relatedCollection = new RecordCollection($relatedTable);
//                    foreach ($value as $related) {
//                        $relatedCollection->add($this->getRecordBySaveGraph($relatedTable, $related));
//                    }
//                }
//                // to-many empty value
//                else {
//                    $record->set($key, new RecordCollection($relatedTable));
//                }
//            }
//        }
//        return $record;
//    }


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
    protected function assertRecordIsScheduledForSave(Record $record, $message = '')
    {
        self::assertThat($record, self::isScheduledFor(UnitOfWork::OPERATION_SAVE), $message);
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
    protected function assertRecordIsNotScheduledForSave(Record $record, $message = '')
    {
        self::assertThat($record, self::logicalNot(self::isScheduledFor(UnitOfWork::OPERATION_SAVE)), $message);
    }
//
//
//    /**
//     * @param array         $constraints
//     * @param array         $deleteGraph
//     * @param RecordManager $rm
//     * @param string        $message
//     * @throws \PHPUnit_Framework_Exception
//     */
//    protected function assertScheduledOperationsForCommit(
//        array $constraints, array $deleteGraph, RecordManager $rm, $message
//    )
//    {
//        $expectedScheduledForCommit = self::getExpectedScheduledOperationsForCommit($rm, $constraints, $deleteGraph);
//        if (empty($expectedScheduledForCommit)) {
//            throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'not empty array');
//        }
//
//        /** @var UnitOfWork $unitOfWork */
//        $unitOfWork = self::readAttribute($rm, 'unitOfWork');
//        /** @var string[] $scheduledForCommit */
//        $scheduledForCommit = self::readAttribute($unitOfWork, 'scheduledForCommit');
//
//        $actualScheduledForCommit = array(
//            UnitOfWork::OPERATION_DELETE => array(),
//            UnitOfWork::OPERATION_SAVE => array()
//        );
//        foreach ($scheduledForCommit as $oid => $operation) {
//            $actualScheduledForCommit[$operation][] = $oid;
//        }
//
//        sort($expectedScheduledForCommit[UnitOfWork::OPERATION_DELETE]);
//        sort($actualScheduledForCommit[UnitOfWork::OPERATION_DELETE]);
//        sort($expectedScheduledForCommit[UnitOfWork::OPERATION_SAVE]);
//        sort($actualScheduledForCommit[UnitOfWork::OPERATION_SAVE]);
//        $this->assertEquals($expectedScheduledForCommit, $actualScheduledForCommit, $message);
//    }
//
//
//    /**
//     * @param \Dive\RecordManager $rm
//     * @param  array              $constraints
//     * @param  array              $deleteGraph
//     * @return array
//     */
//    private function getExpectedScheduledOperationsForCommit(RecordManager $rm, array $constraints, array $deleteGraph)
//    {
//        $expected = array(
//            UnitOfWork::OPERATION_DELETE => array(),
//            UnitOfWork::OPERATION_SAVE => array()
//        );
//
//        foreach ($deleteGraph as $level => $tableReferences) {
//            foreach ($tableReferences as $tableName => $recordKeys) {
//                foreach ($recordKeys as $recordKey) {
//                    $record = $this->getGeneratedRecord($rm, $tableName, $recordKey);
//                    $operation = self::getExpectedScheduleOperation($level, $constraints);
//                    if ($operation) {
//                        $expected[$operation][] = $record->getOid();
//                    }
//                }
//            }
//        }
//        return $expected;
//    }
//
//
//    /**
//     * @param  string $level
//     * @param  array  $constraints
//     * @return bool|string
//     */
//    private static function getExpectedScheduleOperation($level, array $constraints)
//    {
//        if ($level == 0) {
//            return UnitOfWork::OPERATION_DELETE;
//        }
//
//        $constraintPathIsCascade = self::isCascadingConstraintPath($level, $constraints);
//        $constraint = $level > 1 ? $constraints[1] : $constraints[0];
//
//        if ($constraintPathIsCascade && $constraint == PlatformInterface::CASCADE) {
//            return UnitOfWork::OPERATION_DELETE;
//        }
//        else if ($constraintPathIsCascade && $constraint == PlatformInterface::SET_NULL) {
//            return UnitOfWork::OPERATION_SAVE;
//        }
//        return false;
//    }
//
//
//    /**
//     * @param int   $level
//     * @param array $constraints
//     * @return array
//     */
//    private static function isCascadingConstraintPath($level, array $constraints)
//    {
//        if ($level == 0) {
//            return true;
//        }
//
//        $constraints = array_slice($constraints, 0, $level - 1);
//        foreach ($constraints as $constraint) {
//            if ($constraint != PlatformInterface::CASCADE) {
//                return false;
//            }
//        }
//        return true;
//    }


    /**
     * @param Record $record
     * @param array  $visited
     */
    private function modifyRecordGraphConstraintFields(Record $record, array &$visited = array())
    {
        $oid = $record->getOid();
        if (in_array($oid, $visited)) {
            return;
        }
        $visited[] = $oid;
        $table = $record->getTable();
        $relations = $table->getRelations();
        foreach ($relations as $relationName => $relation) {
            if ($relation->hasReferenceLoadedFor($record, $relationName)) {
                $related = $relation->getReferenceFor($record, $relationName);
                if ($related instanceof RecordCollection) {
                    foreach ($related as $relatedRecord) {
                        $this->modifyRecordGraphConstraintFields($relatedRecord, $visited);
                    }
                }
                else if ($related instanceof Record) {
                    $this->modifyRecordGraphConstraintFields($related, $visited);
                }
            }
        }

        $idFields = $table->getIdentifierAsArray();
        foreach ($idFields as $idField) {
            $record->markFieldAsModified($idField);
        }
    }

}
