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
use Dive\Event\EventDispatcher;
use Dive\Model;
use Dive\Platform\PlatformInterface;
use Dive\Record\FieldValueChangeEvent;
use Dive\RecordManager;
use Dive\Table;
use Dive\TestSuite\ConstraintTestCase;
use Dive\TestSuite\Record\Record;


/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 05.12.13
 */
class RecordUpdateConstraintTest extends ConstraintTestCase
{

    /**
     * @dataProvider provideUpdateRestrictedConstraint
     * @expectedException \Dive\UnitOfWork\UnitOfWorkException
     *
     * @param string $tableName
     * @param string $recordKey
     * @param array  $relationsToLoad
     * @param array  $constraints
     */
    public function testUpdateRestrictedConstraint($tableName, $recordKey, array $relationsToLoad, array $constraints)
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraints($tableName, 'onUpdate', $constraints);
        $table = $rm->getTable($tableName);

        $record = $this->getGeneratedRecord(self::$recordGenerator, $table, $recordKey);
        $record->loadReferences($relationsToLoad);
        $this->modifyRecordGraphConstraintFields($record);

        $rm->scheduleSave($record);
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
     *
     * @param string $tableName
     * @param string $recordKey
     * @param array  $relationsToLoad
     * @param int    $expectedCountScheduledForSave
     */
    public function testUpdateCascadeConstraint(
        $tableName, $recordKey, array $relationsToLoad, $expectedCountScheduledForSave
    )
    {
        $rm = $this->getRecordManagerWithOverWrittenConstraints(
            $tableName, 'onUpdate', array(PlatformInterface::CASCADE)
        );
        $table = $rm->getTable($tableName);

        $record = $this->getGeneratedRecord(self::$recordGenerator, $table, $recordKey);
        $record->loadReferences($relationsToLoad);
        $this->modifyRecordGraphConstraintFields($record);

        // clean event dispatcher with no listeners
        $this->addPreFieldValueChangeEventListener($rm);

        $rm->scheduleSave($record);

        $this->assertScheduledOperationsForCommit($rm, $expectedCountScheduledForSave, 0);
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
            'expectedCountScheduledForSave' => 5
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
            'expectedCountScheduledForSave' => 8
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
     *
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
        $rm = $this->getRecordManagerWithOverWrittenConstraints($tableName, 'onUpdate', $constraints);
        $table = $rm->getTable($tableName);

        $record = $this->getGeneratedRecord(self::$recordGenerator, $table, $recordKey);
        $record->loadReferences($relationsToLoad);
        $this->modifyRecordGraphConstraintFields($record);

        // clean event dispatcher with no listeners
        $this->addPreFieldValueChangeEventListener($rm);

        $rm->scheduleSave($record);

        $this->assertScheduledOperationsForCommit($rm, $expectedCountScheduledForSave, 0);
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
            'relationsToLoad' => array(),
            'constraints' => array(PlatformInterface::SET_NULL),
            'expectedCountScheduledForSave' => 4
        );

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
     * @param \Dive\Record $record
     * @param array        $visited
     */
    private function modifyRecordGraphConstraintFields(\Dive\Record $record, array &$visited = array())
    {
        if ($record instanceof Model) {
            $record = $record->getRecord();
        }

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

        $idFields = $table->getIdentifierFields();
        foreach ($idFields as $idField) {
            $record->markFieldAsModified($idField);
        }
    }


    /**
     * @param RecordManager $rm
     */
    private function addPreFieldValueChangeEventListener(RecordManager $rm)
    {
        $fieldValueChangeListener = function (FieldValueChangeEvent $event) {
            /** @var Record $record */
            $record = $event->getRecord();
            $record->markFieldAsModified($event->getProperty());
        };
        // clean event dispatcher with no listeners
        $rm->getConnection()->setEventDispatcher(new EventDispatcher());
        $eventDispatcher = $rm->getEventDispatcher();
        $eventDispatcher->addListener(Record::EVENT_PRE_FIELD_VALUE_CHANGE, $fieldValueChangeListener);
    }

}
