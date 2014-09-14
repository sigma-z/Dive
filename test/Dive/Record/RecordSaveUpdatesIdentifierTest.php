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

    /** @var Record */
    private $relatedRecord;

    /** @var string */
    private $oldIdentifierStoredRecord;

    /** @var string */
    private $oldIdentifierRelatedRecord;


    protected function tearDown()
    {
        parent::tearDown();

        if ($this->storedRecord) {
            $this->rm->scheduleDelete($this->storedRecord);
            $this->rm->commit();
        }

        if ($this->relatedRecord) {
            $this->rm->scheduleDelete($this->relatedRecord);
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


    public function testSaveOwningRecordOneToOne()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveAOwningRecordWithAnOneToOneRelatedRecord();

        $this->whenIChangeTheRecordIdentifierTo('123');
        $this->whenIChangeTheRelatedRecordIdentifierTo('321');
        $this->whenISaveTheRecord();

        $this->thenTheRecordIdentifierShouldHaveBeenUpdatedInTheDatabase();
        $this->thenTheRecordIdentifierShouldHaveBeenUpdatedInTheRepository();
        $this->thenTheRelatedRecordIdentifierShouldHaveBeenUpdatedInTheDatabase();
        $this->thenTheRelatedRecordIdentifierShouldHaveBeenUpdatedInTheRepository();

        $this->assertEquals('321', $this->storedRecord->user_id);
    }


    public function testSaveReferencedRecordOneToOne()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveAReferencedRecordWithAnOneToOneRelatedRecord();

        $this->whenIChangeTheRecordIdentifierTo('123');
        $this->whenIChangeTheRelatedRecordIdentifierTo('321');
        $this->whenISaveTheRecord();

        $this->thenTheRecordIdentifierShouldHaveBeenUpdatedInTheDatabase();
        $this->thenTheRecordIdentifierShouldHaveBeenUpdatedInTheRepository();
        $this->thenTheRelatedRecordIdentifierShouldHaveBeenUpdatedInTheDatabase();
        $this->thenTheRelatedRecordIdentifierShouldHaveBeenUpdatedInTheRepository();

        $this->assertEquals('123', $this->relatedRecord->user_id);
    }


    // given / when / then methods
    private function givenIHaveARecordManager()
    {
        $this->rm = self::createDefaultRecordManager();
    }


    private function givenIHaveASingleRecordStored()
    {
        $userTable = $this->rm->getTable('user');
        $user = self::getRecordWithRandomData($userTable, array('id' => '1', 'username' => 'Hugo'));
        $this->rm->scheduleSave($user);
        $this->rm->commit();

        $this->oldIdentifierStoredRecord = $user->getIdentifierAsString();
        $this->storedRecord = $user;
    }


    private function givenIHaveAOwningRecordWithAnOneToOneRelatedRecord()
    {
        $userTable = $this->rm->getTable('user');
        $user = self::getRecordWithRandomData($userTable, array('id' => '1', 'username' => 'Hugo'));
        $authorTable = $this->rm->getTable('author');
        $authorData = array('id' => '1', 'lastname' => 'Smith', 'email' => 'smith@example.com');
        $author = self::getRecordWithRandomData($authorTable, $authorData);
        $author->User = $user;
        $this->rm->scheduleSave($author);
        $this->rm->commit();

        $this->oldIdentifierStoredRecord = $author->getIdentifierAsString();
        $this->oldIdentifierRelatedRecord = $user->getIdentifierAsString();
        $this->storedRecord = $author;
        $this->relatedRecord = $user;
    }


    private function givenIHaveAReferencedRecordWithAnOneToOneRelatedRecord()
    {
        $userTable = $this->rm->getTable('user');
        $user = self::getRecordWithRandomData($userTable, array('id' => '1', 'username' => 'Hugo'));
        $authorTable = $this->rm->getTable('author');
        $authorData = array('id' => '1', 'lastname' => 'Smith', 'email' => 'smith@example.com');
        $author = self::getRecordWithRandomData($authorTable, $authorData);
        $user->Author = $author;
        $this->rm->scheduleSave($user);
        $this->rm->commit();

        $this->oldIdentifierStoredRecord = $user->getIdentifierAsString();
        $this->oldIdentifierRelatedRecord = $author->getIdentifierAsString();
        $this->storedRecord = $user;
        $this->relatedRecord = $author;
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


    /**
     * @param  string $id
     * @throws \Dive\Table\TableException
     */
    private function whenIChangeTheRelatedRecordIdentifierTo($id)
    {
        $this->relatedRecord->set('id', $id);
    }


    private function thenTheRecordIdentifierShouldHaveBeenUpdatedInTheDatabase()
    {
        $identifier = $this->storedRecord->getIdentifierAsString();
        $table = $this->storedRecord->getTable();
        $isStoredInDatabase = $table->createQuery()->where('id = ?', $identifier)->hasResult();
        $this->assertTrue($isStoredInDatabase);
    }


    private function thenTheRelatedRecordIdentifierShouldHaveBeenUpdatedInTheDatabase()
    {
        $identifier = $this->relatedRecord->getIdentifierAsString();
        $table = $this->relatedRecord->getTable();
        $isStoredInDatabase = $table->createQuery()->where('id = ?', $identifier)->hasResult();
        $this->assertTrue($isStoredInDatabase);
    }


    private function thenTheRecordIdentifierShouldHaveBeenUpdatedInTheRepository()
    {
        $identifier = $this->storedRecord->getIdentifierAsString();
        $table = $this->storedRecord->getTable();
        $repository = $table->getRepository();
        $this->assertFalse($repository->hasByInternalId($this->oldIdentifierStoredRecord));
        $this->assertTrue($repository->hasByInternalId($identifier));
    }


    private function thenTheRelatedRecordIdentifierShouldHaveBeenUpdatedInTheRepository()
    {
        $identifier = $this->relatedRecord->getIdentifierAsString();
        $table = $this->relatedRecord->getTable();
        $repository = $table->getRepository();
        $this->assertFalse($repository->hasByInternalId($this->oldIdentifierRelatedRecord));
        $this->assertTrue($repository->hasByInternalId($identifier));
    }

}
