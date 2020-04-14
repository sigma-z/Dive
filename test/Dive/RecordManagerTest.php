<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test;

use Dive\Hydrator\HydratorInterface;
use Dive\RecordManager;
use Dive\Schema\DataTypeMapper\DataTypeMapper;
use Dive\Table\Behavior\TimestampableBehavior;
use Dive\TestSuite\Model\User;
use Dive\TestSuite\Record\Record;
use Dive\TestSuite\TestCase;
use Dive\UnitOfWork\UnitOfWork;
use Dive\Validation\FieldValidator\FieldValidator;
use Dive\Validation\RecordInvalidException;
use Dive\Validation\ValidationContainer;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 24.11.12
 */
class RecordManagerTest extends TestCase
{

    /** @var RecordManager */
    private $rm;


    protected function setUp()
    {
        parent::setUp();

        $this->rm = self::createDefaultRecordManager();
    }


    public function testCreatedRecordManager()
    {
        $this->assertInstanceOf('\Dive\RecordManager', $this->rm);
    }


    public function testGetTable()
    {
        $table = $this->rm->getTable('user');
        $this->assertInstanceOf('\Dive\Table', $table);
    }


    public function testGetTableRepository()
    {
        $repository = $this->rm->getTableRepository('user');
        $this->assertInstanceOf('\Dive\Table\Repository', $repository);
    }


    /**
     * @expectedException \Dive\Schema\SchemaException
     */
    public function testGetNotExistingTable()
    {
        $this->rm->getTable('notexistingtable');
    }


    public function testGetConnection()
    {
        $this->assertInstanceOf('Dive\Connection\Connection', $this->rm->getConnection());
    }


    public function testClearTables()
    {
        $this->assertCount(0, self::readAttribute($this->rm, 'tables'));
        $this->rm->getTable('user');
        $this->assertCount(1, self::readAttribute($this->rm, 'tables'));
        $this->rm->getTable('author');
        $this->assertCount(2, self::readAttribute($this->rm, 'tables'));
        $this->rm->getTable('author');
        $this->assertCount(2, self::readAttribute($this->rm, 'tables'));
        $this->rm->clearTables();
        $this->assertCount(0, self::readAttribute($this->rm, 'tables'));
    }


    /**
     * @param string $hydratorName
     * @param string $expectedHydratorClassName
     *
     * @dataProvider provideGetDiveDefinedHydrator
     */
    public function testGetDiveDefinedHydrator($hydratorName, $expectedHydratorClassName)
    {
        $collHydrator = $this->rm->getHydrator($hydratorName);
        $this->assertInstanceOf($expectedHydratorClassName, $collHydrator);
    }


    /**
     * @return array
     */
    public function provideGetDiveDefinedHydrator()
    {
        return array(
            array(RecordManager::FETCH_RECORD_COLLECTION, '\Dive\Hydrator\RecordCollectionHydrator'),
            array(RecordManager::FETCH_RECORD,            '\Dive\Hydrator\RecordHydrator'),
            array(RecordManager::FETCH_ARRAY,             '\Dive\Hydrator\ArrayHydrator'),
            array(RecordManager::FETCH_SINGLE_ARRAY,      '\Dive\Hydrator\SingleArrayHydrator'),
            array(RecordManager::FETCH_SCALARS,           '\Dive\Hydrator\ScalarHydrator'),
            array(RecordManager::FETCH_SINGLE_SCALAR,     '\Dive\Hydrator\SingleScalarHydrator'),
        );
    }


    /**
     * @expectedException \Dive\Exception
     */
    public function testGetDiveDefinedHydratorNotExistingException()
    {
        $this->rm->getHydrator('notexistingname');
    }


    public function testGetSchema()
    {
        $this->assertInstanceOf('\Dive\Schema\Schema', self::readAttribute($this->rm, 'schema'));
    }


    public function testSetCustomHydrator()
    {
        /** @var HydratorInterface $customHydrator */
        $customHydrator = $this->getMockForAbstractClass(HydratorInterface::class);
        $this->rm->setHydrator('custom', $customHydrator);
        $actualCustomHydrator = $this->rm->getHydrator('custom');
        self::assertSame($customHydrator, $actualCustomHydrator);
    }


    /**
     * @expectedException \Dive\Schema\SchemaException
     */
    public function testTableNotFoundException()
    {
        $this->rm->getTable('notexistingtablename');
    }


    public function testGetTableWithBehavior()
    {
        $tableName = 'article';
        // initializes article table and instances TimestampableBehavior as shared instance
        $this->rm->getTable($tableName);

        $tableBehaviors = self::readAttribute($this->rm, 'tableBehaviors');
        $this->assertCount(1, $tableBehaviors);
        /** @var TimestampableBehavior $timestampableBehavior */
        $timestampableBehavior = current($tableBehaviors);
        $this->assertInstanceOf('\Dive\Table\Behavior\TimestampableBehavior', $timestampableBehavior);

        $eventDispatcher = $this->rm->getEventDispatcher();
        $this->assertCount(1, $eventDispatcher->getListeners(Record::EVENT_PRE_SAVE));
        $this->assertCount(1, $eventDispatcher->getListeners(Record::EVENT_PRE_UPDATE));
        $this->assertCount(1, $eventDispatcher->getListeners(Record::EVENT_PRE_INSERT));
    }


    public function testHasAConfiguredValidationContainer()
    {
        $rm = self::createDefaultRecordManager();
        $validationContainer = $rm->getRecordValidationContainer();
        $this->assertNotNull($validationContainer);
        $this->assertInstanceOf('\Dive\Validation\ValidationContainer', $validationContainer);

        $uniqueValidator = $validationContainer->getValidator(ValidationContainer::VALIDATOR_UNIQUE_CONSTRAINT);
        $this->assertNotNull($uniqueValidator);
        $this->assertInstanceOf('\Dive\Validation\UniqueValidator\UniqueRecordValidator', $uniqueValidator);

        /** @var FieldValidator $fieldTypeValidator */
        $fieldTypeValidator = $validationContainer->getValidator(ValidationContainer::VALIDATOR_FIELD);
        $this->assertNotNull($fieldTypeValidator);
        $this->assertInstanceOf('\Dive\Validation\FieldValidator\FieldValidator', $fieldTypeValidator);
        $booleanOrmDataTypeValidator = $fieldTypeValidator->getDataTypeValidator(DataTypeMapper::OTYPE_BOOLEAN);
        $this->assertInstanceOf('\Dive\Schema\OrmDataType\BooleanOrmDataType', $booleanOrmDataTypeValidator);
    }


    public function testCommitInvalidRecordSaveWillResetScheduledForCommitData()
    {
        $rm = self::createDefaultRecordManager();
        $userTable = $rm->getTable('user');
        /** @var User $user */
        $user = $userTable->createRecord();
        $user->username = 'test';

        $rm->scheduleSave($user);
        try {
            $rm->commit();
            $this->fail('Invalid record should have thrown exception.');
        }
        catch (RecordInvalidException $e) {
            /** @var UnitOfWork $unitOfWork */
            $unitOfWork = self::readAttribute($rm, 'unitOfWork');
            $this->assertFalse($unitOfWork->isRecordScheduledForCommit($user, UnitOfWork::OPERATION_SAVE));
        }
    }

}
