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
 * Handles one to one relations
 *
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class ToManyReferenceMap extends ToOneReferenceMap
{

    /**
     * @var RecordCollection[]
     * keys:  oid (referenced record)
     */
    private $relatedCollections = array();

    /** @var bool[] */
    private $referenceLoaded = [];


    /**
     * @param  string $refId
     * @return bool
     */
    public function hasNullReference($refId)
    {
        return false;
    }


    /**
     * Sets reference for a referenced id
     *
     * @param  string $refId
     * @param  array  $owningId
     * @throws \InvalidArgumentException
     */
    public function setReference($refId, $owningId)
    {
        if (!is_array($owningId)) {
            throw new \InvalidArgumentException(
                "One-To-Many relation expects referencing identifier to be array!\nYou gave me: "
                . gettype($owningId)
            );
        }

        $this->references[$refId] = $owningId;
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

        $owningId = $record->getInternalId();
        $this->removeOwningReference($referencedId, $owningId);

        $refRepository = $this->getRefRepository($record, $this->relation->getReferencedAlias());
        $oldRefRecord = $refRepository->getByInternalId($referencedId);
        if ($oldRefRecord) {
            $relatedCollection = $this->getRelatedCollection($oldRefRecord->getOid());
            // TODO exception, or if not set create one??
            if ($relatedCollection) {
                $relatedCollection->unlinkRecord($record);
            }
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
        return isset($this->relatedCollections[$referencedOid])
            ? $this->relatedCollections[$referencedOid]
            : null;
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
            $collection->add($relatedRecord);
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
                $this->unsetFieldMapping($owningRecord->getOid());
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
        throw new RelationException("Relation '$owningAlias' does not expected a record as reference!");
    }


    /**
     * Updates record references for given owning record and given referenced record
     *
     * @param Record $owningRecord
     * @param Record $referencedRecord
     */
    public function updateRecordReference(Record $owningRecord = null, Record $referencedRecord = null)
    {
        parent::updateRecordReference($owningRecord, $referencedRecord);

        // link new reference
        if ($referencedRecord && $owningRecord) {
            $refOid = $referencedRecord->getOid();
            $relatedCollection = $this->getRelatedCollection($refOid);
            // TODO exception, or if not set create one??
            if ($relatedCollection) {
                $relatedCollection->add($owningRecord);
            }

            if (!$referencedRecord->exists()) {
                $this->setReferenceLoaded($referencedRecord->getInternalId());
            }
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

        parent::setOwningReferenceByForeignKey($record, $newId);

        $refRepository = $this->getRefRepository($record, $this->relation->getReferencedAlias());
        $newRefRecord = $refRepository->getByInternalId($newId);
        if ($newRefRecord) {
            $relatedCollection = $this->getRelatedCollection($newRefRecord->getOid());
            // TODO exception, or if not set create one??
            if ($relatedCollection) {
                $relatedCollection->add($record);
            }
        }
    }



    /**
     * Adds a reference
     *
     * @param string $refId
     * @param string $owningId
     */
    public function assignReference($refId, $owningId)
    {
        if ($owningId) {
            $this->addReference($refId, $owningId);
        }
    }


    /**
     * @param Record $record
     */
    protected function unsetReferenceForReferencedRecord(Record $record)
    {
        parent::unsetReferenceForReferencedRecord($record);

        $oid = $record->getOid();
        unset($this->relatedCollections[$oid]);
    }


    /**
     * @param Record $record
     * @param Record $referencedRecord
     */
    protected function unsetReferenceFromMap(Record $record, Record $referencedRecord)
    {
        $this->removeOwningReference($referencedRecord->getInternalId(), $record->getInternalId());

        $refOid = $referencedRecord->getOid();
        if (isset($this->relatedCollections[$refOid])) {
            $pos = $this->relatedCollections[$refOid]->search($record);
            if ($pos !== false) {
                $this->relatedCollections[$refOid]->offsetUnset($pos);
            }
        }
    }


    /**
     * @param Record $record
     */
    protected function unsetReferenceForOwningRecordOnUnloadedReference(Record $record)
    {
        $owningId = $record->getInternalId();
        foreach ($this->references as $refId => $owningIds) {
            if ($this->removeOwningReference($refId, $owningId)) {
                break;
            }
        }

        $this->unsetFieldMapping($record->getOid());
    }


    /**
     * @param string $refId
     * @param string $newIdentifier
     * @param string $oldIdentifier
     */
    protected function updateReferenceInMap($refId, $newIdentifier, $oldIdentifier)
    {
        if (isset($this->references[$refId])) {
            $pos = array_search($oldIdentifier, $this->references[$refId], true);
            if ($pos !== false) {
                $this->references[$refId][$pos] = $newIdentifier;
                return;
            }
        }
        $this->references[$refId][] = $newIdentifier;

    }


    /**
     * Adds owning id for a referenced id
     *
     * @param  string $refId
     * @param  string $owningId
     * @param  bool   $checkExistence
     */
    private function addReference($refId, $owningId, $checkExistence = true)
    {
        if (!$checkExistence || !isset($this->references[$refId]) || !in_array($owningId, $this->references[$refId], true)) {
            $this->references[$refId][] = $owningId;
        }
    }


    /**
     * @param string $refId
     * @param string $owningId
     * @return bool
     */
    private function removeOwningReference($refId, $owningId)
    {
        if (!$this->isReferenced($refId)) {
            return false;
        }

        $pos = array_search($owningId, $this->references[$refId], true);
        if ($pos !== false) {
            unset($this->references[$refId][$pos]);
            return true;
        }

        return false;
    }


    /**
     * Clears reference map
     */
    public function clear()
    {
        parent::clear();

        $this->relatedCollections = array();
    }


    /**
     * @param string $refId
     * @param bool   $mustBeLoaded
     * @return array|null|string
     */
    public function getOwning($refId, $mustBeLoaded = true)
    {
        if ($mustBeLoaded && (!isset($this->referenceLoaded[$refId]) || $this->referenceLoaded[$refId] === false)) {
            return null;
        }
        return parent::getOwning($refId);
    }


    /**
     * @param string $refId
     */
    public function setReferenceLoaded($refId)
    {
        $this->referenceLoaded[$refId] = true;
    }

}
