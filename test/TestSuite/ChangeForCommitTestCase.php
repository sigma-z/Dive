<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\TestSuite;

use Dive\Platform\PlatformInterface;
use Dive\Record;
use Dive\RecordManager;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 01.11.13
 */
class ChangeForCommitTestCase extends TestCase
{

    const CONSTRAINT_TYPE_ON_DELETE = 'onDelete';
    const CONSTRAINT_TYPE_ON_UPDATE = 'onUpdate';


    /**
     * @var array
     */
    protected $tableRows = array();


    /**
     * @return array
     */
    public function provideConstraints()
    {
        return array(
            array(PlatformInterface::CASCADE),
            array(PlatformInterface::SET_NULL),
            array(PlatformInterface::NO_ACTION),
            array(PlatformInterface::RESTRICT)
        );
    }


    /**
     * @return array
     */
    public function provideRestrictConstraints()
    {
        return array(
            array(PlatformInterface::NO_ACTION),
            array(PlatformInterface::RESTRICT)
        );
    }


    /**
     * @param Record $record
     * @param string $message
     */
    protected function assertRecordIsScheduledForDelete(Record $record, $message = '')
    {
        $rm = $record->getRecordManager();
        $tableName = $record->getTable()->getTableName();
        if (!$message) {
            $message = 'Record ' . $tableName . ' has to be scheduled for delete';
        }
        $this->assertTrue($rm->isRecordScheduledForDelete($record), $message);
    }


    /**
     * @param Record $record
     * @param string $message
     */
    protected function assertRecordIsScheduledForSave(Record $record, $message = '')
    {
        $rm = $record->getRecordManager();
        $tableName = $record->getTable()->getTableName();
        if (!$message) {
            $message = 'Record ' . $tableName . ' has to be scheduled for save';
        }
        $this->assertTrue($rm->isRecordScheduledForSave($record), $message);
    }


    /**
     * @param Record $record
     * @param string $message
     */
    protected function assertRecordIsNotScheduledForDelete(Record $record, $message = '')
    {
        $rm = $record->getRecordManager();
        $tableName = $record->getTable()->getTableName();
        if (!$message) {
            $message = 'Record ' . $tableName . ' has not to be scheduled for delete';
        }
        $this->assertFalse($rm->isRecordScheduledForDelete($record), $message);
    }


    /**
     * @param Record $record
     * @param string $message
     */
    protected function assertRecordIsNotScheduledForSave(Record $record, $message = '')
    {
        $rm = $record->getRecordManager();
        $tableName = $record->getTable()->getTableName();
        if (!$message) {
            $message = 'Record ' . $tableName . ' has not to be scheduled for save';
        }
        $this->assertFalse($rm->isRecordScheduledForSave($record), $message);
    }


    /**
     * @param  string $type
     * @param  string $constraint
     * @return RecordManager
     */
    protected function getRecordManagerWithOverWrittenConstraint($type, $constraint = null)
    {
        $schemaDefinition = TestCase::getSchemaDefinition();
        if ($constraint) {
            $schemaDefinition['relations']['author.user_id'][$type] = $constraint;
        }
        $recordManager = TestCase::createDefaultRecordManager($schemaDefinition);
        if ($constraint) {
            $userTable = $recordManager->getTable('user');
            $relation = $userTable->getRelation('Author');
            $method = "get" . ucfirst($type);
            $this->assertEquals($constraint, $relation->$method());
        }
        return $recordManager;
    }


    /**
     * @param \Dive\RecordManager $rm
     * @param  string             $username
     * @return Record
     */
    protected function createUserWithAuthor(RecordManager $rm, $username)
    {
        $recordGenerator = $this->createRecordGenerator($rm);
        $recordGenerator
            ->setTablesMapField(array('user' => 'username'))
            ->setTablesRows($this->tableRows[$username])
            ->generate();
        $userId = $recordGenerator->getRecordIdFromMap('user', $username);
        $userTable = $rm->getTable('user');
        $user = $userTable->findByPk($userId);
        $this->assertInstanceOf('\Dive\Record', $user);
        /** @noinspection PhpUndefinedFieldInspection */
        $author = $user->Author;
        $this->assertInstanceOf('\Dive\Record', $author);

        $this->assertRecordIsNotScheduledForDelete($author);
        $this->assertRecordIsNotScheduledForDelete($user);
        $this->assertRecordIsNotScheduledForSave($author);
        $this->assertRecordIsNotScheduledForSave($user);

        return $user;
    }
}