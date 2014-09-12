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
use Dive\RecordManager;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 12.09.2014
 */
class RecordSaveUpdatesIdentifierTest extends TestCase
{

    /** @var RecordManager */
    private $rm;

    /** @var Record */
    private $storedRecord;

    /** @var string */
    private $oldIdentifier;


    protected function tearDown()
    {
        parent::tearDown();

        if ($this->storedRecord) {
            $this->rm->scheduleDelete($this->storedRecord);
            $this->rm->commit();
        }
    }


    public function testSaveSingleRecord()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveASingleRecordStored();
        $this->whenIChangeTheRecordIdentifierTo('123');
        $this->whenISaveTheRecord();
        $this->thenTheRecordIdentifierShouldHaveBeenUpdatedInTheDatabase();
        $this->thenTheRecordIdentifierShouldHaveBeenUpdatedInTheRepository();
    }


    private function givenIHaveASingleRecordStored()
    {
        $userTable = $this->rm->getTable('user');
        $user = self::getRecordWithRandomData($userTable, array('id' => '1', 'username' => 'Hugo'));
        $this->rm->scheduleSave($user);
        $this->rm->commit();

        $this->oldIdentifier = $user->getIdentifierAsString();
        $this->storedRecord = $user;
    }


    /**
     * @param  string $identifier
     * @throws \Dive\Table\TableException
     */
    private function whenIChangeTheRecordIdentifierTo($identifier)
    {
        $this->storedRecord->set('id', $identifier);
    }


    private function whenISaveTheRecord()
    {
        $this->rm->scheduleSave($this->storedRecord);
        $this->rm->commit();
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


    private function givenIHaveARecordManager()
    {
        $this->rm = self::createDefaultRecordManager();
    }

}
