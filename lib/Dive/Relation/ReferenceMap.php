<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 31.05.13
 */


namespace Dive\Relation;


use Dive\Collection\RecordCollection;
use Dive\Record;

class ReferenceMap
{

    /**
     * @var Relation
     */
    protected $relation = null;
    /**
     * @var array
     * keys: referenced ids
     * values: owning id (one-to-one) or owning ids (one to many)
     */
    private $references = array();
    /**
     * @var RecordCollection[]
     * keys:  oid (referenced record)
     */
    private $relatedCollections = array();
    /**
     * owning field references
     * @var array
     * keys:   oid (owning record - contains foreign key field)
     * values: oid (referenced not-yet-persisted record)
     */
    private $ownerFieldOidMapping = array();


    public function __construct(Relation $relation)
    {
        $this->relation = $relation;
    }


    public function hasReferenced($id)
    {
        return array_key_exists($id, $this->references);
    }


    public function getOwning($id)
    {
        return $this->references[$id];
    }


    /**
     * Sets reference for a referenced id
     *
     * @param  string       $id
     * @param  array|string $ownerIdentifier
     * @throws \InvalidArgumentException
     */
    public function setReference($id, $ownerIdentifier)
    {
        if ($this->relation->isOneToOne() && !is_string($ownerIdentifier) && $ownerIdentifier !== null) {
            throw new \InvalidArgumentException(
                "One-To-One relation expects referencing identifier to be string!\nYou gave me: "
                . gettype($ownerIdentifier)
            );
        }
        if ($this->relation->isOneToMany() && !is_array($ownerIdentifier)) {
            throw new \InvalidArgumentException(
                "One-To-One relation expects referencing identifier to be array!\nYou gave me: "
                . gettype($ownerIdentifier)
            );
        }

        $this->references[$id] = $ownerIdentifier;
    }


    /**
     * Adds owning id for a referenced id
     *
     * @param  string $id
     * @param  string $ownerIdentifier
     * @param  bool   $checkExistence
     */
    public function addReference($id, $ownerIdentifier, $checkExistence = true)
    {
        if (!$checkExistence || !isset($this->references[$id]) || !in_array($ownerIdentifier, $this->references[$id])) {
            $this->references[$id][] = $ownerIdentifier;
        }
    }


    public function hasFieldMapping($owningOid)
    {
        return isset($this->ownerFieldOidMapping[$owningOid]);
    }


    /**
     * @param string $owningOid
     * @param string $referencedOid
     */
    public function setFieldMapping($owningOid, $referencedOid)
    {
        $this->ownerFieldOidMapping[$owningOid] = $referencedOid;
    }


    /**
     * @param  string $owningOid
     * @return string
     */
    public function getFieldMapping($owningOid)
    {
        return $this->ownerFieldOidMapping[$owningOid];
    }


    public function removeFieldMapping($oid)
    {
        unset($this->ownerFieldOidMapping[$oid]);
    }


    public function setRelatedCollection($referencedOid, RecordCollection $collection)
    {
        $this->relatedCollections[$referencedOid] = $collection;
    }


    /**
     * @param  string $referencedOid
     * @return RecordCollection|Record[]|null
     */
    public function getRelatedCollection($referencedOid)
    {
        return isset($this->relatedCollections[$referencedOid]) ? $this->relatedCollections[$referencedOid] : null;
    }


//    /**
//     * TODO to be moved!
//     * @param Record $record
//     * @param string $oldRefId
//     */
//    private function removeOwningReferenceForeignKey(Record $record, $oldRefId)
//    {
//        if ($oldRefId === null || !isset($this->references[$oldRefId])) {
//            return;
//        }
//        if ($this->relation->isOneToMany()) {
//            $owningId = $record->getInternalIdentifier();
//            if (isset($this->references[$oldRefId])) {
//                $pos = array_search($owningId, $this->references[$oldRefId]);
//                if ($pos !== false) {
//                    array_splice($this->references[$oldRefId], $pos, 1);
//                }
//            }
//
//            $refRepository = $this->getRefRepository($record, $this->relation->getOwnerAlias());
//            $oldRefRecord = $refRepository->getByInternalId($oldRefId);
//            if ($oldRefRecord) {
//                $relatedCollection = $this->getRelatedCollection($oldRefRecord);
//                // TODO exception, or if not set create one??
//                if ($relatedCollection) {
//                    $relatedCollection->remove($owningId);
//                }
//            }
//        }
//        else {
//            $this->references[$oldRefId] = null;
//        }
//    }


    public function getMapping()
    {
        return $this->references;
    }


