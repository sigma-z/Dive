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
use Dive\Record;
use Dive\Record\Generator\RecordGenerator;
use Dive\RecordManager;
use Dive\Relation\ReferenceMap;
use Dive\TestSuite\TableRowsProvider;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 30.08.13
 */
class RecordDeleteTest extends TestCase
{

    /** @var RecordGenerator */
    protected static $recordGenerator;


    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $tableRows = TableRowsProvider::provideTableRows();
        $rm = self::createDefaultRecordManager();
        self::$recordGenerator = self::saveTableRows($rm, $tableRows);
    }


    /**
     * @dataProvider provideDelete
     *
     * @param array $deleteRecordKeys
     * @param array $expectedDeleteRecordKeys
     * @param array $expectedSaveRecordKeys
     */
    public function testDelete(array $deleteRecordKeys, array $expectedDeleteRecordKeys, array $expectedSaveRecordKeys)
    {
        $rm = self::createDefaultRecordManager();

        foreach ($deleteRecordKeys as $deleteRecordKey => $tableName) {
            $table = $rm->getTable($tableName);
            $record = $this->getGeneratedRecord(self::$recordGenerator, $table, $deleteRecordKey);

            // load related references to test that the identifier is removed from references and related record collections
            $relations = $table->getRelations();
            $references = array_combine(array_keys($relations), array_fill(0, count($relations), true));
            $record->loadReferences($references);

            $rm->delete($record);
        }

        $expectedSaveRecords = $this->getRecordsForRecordKeys($rm, $expectedSaveRecordKeys);
        $expectedDeleteRecords = $this->getRecordsForRecordKeys($rm, $expectedDeleteRecordKeys);
        $deleteIdMap = array();
        foreach ($expectedDeleteRecords as $deleteRecordKey => $deleteRecord) {
            $deleteIdMap[$deleteRecordKey] = $deleteRecord->getInternalId();
        }
        $this->assertScheduledRecordsForCommit($rm, $expectedSaveRecords, $expectedDeleteRecords);

        $rm->commit();

        // TODO fix reference maps and related record collections!
        $this->markTestIncomplete("Fix reference maps and related record collections!");

        $this->assertRecordsDeleted($expectedDeleteRecords);

        // assert that no record is scheduled for commit anymore
        $this->assertScheduledOperationsForCommit($rm, 0, 0);
    }


    /**
     * @return array[]
     */
    public function provideDelete()
    {
        $testCases = array();

        $testCases[] = array(
            'deleteRecordKeys' => array(
                'DiveORM released' => 'article',
                'helloWorld' => 'article',
                'JohnD' => 'user'
            ),
            'expectedDeleteRecordKeys' => array(
                'DiveORM released#1' => 'comment',
                'DiveORM released#News' => 'article2tag',
                'DiveORM released#Release Notes' => 'article2tag',
                'DiveORM released' => 'article',
                'helloWorld' => 'article',
                'John Doe' => 'author',
                'JohnD' => 'user',
            ),
            'expectedSaveRecordKeys' => array(
                'Bart Simon' => 'author'
            )
        );

        return $testCases;
    }


    /**
     * @param  RecordManager $rm
     * @param  array         $recordKeys keys: record keys; values: table names
     * @return Record[]      recordKey as keys
     */
    private function getRecordsForRecordKeys(RecordManager $rm, array $recordKeys)
    {
        $records = array();
        foreach ($recordKeys as $recordKey => $tableName) {
            $table = $rm->getTable($tableName);
            $record = $this->getGeneratedRecord(self::$recordGenerator, $table, $recordKey);
            $records[$recordKey] = $record;
        }
        return $records;
    }


    /**
     * @param Record[] $expectedDeletedRecords
     */
    private function assertRecordsDeleted(array $expectedDeletedRecords)
    {
        foreach ($expectedDeletedRecords as $record) {
            $table = $record->getTable();
            $identifier = $record->getIdentifier();
            $result = $table->findByPk($identifier);
            $message = "Record with id: " . (is_array($identifier) ? implode(', ', $identifier) : $identifier)
                . " was not deleted from table '" . $table->getTableName() . "'!";
            $this->assertFalse($result, $message);

            $identifierAsString = $record->getIdentifierAsString();
            $this->assertFalse($table->isInRepository($identifierAsString));

            $this->assertRecordNotReferenced($record);
        }
    }


    /**
     * @param Record $record
     */
    private function assertRecordNotReferenced(Record $record)
    {
        $internalId = $record->getInternalId();
        $table = $record->getTable();
        $relations = $table->getRelations();
        $message = "Record with id '$internalId' in table '" . $table->getTableName() . "'";
        foreach ($relations as $relationName => $relation) {
            $this->assertRecordNotReferencedByRelation($record, $relationName, $message);
        }
    }


    /**
     * TODO assert that owningFieldOidMapping is not referenced, too
     *
     * @param Record $record
     * @param string $relationName
     * @param string $message
     */
    private function assertRecordNotReferencedByRelation(Record $record, $relationName, $message = '')
    {
        $oid = $record->getOid();
        $internalId = $record->getInternalId();
        $relation = $record->getTableRelation($relationName);
        $isOwningSide = $relation->isOwningSide($record, $relationName);
        /** @var ReferenceMap $referenceMap */
        $referenceMap = self::readAttribute($relation, 'map');

        /** @var RecordCollection[] $relatedCollections */
        $relatedCollections = self::readAttribute($referenceMap, 'relatedCollections');

        $references = $referenceMap->getMapping();
        $referencedMessage = $message . " expected not be referenced (relation '$relationName')";
        $relatedCollectionMessage = $message
            . " expected not to be by a related collection (relation '$relationName')";

        if ($isOwningSide) {
            $isOneToMany = $relation->isOneToMany();
            foreach ($references as $owningIds) {
                if ($isOneToMany) {
                    $this->assertFalse(in_array($internalId, $owningIds), $referencedMessage);
                }
                else {
                    $this->assertNotEquals($internalId, $owningIds, $referencedMessage);
                }
            }

            if ($isOneToMany) {
                foreach ($relatedCollections as $relatedCollection) {
                    $this->assertFalse($relatedCollection->has($internalId), $relatedCollectionMessage);
                }
            }
        }
        else {
            $this->assertArrayNotHasKey($internalId, $references, $referencedMessage);
            $this->assertArrayNotHasKey($oid, $relatedCollections, $relatedCollectionMessage);
        }
    }


