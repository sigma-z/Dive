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
use Dive\Event\Dispatcher;
use Dive\Platform\PlatformInterface;
use Dive\Record\FieldValueChangeEvent;
use Dive\Record\Generator\RecordGenerator;
use Dive\Table;
use Dive\TestSuite\Constraint\RecordScheduleConstraint;
use Dive\TestSuite\Record\Record;
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
     * @param int    $expectedCountScheduledForSave
     */
    public function testUpdateCascadeConstraint(
        $tableName, $recordKey, array $relationsToLoad, $expectedCountScheduledForSave
    )
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraints($tableName, array(PlatformInterface::CASCADE));
        // clean event dispatcher with no listeners
        $rm->getConnection()->setEventDispatcher(new Dispatcher());
        $eventDispatcher = $rm->getEventDispatcher();
        $table = $rm->getTable($tableName);

        $record = $this->getGeneratedRecord(self::$recordGenerator, $table, $recordKey);
        $record->loadReferences($relationsToLoad);
        $this->modifyRecordGraphConstraintFields($record);

        $fieldValueChangeListener = function (FieldValueChangeEvent $event) {
            /** @var Record $record */
            $record = $event->getRecord();
            $record->markFieldAsModified($event->getFieldName());
        };
        $eventDispatcher->addListener(Record::EVENT_PRE_FIELD_VALUE_CHANGE, $fieldValueChangeListener);

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
            'expectedCountScheduledForSave' => 4
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JohnD',
            'relationsToLoad' => array(),
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
            'expectedCountScheduledForSave' => 9
        );

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JamieTK',
            'relationsToLoad' => array(
                'Author' => array(
                    'Article' => array(
                        'Comment' => array(),
                        'Article2tagHasMany' => array()
                    )
                )
            ),
            'expectedCountScheduledForSave' => 9
        );

        return $testCases;
    }


    /**
     * @dataProvider provideUpdateSetNullConstraint
     * @param string $tableName
     * @param string $recordKey
     * @param array  $relationsToLoad
     * @param array  $constraints
     * @param int    $expectedCountScheduledForSave
     */
    public function testUpdateSetNullConstraint(
        $tableName, $recordKey, array $relationsToLoad, array $constraints, $expectedCountScheduledForSave
    )
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraints($tableName, $constraints);
        // clean event dispatcher with no listeners
        $rm->getConnection()->setEventDispatcher(new Dispatcher());
        $eventDispatcher = $rm->getEventDispatcher();
        $table = $rm->getTable($tableName);

        $record = $this->getGeneratedRecord(self::$recordGenerator, $table, $recordKey);
        $record->loadReferences($relationsToLoad);
        $this->modifyRecordGraphConstraintFields($record);

        $fieldValueChangeListener = function (FieldValueChangeEvent $event) {
            /** @var Record $record */
            $record = $event->getRecord();
            $record->markFieldAsModified($event->getFieldName());
        };
        $eventDispatcher->addListener(Record::EVENT_PRE_FIELD_VALUE_CHANGE, $fieldValueChangeListener);

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
    public function provideUpdateSetNullConstraint()
    {
        $testCases = array();

        $testCases[] = array(
            'tableName' => 'user',
            'recordKey' => 'JamieTK',
            'relationsToLoad' => array(
                'Author' => array()
            ),
            'constraints' => array(PlatformInterface::CASCADE, PlatformInterface::SET_NULL),
            'expectedCountScheduledForSave' => 5
        );

        return $testCases;
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
        $rm = self::createDefaultRecordManager($schemaDefinition);



        return $rm;
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
