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


    /**
     * @param Relation $relation
     */
    public function __construct(Relation $relation)
    {
        $this->relation = $relation;
    }


    /**
     * True, if reference exists for record id
     *
     * @param  string $id
     * @return bool
     */
    public function hasReferenced($id)
    {
        return array_key_exists($id, $this->references);
    }


    /**
     * Gets owning side id (one-to-one) or owning side ids (one-to-many)
     *
     * @param  string $id
     * @return array|string
     */
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
     * @param  string $ownerId
     * @param  bool   $checkExistence
     */
    public function addReference($id, $ownerId, $checkExistence = true)
    {
        if (!$checkExistence || !isset($this->references[$id]) || !in_array($ownerId, $this->references[$id])) {
            $this->references[$id][] = $ownerId;
        }
    }


    /**
     * True, if not-yet-existing referenced record is mapped for owning record
     *
     * @param  string $owningOid
     * @return bool
     */
    public function hasFieldMapping($owningOid)
    {
        return isset($this->ownerFieldOidMapping[$owningOid]);
    }


    /**
     * Sets reference for a not-yet-existing referenced record on an owning record
     *
     * @param string $owningOid
     * @param string $referencedOid
     */
    public function setFieldMapping($owningOid, $referencedOid)
    {
        $this->ownerFieldOidMapping[$owningOid] = $referencedOid;
    }


    /**
     * Gets referenced record oid for a given owning record oid
     *
     * @param  string $owningOid
     * @return string
     */
    public function getFieldMapping($owningOid)
    {
        return $this->ownerFieldOidMapping[$owningOid];
    }


    /**
     * Removes referenced record reference for a given owning record oid
     *
     * @param string $owningId
     */
    public function removeFieldMapping($owningId)
    {
        unset($this->ownerFieldOidMapping[$owningId]);
    }


    /**
     * Sets related collection for a referenced record by a given oid
     *
     * @param string           $referencedOid
     * @param RecordCollection $collection
     */
    public function setRelatedCollection($referencedOid, RecordCollection $collection)
    {
        $this->relatedCollections[$referencedOid] = $collection;
    }


    /**
     * Gets related collection for a given referenced record oid
     *
     * @param  string $referencedOid
     * @return RecordCollection|Record[]|null
     */
    public function getRelatedCollection($referencedOid)
    {
        return isset($this->relatedCollections[$referencedOid]) ? $this->relatedCollections[$referencedOid] : null;
    }


    /**
     * Created new related collection for a given referenced record
     *
     * @param Record $record
     * @return bool|RecordCollection
     * @throws RelationException
     */
    public function createRelatedCollection(Record $record)
    {
        $relationName = $this->relation->getReferencedAlias();
        if (!$this->relation->isOneToMany()) {
            throw new RelationException("Reference type for relation '$relationName' must be a collection!");
        }

        $oid = $record->getOid();
        $refIds = $this->relation->getRecordReferencedIdentifiers($record, $relationName);
        if (is_array($refIds)) {
            $rm = $record->getTable()->getRecordManager();
            $refTable = $this->relation->getJoinTable($rm, $relationName);
            $collection = new RecordCollection($refTable, $record, $this->relation);
            $recordsInRepository = true;
            foreach ($refIds as $refId) {
                if (!$refTable->isInRepository($refId)) {
                    $recordsInRepository = false;
                    break;
                }
                $relatedRecord = $refTable->getFromRepository($refId);
                $collection->add($relatedRecord, $refId);
            }
            if ($recordsInRepository) {
                $this->setRelatedCollection($oid, $collection);
                return $collection;
            }
        }
        return false;
    }


    /**
     * @param Record                    $record
     * @param RecordCollection|Record[] $related
     */
    public function updateCollectionReference(Record $record, RecordCollection $related)
    {
        $oid = $record->getOid();

        // when exchanging collection, we have to unlink all related records
        $relatedCollection = $this->getRelatedCollection($oid);
        if ($relatedCollection && $relatedCollection !== $related) {
            $ownerField = $this->relation->getOwnerField();
            foreach ($relatedCollection as $owningRecord) {
                $this->removeFieldMapping($owningRecord->getOid());
                $owningRecord->set($ownerField, null);
            }
        }

        // set references for new related records
        $id = $record->getIntId();
        $this->setReference($id, $related->getIdentifiers());
        if (!$record->exists()) {
            foreach ($related as $relatedRecord) {
                $this->setFieldMapping($relatedRecord->getOid(), $oid);
            }
        }
        $this->setRelatedCollection($oid, $related);
    }


    /**
     * Get relation referenced repository
     *
     * @param  Record $record
     * @param  string $relationName
     * @return \Dive\Table\Repository
     */
    private function getRefRepository(Record $record, $relationName)
    {
        $rm = $record->getTable()->getRecordManager();
        $tableName = $this->relation->isOwningSide($relationName)
            ? $this->relation->getReferencedTable()
            : $this->relation->getOwnerTable();
        $refTable = $rm->getTable($tableName);
        return $refTable->getRepository();
    }


    /**
     * Gets record for owning side
     *
     * @param  Record $record
     * @return bool|Record|null
     */
    public function getRecordForOwningSide(Record $record)
    {
        $refId = $record->get($this->relation->getOwnerField());
        if ($refId === null) {
            $oid = $record->getOid();
            if ($this->hasFieldMapping($oid)) {
                $refOid = $this->getFieldMapping($oid);
                $refRepository = $this->getRefRepository($record, $this->relation->getOwnerAlias());
                return $refRepository->getByOid($refOid);
            }
        }
        else {
            $refRepository = $this->getRefRepository($record, $this->relation->getOwnerAlias());
            return $refRepository->getByInternalId($refId);
        }
        return null;
    }


    /**
     * Gets record for referenced side
     *
     * @param  Record $record
     * @return bool|Record|null
     * @throws RelationException
     */
    public function getRecordForReferencedSide(Record $record)
    {
        $refAlias = $this->relation->getReferencedAlias();
        if ($this->relation->isOneToMany()) {
            throw new RelationException("Relation '$refAlias' does not expected a record as reference!");
        }
        $id = $record->getInternalIdentifier();
        if ($this->hasReferenced($id)) {
            $refId = $this->getOwning($id);
            $refRepository = $this->getRefRepository($record, $refAlias);
            return $refRepository->getByInternalId($refId);
        }
        return null;
    }


    /**
     * TODO Refactor method into smaller parts
     * Updates record references for given owning record and given referenced record
     *
     * @param Record $owningRecord
     * @param Record $referencedRecord
     */
    public function updateRecordReference(Record $owningRecord = null, Record $referencedRecord = null)
    {
        if ($owningRecord === null && $referencedRecord === null) {
            return;
        }

        // get old referenced id from owning record
        $oldRefId = false;
        if ($owningRecord) {
            $oldRefId = $this->getOldReferencedId($owningRecord);
        }

        $ownerField = $this->relation->getOwnerField();
        $refId = $referencedRecord ? $referencedRecord->getInternalIdentifier() : null;
        if ($referencedRecord && $this->relation->isOneToOne() && $this->hasReferenced($refId)) {
            $oldOwningId = $this->getOwning($refId);
            $repositoryOwningSide = $this->getRefRepository(
                $referencedRecord,
                $this->relation->getReferencedAlias()
            );
            if ($repositoryOwningSide->hasByInternalId($oldOwningId)) {
                $oldOwningRecord = $repositoryOwningSide->getByInternalId($oldOwningId);
                $oldOwningRecord->set($ownerField, null);
                $this->removeFieldMapping($oldOwningRecord->getOid());
            }
        }

        if ($owningRecord) {
            // set field reference id, if referenced record exists in database
            if (!$referencedRecord || $referencedRecord->exists()) {
                $owningRecord->set($ownerField, $refId);
            }
            $this->updateOwningFieldOidMapping($owningRecord, $referencedRecord);
        }

        // unlink old reference
        if ($oldRefId) {
            $this->removeOwningReferenceForeignKey($owningRecord, $oldRefId);
        }
        // link new reference
        if ($referencedRecord) {
            $owningId = $owningRecord ? $owningRecord->getInternalIdentifier() : null;
            $this->updateReference($owningId, $referencedRecord);
            if ($owningId) {
                $relatedCollection = $this->getRelatedCollection($referencedRecord->getOid());
                // TODO exception, or if not set create one??
                if ($relatedCollection) {
                    $relatedCollection->add($owningRecord, null);
                }
            }
        }
    }


    /**
     * TODO explain method
     *
     * @param Record $owningRecord
     * @return bool|string
     */
    private function getOldReferencedId(Record $owningRecord)
    {
        $oid = $owningRecord->getOid();
        $oldRefId = $owningRecord->getModifiedFieldValue($this->relation->getOwnerField());
        if (!$oldRefId && $this->hasFieldMapping($oid)) {
            $oldRefId = Record::NEW_RECORD_ID_MARK . $this->getFieldMapping($oid);
        }
        return $oldRefId;
    }


    /**
     * TODO explain method
     *
     * @param Record $record
     * @param string $oldRefId
     */
    public function removeOwningReferenceForeignKey(Record $record, $oldRefId)
    {
        if (!$oldRefId || !isset($this->references[$oldRefId])) {
            return;
        }
        if ($this->relation->isOneToMany()) {
            $owningId = $record->getIntId();
            if (isset($this->references[$oldRefId])) {
                $pos = array_search($owningId, $this->references[$oldRefId]);
                if ($pos !== false) {
                    array_splice($this->references[$oldRefId], $pos, 1);
                }
            }

            $refRepository = $this->getRefRepository($record, $this->relation->getOwnerAlias());
            $oldRefRecord = $refRepository->getByInternalId($oldRefId);
            if ($oldRefRecord) {
                $relatedCollection = $this->getRelatedCollection($oldRefRecord->getOid());
                // TODO exception, or if not set create one??
                if ($relatedCollection) {
                    $relatedCollection->remove($owningId);
                }
            }
        }
        else {
            $this->references[$oldRefId] = null;
        }
    }


    /**
     * TODO explain method
     *
     * @param Record $record
     * @param string $newId
     */
    public function setOwningReferenceByForeignKey(Record $record, $newId)
    {
        if (!$newId) {
            return;
        }
        $id = $record->getInternalIdentifier();
        if ($this->relation->isOneToMany()) {
            $refRepository = $this->getRefRepository($record, $this->relation->getOwnerAlias());
            $newRefRecord = $refRepository->getByInternalId($newId);
            if ($newRefRecord) {
                $relatedCollection = $this->getRelatedCollection($newRefRecord->getOid());
                // TODO exception, or if not set create one??
                if ($relatedCollection) {
                    $relatedCollection->add($record);
                }
            }
            $this->addReference($newId, $id);
        }
        else {
            $this->setReference($newId, $id);
        }
    }


    /**
     * @param RecordCollection|Record[] $ownerCollection
     * @param RecordCollection|Record[] $referencedCollection
     */
    public function updateOwnerCollectionWithReferencedCollection(
        RecordCollection $ownerCollection,
        RecordCollection $referencedCollection
    )
    {
        $ownerField = $this->relation->getOwnerField();
        $isOneToMany = $this->relation->isOneToMany();
        foreach ($ownerCollection as $refRecord) {
            $refId = $refRecord->get($ownerField);
            $ownerId = $refRecord->getIntId();
            if ($isOneToMany) {
                $this->addReference($refId, $ownerId);
            }
            else {
                $this->setReference($refId, $ownerId);
            }
        }

        foreach ($referencedCollection as $refRecord) {
            $id = $refRecord->getIntId();
            if (!$this->hasReferenced($id)) {
                $this->setReference($id, $isOneToMany ? array() : null);
            }
        }
    }


    /**
     * @param string $owningId
     * @param Record $referencedRecord
     */
    private function updateReference($owningId, Record $referencedRecord)
    {
        $refId = $referencedRecord->getInternalIdentifier();
        // add new referenced record id
        if ($this->relation->isOneToOne()) {
            $this->setReference($refId, $owningId);
        }
        else if ($owningId) {
            $this->addReference($refId, $owningId);
        }
    }


    private function updateOwningFieldOidMapping(Record $owningRecord, Record $referencedRecord = null)
    {
        $oid = $owningRecord->getOid();
        if ($referencedRecord && !$referencedRecord->exists()) {
            $this->setFieldMapping($oid, $referencedRecord->getOid());
        }
        else {
            $this->removeFieldMapping($oid);
        }
    }


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

}