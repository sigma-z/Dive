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
 */
class ToOneReferenceMap
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
    protected $references = array();

    /**
     * owning field references
     * @var array
     * keys:   oid (owning record - contains foreign key field)
     * values: oid (referenced record not-yet-persisted)
     */
    protected $owningFieldOidMapping = array();


    /**
     * @param Relation $relation
     */
    public function __construct(Relation $relation)
    {
        $this->relation = $relation;
    }


    // <editor-fold desc="reference operations">
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
     * @param  string $refId
     * @param  string $owningId
     * @throws \InvalidArgumentException
     */
    public function setReference($refId, $owningId)
    {
        if (!is_string($owningId) && $owningId !== null) {
            throw new \InvalidArgumentException(
                "One-To-One relation expects referencing identifier to be string!\nYou gave me: "
                . gettype($owningId)
            );
        }

        $this->references[$refId] = $owningId;
    }


    /**
     * Sets a reference
     *
     * @param string $refId
     * @param string $owningId
     */
    public function assignReference($refId, $owningId)
    {
        // add new referenced record id
        $this->setReference($refId, $owningId);
    }


    /**
     * @param string $refId
     * @param string $newIdentifier
     * @param string $oldIdentifier
     */
    protected function updateReferenceInMap($refId, $newIdentifier, $oldIdentifier)
    {
        $this->references[$refId] = $newIdentifier;
    }


    /**
     * @param string $newIdentifier
     * @param string $oldIdentifier
     */
    public function updateReferencedIdentifier($newIdentifier, $oldIdentifier)
    {
        if (array_key_exists($oldIdentifier, $this->references)) {
            $this->references[$newIdentifier] = $this->references[$oldIdentifier];
            unset($this->references[$oldIdentifier]);
        }
    }
    //</editor-fold>


    //<editor-fold desc="field mapping operations">
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
    public function setFieldMapping($owningOid, $referencedOid)
    {
        if ($referencedOid) {
            $this->owningFieldOidMapping[$owningOid] = $referencedOid;
        }
        else {
            $this->unsetFieldMapping($owningOid);
        }
    }


    /**
     * Removes referenced record reference for a given owning record oid
     *
     * @param string $owningOid
     */
    protected function unsetFieldMapping($owningOid)
    {
        unset($this->owningFieldOidMapping[$owningOid]);
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
    //</editor-fold>


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
        $this->owningFieldOidMapping = array();
    }


    /**
     * @param Record $record
     * @param string $relationName
     */
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
    protected function unsetReferenceForReferencedRecord(Record $record)
    {
        $refId = $record->getInternalId();
        unset($this->references[$refId]);
    }


    /**
     * @param Record $record
     */
    protected function unsetReferenceForOwningRecord(Record $record)
    {
        $referencedAlias = $this->relation->getReferencedAlias();
        if (!$this->relation->hasReferenceLoadedFor($record, $referencedAlias)) {
            $this->unsetReferenceForOwningRecordOnUnloadedReference($record);
            return;
        }
        $referencedRecord = $this->relation->getReferenceFor($record, $referencedAlias);
        if ($referencedRecord === null) {
            return;
        }

        $this->unsetReferenceFromMap($record, $referencedRecord);
        $this->unsetFieldMapping($record->getOid());
    }


    /**
     * @param Record $record
     * @param Record $referencedRecord
     */
    protected function unsetReferenceFromMap(Record $record, Record $referencedRecord)
    {
        $owningId = $record->getInternalId();
        $refId = $referencedRecord->getInternalId();
        if ($owningId == $this->references[$refId]) {
            $this->unsetReferenceForReferencedRecord($referencedRecord);
        }
    }


    /**
     * @param Record $record
     */
    protected function unsetReferenceForOwningRecordOnUnloadedReference(Record $record)
    {
        $owningId = $record->getInternalId();
        $refId = array_search($owningId, $this->references);
        if ($refId) {
            unset($this->references[$refId]);
        }

        $this->unsetFieldMapping($record->getOid());
    }


    /**
     * Get relation referenced repository
     *
     * @param  Record $record
     * @param  string $relationName
     * @return \Dive\Table\Repository
     */
    protected function getRefRepository(Record $record, $relationName)
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
        $id = $record->getInternalId();
        if ($this->isReferenced($id)) {
            $refId = $this->getOwning($id);
            $owningAlias = $this->relation->getOwningAlias();
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

        $refOid = null;
        // unlink the field mapping of the referenced record for the old owning record
        if ($referencedRecord) {
            $this->unlinkFieldMappingForOldOwningRecord($referencedRecord);
            $refOid = $referencedRecord->getOid();
        }

        if ($owningRecord) {
            // set field reference id, if referenced record exists in database
            $owningField = $this->relation->getOwningField();
            $refId = $referencedRecord && $referencedRecord->exists() ? $referencedRecord->getInternalId() : null;
            $owningRecord->set($owningField, $refId);
            $this->setFieldMapping($owningRecord->getOid(), $refOid);
        }

        // unlink old reference
        if ($actualRefId) {
            $this->removeOwningReferenceForeignKey($owningRecord, $actualRefId);
        }

        // link new reference
        if ($referencedRecord) {
            $owningId = $owningRecord ? $owningRecord->getInternalId() : null;
            $this->assignReference($referencedRecord->getInternalId(), $owningId);
        }
    }


    /**
     * @param string $newIdentifier
     * @param string $oldIdentifier
     * @param Record $owningRecord
     * @throws \Dive\Table\RepositoryException
     * @return bool
     */
    public function updateReferenceIfReferencedRecordIdentifierHasChanged($newIdentifier, $oldIdentifier, Record $owningRecord)
    {
        $owningOid = $owningRecord->getOid();
        if ($this->hasFieldMapping($owningOid)) {
            $referencedOid = $this->getFieldMapping($owningOid);
            $refRepository = $this->getRefRepository($owningRecord, $this->relation->getReferencedAlias());
            $referencedRecord = $refRepository->getByOid($referencedOid);
            if ($referencedRecord->getIdentifierAsString() == $newIdentifier) {
                $this->updateReferencedIdentifier($newIdentifier, $oldIdentifier);
                return true;
            }
        }
        return false;
    }


    /**
     * @param string $newIdentifier
     * @param string $oldIdentifier
     * @param Record $owningRecord
     */
    public function updateOwningIdentifier($newIdentifier, $oldIdentifier, Record $owningRecord)
    {
        $relationName = $this->relation->getReferencedAlias();
        if (!$this->relation->hasReferenceLoadedFor($owningRecord, $relationName)) {
            return;
        }

        $referencedRecord = $this->relation->getReferenceFor($owningRecord, $relationName);
        if ($referencedRecord) {
            $refId = $referencedRecord->getInternalId();
            $this->updateReferenceInMap($refId, $newIdentifier, $oldIdentifier);
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
            $this->unsetFieldMapping($oldOwningRecord->getOid());
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
        $this->setReference($referencedId, null);
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

}