//    /**
//     * @var array
//     */
//    protected $tableRows = array(
//        'JohnD' => array(
//            'user' => array('JohnD'),
//            'author' => array(
//                'John' => array(
//                    'firstname' => 'John',
//                    'lastname' => 'Doe',
//                    'email' => 'jdo@example.com',
//                    'User' => 'JohnD'
//                )
//            )
//        ),
//        'SallyK' => array(
//            'user' => array('SallyK'),
//            'author' => array(
//                'SallyK' => array(
//                    'firstname' => 'Sally',
//                    'lastname' => 'Kingston',
//                    'email' => 'ski@example.com',
//                    'User' => 'SallyK'
//                )
//            )
//        )
//    );
//
//
//    /**
//     * @param string $side
//     * @dataProvider provideRelationSides
//     */
//    public function testDeleteOnNonSavedRecordsOwningSide($side)
//    {
//        $rm = $this->getRecordManagerWithOverWrittenConstraint(self::CONSTRAINT_TYPE_ON_DELETE, 'author.user_id');
//
//        $user = $rm->getRecord('user', array());
//        $author = $rm->getTable('author')->createRecord();
//        $user->Author = $author;
//
//        if ($side == self::RELATION_SIDE_REFERENCED) {
//            $recordToDelete = $user;
//        }
//        else {
//            $recordToDelete = $author;
//        }
//        $rm->delete($recordToDelete);
//        $this->assertRecordIsNotScheduledForDelete($author);
//        $this->assertRecordIsNotScheduledForDelete($user);
//    }
//
//
//    /**
//     * @param string $side
//     * @dataProvider provideRelationSides
//     */
//    public function testDeleteSetNullConstraintOwningSide($side)
//    {
//        $rm = $this->getRecordManagerWithOverWrittenConstraint(
//            self::CONSTRAINT_TYPE_ON_DELETE, 'author.user_id', PlatformInterface::SET_NULL
//        );
//        $user = $this->createUserWithAuthor($rm, 'JohnD');
//        $author = $user->Author;
//
//
//        if ($side == self::RELATION_SIDE_REFERENCED) {
//            $rm->delete($user);
//            $this->assertRecordIsScheduledForSave($author);
//
//            $this->assertRecordIsScheduledForDelete($user);
//            $this->assertNull($author->user_id);
//
//            $deleteSecond = $author;
//        }
//        else {
//            // does not touch user record
//            $rm->delete($author);
//            $this->assertRecordIsScheduledForDelete($author);
//
//            $this->assertRecordIsNotScheduledForDelete($user);
//            $this->assertNotNull($author->user_id);
//
//            $deleteSecond = $user;
//        }
//
//        $rm->delete($deleteSecond);
//        $this->assertRecordIsScheduledForDelete($user);
//        $this->assertRecordIsScheduledForDelete($author);
//    }
//
//
//    /**
//     * deleting the author, user stays untouched
//     * both records have to be deleted
//     */
//    public function testDeleteCascadeConstraintReferencedSide()
//    {
//        $rm = $this->getRecordManagerWithOverWrittenConstraint(
//            self::CONSTRAINT_TYPE_ON_DELETE, 'author.user_id', PlatformInterface::CASCADE
//        );
//
//        $user = $this->createUserWithAuthor($rm, 'JohnD');
//        $author = $user->Author;
//
//        $this->assertRecordIsNotScheduledForDelete($author);
//        $this->assertRecordIsNotScheduledForDelete($user);
//        $rm->delete($user);
//
//        $this->assertFalse($author->isFieldModified('user_id'));
//        $this->assertRecordIsScheduledForDelete($user);
//        $this->assertRecordIsScheduledForDelete($author);
//
//        $rm->delete($author);
//        $this->assertRecordIsScheduledForDelete($author);
//    }
//
//
//    /**
//     */
//    public function testDeleteCascadeConstraintOwningSide()
//    {
//        $rm = $this->getRecordManagerWithOverWrittenConstraint(
//            self::CONSTRAINT_TYPE_ON_DELETE, 'author.user_id', PlatformInterface::CASCADE
//        );
//
//        $user = $this->createUserWithAuthor($rm, 'JohnD');
//        $author = $user->Author;
//
//        $rm->delete($author);
//        $this->assertRecordIsScheduledForDelete($author);
//        $this->assertRecordIsNotScheduledForDelete($user);
//    }
//
//
//    /**
//     * @param string $constraint
//     * @dataProvider provideConstraints
//     */
//    public function testDeleteThrowsExceptionForScheduleSaveAfterDelete($constraint)
//    {
//        $rm = $this->getRecordManagerWithOverWrittenConstraint(
//            self::CONSTRAINT_TYPE_ON_DELETE, 'author.user_id', $constraint
//        );
//        $user = $this->createUserWithAuthor($rm, 'JohnD');
//        $rm->delete($user->Author);
//        $this->assertRecordIsScheduledForDelete($user->Author);
//        $this->assertRecordIsNotScheduledForDelete($user);
//
//        // when the record is scheduled for delete it can't be saved again
//        $this->setExpectedException('\Dive\UnitOfWork\UnitOfWorkException');
//        $rm->save($user->Author);
//    }
//
//
//    /**
//     * @param string $side
//     * @dataProvider provideRelationSides
//     */
//    public function testRecordWithModifiedReferenceOnReferenceSide($side = 'reference')
//    {
//        $rm = $this->getRecordManagerWithOverWrittenConstraint(
//            self::CONSTRAINT_TYPE_ON_DELETE, 'author.user_id', PlatformInterface::CASCADE
//        );
//
//        $userJohn = $this->createUserWithAuthor($rm, 'JohnD');
//        $authorJohn = $userJohn->Author;
//        $userSally = $this->createUserWithAuthor($rm, 'SallyK');
//        $authorSally = $userSally->Author;
//
//        if ($side == self::RELATION_SIDE_REFERENCED) {
//            $recordToBeDeleted = $userJohn;
//            $authorWithoutUser = $authorJohn;
//            $recordNotToBeDeleted = $authorJohn;
//            $recordToBeDeleted->Author = $authorSally;
//        }
//        else {
//            $recordToBeDeleted = $authorJohn;
//            $authorWithoutUser = $authorSally;
//            $recordNotToBeDeleted = $userJohn;
//            $authorJohn->User = $userSally;
//        }
//        $rm->delete($recordToBeDeleted);
//        $this->assertRecordIsScheduledForDelete($recordToBeDeleted);
//        $this->assertNull($authorWithoutUser->get('user_id'));
//        $this->assertRecordIsNotScheduledForDelete($recordNotToBeDeleted);
//
//        $this->assertRecordIsNotScheduledForDelete($userSally);
//        $this->assertRecordIsNotScheduledForDelete($authorSally);
//    }
//
//
//    /**
//     * @dataProvider provideConstraints
//     */
//    public function testRecordWithoutOwningSide($constraint)
//    {
//        $rm = $this->getRecordManagerWithOverWrittenConstraint(
//            self::CONSTRAINT_TYPE_ON_DELETE, 'author.user_id', $constraint
//        );
//
//        $userData = array('username' => 'JohnD', 'password' => 'secret');
//        $user = $rm->getTable('user')->createRecord($userData);
//        $rm->save($user);
//        $rm->commit();
//
//        $relation = $user->getTableRelation('Author');
//        $this->assertFalse($relation->hasReferenceLoadedFor($user, 'Author'));
//
//        $rm->delete($user);
//        $this->assertRecordIsScheduledForDelete($user);
//    }

}
