<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Relation;

use Dive\Collection\RecordCollection;
use Dive\Record;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 31.05.13
 */
class ReferenceMap
{

    /** @var Relation */
    protected $relation;

    /**
     * @var array
     * keys:   referenced ids,
     * values:
     *     one-to-many: owning ids as array
     *     one-to-one:  owning id as string
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
    private $owningFieldOidMapping = array();


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
     * @param  string $refId
     * @return bool
     */
    public function isReferenced($refId)
    {
        return $refId && isset($this->references[$refId]);
    }


    /**
     * @param  string $refId
     * @return bool
     */
    public function hasNullReference($refId)
    {
        if ($this->relation->isOneToMany()) {
            return false;
        }
        return array_key_exists($refId, $this->references) && $this->references[$refId] === null;
    }


    /**
     * Gets owning side id (one-to-one) or owning side ids (one-to-many)
     *
     * @param  string $refId
     * @return array|string
     */
    public function getOwning($refId)
    {
        return $this->references[$refId];
    }


    /**
     * Sets reference for a referenced id
     *
     * @param  string       $refId
     * @param  array|string $owningId
     * @throws \InvalidArgumentException
     */
    public function setReference($refId, $owningId)
    {
        if ($this->relation->isOneToOne() && !is_string($owningId) && $owningId !== null) {
            throw new \InvalidArgumentException(
                "One-To-One relation expects referencing identifier to be string!\nYou gave me: "
                . gettype($owningId)
            );
        }
        if ($this->relation->isOneToMany() && !is_array($owningId)) {
            throw new \InvalidArgumentException(
                "One-To-One relation expects referencing identifier to be array!\nYou gave me: "
                . gettype($owningId)
            );
        }

        $this->references[$refId] = $owningId;
    }


    /**
     * Adds owning id for a referenced id
     *
     * @param  string $refId
     * @param  string $owningId
     * @param  bool   $checkExistence
     */
    public function addReference($refId, $owningId, $checkExistence = true)
    {
        if (!$checkExistence || !isset($this->references[$refId]) || !in_array($owningId, $this->references[$refId])) {
            $this->references[$refId][] = $owningId;
        }
    }


    /**
     * Sets (one-to-one) or adds (one-to-many) a reference
     *
     * @param string $refId
     * @param string $owningId
     */
    private function assignReference($refId, $owningId)
    {
        // add new referenced record id
        if ($this->relation->isOneToOne()) {
            $this->setReference($refId, $owningId);
        }
        else if ($owningId) {
            $this->addReference($refId, $owningId);
        }
    }


    /**
     * @param string $refId
     * @param string $owningId
     */
    private function removeOwningReference($refId, $owningId)
    {
        if (!$this->isReferenced($refId)) {
            return;
        }
        $pos = array_search($owningId, $this->references[$refId]);
        if ($pos !== false) {
            unset($this->references[$refId][$pos]);
        }
    }


    public function unsetRecordReference(Record $record, $relationName)
    {
        $isReferencedSide = $this->relation->isReferencedSide($relationName);
        if ($isReferencedSide) {
            $this->unsetReferenceForOwningRecord($record);
        }
        else {
            $this->unsetReferenceForReferencedRecord($record);
        }
    }


    /**
     * @param Record $record
     */
    private function unsetReferenceForReferencedRecord(Record $record)
    {
        $refId = $record->getInternalId();
        unset($this->references[$refId]);

        $oid = $record->getOid();
        unset($this->relatedCollections[$oid]);
    }


