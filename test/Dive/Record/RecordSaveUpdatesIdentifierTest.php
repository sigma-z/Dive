<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Record;

use Dive\Record;
use Dive\TestSuite\TestCase;

/**
 * Class RecordSaveUpdatesIdentifierTest
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 12.09.2014
 */
class RecordSaveUpdatesIdentifierTest extends TestCase
{

    /** @var Record */
    private $storedRecord;

    /** @var string */
    private $oldIdentifier;


    public function testSaveSingleRecord()
    {
        $this->markTestIncomplete();
        $this->givenIHaveASingleRecordStored();
        $this->whenIChangeTheRecordIdentifierTo('123');
        $this->whenISaveTheRecord();
        $this->thenTheRecordIdentifierShouldHaveBeenUpdatedInTheDatabase();
        $this->thenTheRecordIdentifierShouldHaveBeenUpdatedInTheRepository();
    }


    private function givenIHaveASingleRecordStored()
    {
        $rm = self::createDefaultRecordManager();
        $userTable = $rm->getTable('user');
        $user = self::getRecordWithRandomData($userTable, array('user' => 'Hugo'));
        $rm->scheduleSave($user);
        $rm->commit();

        $this->storedRecord = $user;
    }


    /**
     * @param  string $identifier
     * @throws \Dive\Table\TableException
     */
    private function whenIChangeTheRecordIdentifierTo($identifier)
    {
        $this->oldIdentifier = $this->storedRecord->get('id');
        $this->storedRecord->set('id', $identifier);
    }


    private function whenISaveTheRecord()
    {
        $rm = $this->storedRecord->getRecordManager();
        $rm->scheduleSave($this->storedRecord);
        $rm->commit();
    }


    private function thenTheRecordIdentifierShouldHaveBeenUpdatedInTheDatabase()
    {
        $identifier = $this->storedRecord->getIdentifierAsString();
        $table = $this->storedRecord->getTable();
        $isStoredInDatabase = $table->createQuery()->where('id = ?', $identifier)->hasResult();
        $this->assertTrue($isStoredInDatabase);
    }


    private function thenTheRecordIdentifierShouldHaveBeenUpdatedInTheRepository()
    {
        $identifier = $this->storedRecord->getIdentifierAsString();
        $table = $this->storedRecord->getTable();
        $repository = $table->getRepository();
        $this->assertFalse($repository->hasByInternalId($this->oldIdentifier));
        $this->assertTrue($repository->hasByInternalId($identifier));
    }

}