    /**
     * Clears reference map
     */
    public function clear()
    {
        $this->references = array();
        $this->relatedCollections = array();
        $this->ownerFieldOidMapping = array();
    }






    // -------------------
    // TODO check methods
    // -------------------

//    /**
//     * Get relation referenced repository
//     *
//     * @param  Record $record
//     * @param  string $relationName
//     * @return \Dive\Table\Repository
//     */
//    private function getRefRepository(Record $record, $relationName)
//    {
//        $rm = $record->getTable()->getRecordManager();
//        $tableName = $this->relation->isOwningSide($relationName)
//            ? $this->relation->getReferencedTable()
//            : $this->relation->getOwnerTable();
//        $refTable = $rm->getTable($tableName);
//        return $refTable->getRepository();
//    }
//
//
//    private function updateOwningFieldOidMapping(Record $owningRecord, Record $referencedRecord = null)
//    {
//        $oid = $owningRecord->getOid();
//        if ($referencedRecord && !$referencedRecord->exists()) {
//            $this->ownerFieldOidMapping[$oid] = $referencedRecord->getOid();
//        }
//        else {
//            unset($this->ownerFieldOidMapping[$oid]);
//        }
//    }
//
//
//    private function getOldReferencedId(Record $owningRecord)
//    {
//        $oid = $owningRecord->getOid();
//        $oldRefId = $owningRecord->getModifiedFieldValue($this->relation->getOwnerField());
//        if (!$oldRefId && isset($this->ownerFieldOidMapping[$oid])) {
//            $oldRefId = Record::NEW_RECORD_ID_MARK . $this->ownerFieldOidMapping[$oid];
//        }
//        return $oldRefId;
//    }
//
//
//    private function updateReference($owningId, Record $referencedRecord)
//    {
//        $refId = $referencedRecord->getInternalIdentifier();
//        // add new referenced record id
//        if ($this->relation->isOneToOne()) {
//            $this->setReference($refId, $owningId);
//        }
//        else if ($owningId) {
//            $this->addReference($refId, $owningId);
//        }
//    }
//
//
//    /**
//     * @param  Record $referencedRecord
//     * @return RecordCollection|Record[]|null
//     */
//    private function getRelatedCollection(Record $referencedRecord)
//    {
//        $oid = $referencedRecord->getOid();
//        return isset($this->relatedCollections[$oid]) ? $this->relatedCollections[$oid] : null;
//    }
//
//
//    /**
//     * Updates record references for given owning record and given referenced record
//     *
//     * @param Record $owningRecord
//     * @param Record $referencedRecord
//     */
//    public function updateRecordReference(Record $owningRecord = null, Record $referencedRecord = null)
//    {
//        if ($owningRecord === null && $referencedRecord === null) {
//            return;
//        }
//
//        // get old referenced id from owning record
//        $oldRefId = false;
//        if ($owningRecord) {
//            $oldRefId = $this->getOldReferencedId($owningRecord);
//        }
//
//        $refId = $referencedRecord ? $referencedRecord->getInternalIdentifier() : null;
//        if ($referencedRecord && $this->relation->isOneToOne() && isset($this->references[$refId])) {
//            $oldOwningId = $this->references[$refId];
//            $repositoryOwningSide = $this->getRefRepository($referencedRecord, $this->relation->getReferencedAlias());
//            if ($repositoryOwningSide->hasByInternalId($oldOwningId)) {
//                $oldOwningRecord = $repositoryOwningSide->getByInternalId($oldOwningId);
//                $oldOwningRecord->set($this->relation->getOwnerField(), null);
//                unset($this->ownerFieldOidMapping[$oldOwningRecord->getOid()]);
//            }
//        }
//
//        if ($owningRecord) {
//            // set field reference id, if referenced record exists in database
//            if (!$referencedRecord || $referencedRecord->exists()) {
//                $owningRecord->set($this->relation->getOwnerField(), $refId);
//            }
//            $this->updateOwningFieldOidMapping($owningRecord, $referencedRecord);
//        }
//
//        // unlink old reference
//        if ($oldRefId) {
//            $this->removeOwningReferenceForeignKey($owningRecord, $oldRefId);
//        }
//        // link new reference
//        if ($referencedRecord) {
//            $owningId = $owningRecord ? $owningRecord->getInternalIdentifier() : null;
//            $this->updateReference($owningId, $referencedRecord);
//            if ($owningId) {
//                $relatedCollection = $this->getRelatedCollection($referencedRecord);
//                // TODO exception, or if not set create one??
//                if ($relatedCollection) {
//                    $relatedCollection->add($owningRecord, null);
//                }
//            }
//        }
//    }

}