    /**
     * @param Record $record
     */
    private function unsetReferenceForOwningRecord(Record $record)
    {
        $referencedAlias = $this->relation->getReferencedAlias();
        if (!$this->relation->hasReferenceLoadedFor($record, $referencedAlias)) {
            return;
        }
        $referencedRecord = $this->relation->getReferenceFor($record, $referencedAlias);
        if ($referencedRecord === null) {
            return;
        }

        $isOneToMany = $this->relation->isOneToMany();
        $owningId = $record->getInternalId();
        $refId = $referencedRecord->getInternalId();
        if ($isOneToMany) {
            $pos = array_search($owningId, $this->references[$refId]);
            if ($pos !== false) {
                unset($this->references[$refId][$pos]);
            }
            $refOid = $referencedRecord->getOid();
            if (isset($this->relatedCollections[$refOid])) {
                $this->relatedCollections[$refOid]->offsetUnset($owningId);
            }
        }
        else if ($owningId == $this->references[$refId]) {
            unset($this->references[$refId]);
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
        return isset($this->owningFieldOidMapping[$owningOid]);
    }


    /**
     * Sets reference for a not-yet-existing referenced record on an owning record
     *
     * @param string $owningOid
     * @param string $referencedOid
     */
    private function setFieldMapping($owningOid, $referencedOid)
    {
        $this->owningFieldOidMapping[$owningOid] = $referencedOid;
    }


    /**
     * Gets referenced record oid for a given owning record oid
     *
     * @param  string $owningOid
     * @return string
     */
    public function getFieldMapping($owningOid)
    {
        return $this->owningFieldOidMapping[$owningOid];
    }


    /**
     * Removes referenced record reference for a given owning record oid
     *
     * @param string $owningId
     */
    private function removeFieldMapping($owningId)
    {
        unset($this->owningFieldOidMapping[$owningId]);
    }


    /**
     * @param  string $owningOid
     * @param  string $referencedOid
     * @return bool
     */
    public function isFieldMappedWith($owningOid, $referencedOid)
    {
        if (!$this->hasFieldMapping($owningOid)) {
            return false;
        }
        return $this->getFieldMapping($owningOid) === $referencedOid;
    }


    /**
     * Updates field mapping for referenced record to an owning record
     *
     * @param Record $owningRecord
     * @param Record $referencedRecord
     */
    private function updateFieldMapping(Record $owningRecord, Record $referencedRecord = null)
    {
        $oid = $owningRecord->getOid();
        if ($referencedRecord && !$referencedRecord->exists()) {
            $this->setFieldMapping($oid, $referencedRecord->getOid());
        }
        else {
            $this->removeFieldMapping($oid);
        }
    }


    /**
     * Sets related collection for a referenced record by a given oid
     *
     * @param string           $referencedOid
     * @param RecordCollection $collection
     */
    private function setRelatedCollection($referencedOid, RecordCollection $collection)
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
        $relationName = $this->relation->getOwningAlias();
        if (!$this->relation->isOneToMany()) {
            throw new RelationException("Reference type for relation '$relationName' must be a collection!");
        }

        $oid = $record->getOid();
        $refIds = $this->relation->getRecordReferencedIdentifiers($record, $relationName);
        if (!is_array($refIds)) {
            return false;
        }

        $rm = $record->getTable()->getRecordManager();
        $refTable = $this->relation->getJoinTable($rm, $relationName);
        $collection = new RecordCollection($refTable, $record, $this->relation);
        foreach ($refIds as $refId) {
            if (!$refTable->isInRepository($refId)) {
                return false;
            }
            $relatedRecord = $refTable->getFromRepository($refId);
            $collection->add($relatedRecord, $refId);
        }
        $this->setRelatedCollection($oid, $collection);
        return $collection;
    }


    /**
     * Updates record collections of referenced record (record collection is to be exchanged with another)
     *
     * @param Record                    $record
     * @param RecordCollection|Record[] $related
     */
    public function updateCollectionReference(Record $record, RecordCollection $related)
    {
        $oid = $record->getOid();

        // when exchanging collection, we have to unlink all related records
        $relatedCollection = $this->getRelatedCollection($oid);
        if ($relatedCollection && $relatedCollection !== $related) {
            $owningField = $this->relation->getOwningField();
            foreach ($relatedCollection as $owningRecord) {
                $this->removeFieldMapping($owningRecord->getOid());
                $owningRecord->set($owningField, null);
            }
        }

        // set references for new related records
        $this->setReference($record->getInternalId(), $related->getIdentifiers());
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
        $tableName = $this->relation->isReferencedSide($relationName)
            ? $this->relation->getReferencedTable()
            : $this->relation->getOwningTable();
        $refTable = $rm->getTable($tableName);
        return $refTable->getRepository();
    }


    /**
     * Gets record for owning side
     * @TODO check that given record is the correct model and within this referenceMap
     *
     * @param  Record $record
     * @return bool|Record|null
     * @throws RelationException
     */
    public function getRecordForOwningSide(Record $record)
    {
        $owningAlias = $this->relation->getOwningAlias();
        if ($this->relation->isOneToMany()) {
            throw new RelationException("Relation '$owningAlias' does not expected a record as reference!");
        }
        $id = $record->getInternalId();
        if ($this->isReferenced($id)) {
            $refId = $this->getOwning($id);
            $refRepository = $this->getRefRepository($record, $owningAlias);
            return $refRepository->getByInternalId($refId);
        }
        return null;
    }


    /**
     * Gets record for referenced side
     *
     * @param  Record $record
     * @return bool|Record|null
     */
    public function getRecordForReferencedSide(Record $record)
    {
        $refId = $record->get($this->relation->getOwningField());
        if ($refId === null) {
            $oid = $record->getOid();
            if ($this->hasFieldMapping($oid)) {
                $refOid = $this->getFieldMapping($oid);
                $refRepository = $this->getRefRepository($record, $this->relation->getReferencedAlias());
                return $refRepository->getByOid($refOid);
            }
        }
        else {
            $refRepository = $this->getRefRepository($record, $this->relation->getReferencedAlias());
            return $refRepository->getByInternalId($refId);
        }
        return null;
    }


    /**
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
        $actualRefId = false;
        if ($owningRecord) {
            $actualRefId = $this->getOldReferencedId($owningRecord);
        }

        // unlink the field mapping of the referenced record for the old owning record
        if ($referencedRecord) {
            $this->unlinkFieldMappingForOldOwningRecord($referencedRecord);
        }

        if ($owningRecord) {
            // set field reference id, if referenced record exists in database
            $owningField = $this->relation->getOwningField();
            $refId = $referencedRecord && $referencedRecord->exists() ? $referencedRecord->getInternalId() : null;
            $owningRecord->set($owningField, $refId);
            $this->updateFieldMapping($owningRecord, $referencedRecord);
        }

        // unlink old reference
        if ($actualRefId) {
            $this->removeOwningReferenceForeignKey($owningRecord, $actualRefId);
        }
        // link new reference
        if ($referencedRecord) {
            $owningId = $owningRecord ? $owningRecord->getInternalId() : null;
            $this->assignReference($referencedRecord->getInternalId(), $owningId);
            if ($owningId && $this->relation->isOneToMany()) {
                $relatedCollection = $this->getRelatedCollection($referencedRecord->getOid());
                // TODO exception, or if not set create one??
                if ($relatedCollection) {
                    $relatedCollection->add($owningRecord, null);
                }
            }
        }
    }


    /**
     * @param Record $referencedRecord
     * @param string $oldReferencedId
     */
    public function updateRecordIdentifier(Record $referencedRecord, $oldReferencedId)
    {
        $oid = $referencedRecord->getOid();
        if (isset($this->relatedCollections[$oid][$oldReferencedId])) {
            // TODO change collection identifier
        }
        if ($this->isReferenced($oldReferencedId)) {
            $owningIds = (array)$this->getOwning($oldReferencedId);
            if (!empty($owningIds)) {
                $referenceId = $referencedRecord->getIdentifier();
                $owningRepository = $this->getRefRepository($referencedRecord, $this->relation->getReferencedAlias());

                foreach ($owningIds as $owningId) {
                    $owningRecord = $owningRepository->getByInternalId($owningId);
                    $owningRecord->set($this->relation->getOwningField(), $referenceId);
                    unset($this->owningFieldOidMapping[$owningRecord->getOid()]);
                }
            }
        }
    }


    /**
     * Unlink the field mapping of the referenced record for the old owning record
     *
     * @param  Record $referencedRecord
     * @return array
     */
    private function unlinkFieldMappingForOldOwningRecord(Record $referencedRecord)
    {
        if (!$this->relation->isOneToOne()) {
            return;
        }

        $refId = $referencedRecord->getInternalId();
        if (!$this->isReferenced($refId)) {
            return;
        }
        $oldOwningId = $this->getOwning($refId);
        $repositoryOwningSide = $this->getRefRepository($referencedRecord, $this->relation->getOwningAlias());
        if ($repositoryOwningSide->hasByInternalId($oldOwningId)) {
            $owningField = $this->relation->getOwningField();
            $oldOwningRecord = $repositoryOwningSide->getByInternalId($oldOwningId);
            $oldOwningRecord->set($owningField, null);
            $this->removeFieldMapping($oldOwningRecord->getOid());
        }
    }


    /**
     * Gets id of old referenced record
     *
     * @param  Record $owningRecord
     * @return bool|string
     */
    private function getOldReferencedId(Record $owningRecord)
    {
        $oid = $owningRecord->getOid();
        $oldRefId = $owningRecord->get($this->relation->getOwningField());
        if (!$oldRefId && $this->hasFieldMapping($oid)) {
            $oldRefId = Record::NEW_RECORD_ID_MARK . $this->getFieldMapping($oid);
        }
        return $oldRefId;
    }


    /**
     * Removes owning record from record collection belonging to the referenced record given by it's id
     *
     * @param Record $record
     * @param string $referencedId
     */
    public function removeOwningReferenceForeignKey(Record $record, $referencedId)
    {
        if (!$referencedId || !$this->isReferenced($referencedId)) {
            return;
        }

        if ($this->relation->isOneToMany()) {
            $owningId = $record->getInternalId();
            $this->removeOwningReference($referencedId, $owningId);

            $refRepository = $this->getRefRepository($record, $this->relation->getOwningAlias());
            $oldRefRecord = $refRepository->getByInternalId($referencedId);
            if ($oldRefRecord) {
                $relatedCollection = $this->getRelatedCollection($oldRefRecord->getOid());
                // TODO exception, or if not set create one??
                if ($relatedCollection) {
                    $relatedCollection->remove($owningId);
                }
            }
        }
        else {
            $this->setReference($referencedId, null);
        }
    }


    /**
     * Sets owning record reference to the referenced record given by it's id
     *
     * @param Record $record
     * @param string $newId
     */
    public function setOwningReferenceByForeignKey(Record $record, $newId)
    {
        if (!$newId) {
            return;
        }
        $id = $record->getInternalId();
        if ($this->relation->isOneToMany()) {
            $refRepository = $this->getRefRepository($record, $this->relation->getOwningAlias());
            $newRefRecord = $refRepository->getByInternalId($newId);
            if ($newRefRecord) {
                $relatedCollection = $this->getRelatedCollection($newRefRecord->getOid());
                // TODO exception, or if not set create one??
                if ($relatedCollection) {
                    $relatedCollection->add($record);
                }
            }
        }
        $this->assignReference($newId, $id);
    }


    /**
     * Updates references between owner and referenced record collection
     *
     * @param RecordCollection|Record[] $ownerCollection
     * @param RecordCollection|Record[] $referencedCollection
     */
    public function updateOwnerCollectionWithReferencedCollection(
        RecordCollection $ownerCollection,
        RecordCollection $referencedCollection
    )
    {
        $owningField = $this->relation->getOwningField();
        $isOneToMany = $this->relation->isOneToMany();
        foreach ($ownerCollection as $refRecord) {
            $refId = $refRecord->get($owningField);
            $owningId = $refRecord->getInternalId();
            $this->assignReference($refId, $owningId);
        }

        foreach ($referencedCollection as $refRecord) {
            $id = $refRecord->getInternalId();
            if (!$this->isReferenced($id) && !$this->hasNullReference($id)) {
                $reference = $isOneToMany ? array() : null;
                $this->setReference($id, $reference);
            }
        }
    }


    /**
     * Gets reference mapping
     *
     * @return array
     *   keys:   referenced ids,
     *   values:
     *      one-to-many: owning ids as array
     *      one-to-one:  owning id as string
     */
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
        $this->owningFieldOidMapping = array();
    }

}
