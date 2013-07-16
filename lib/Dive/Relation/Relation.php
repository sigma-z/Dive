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
use Dive\RecordManager;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 30.10.12
 *
 * TODO refactor class by moving methods to ReferenceUpdater and ReferenceLoader??
 */

class Relation
{

    const ONE_TO_ONE = '1-1';
    const ONE_TO_MANY = '1-m';

    /**
     * @var string
     */
    protected $ownerField = '';
    /**
     * @var string
     */
    protected $ownerTable = '';
    /**
     * @var string
     */
    protected $ownerAlias = '';
    /**
     * @var string
     */
    protected $refField =  '';
    /**
     * @var string
     */
    protected $refTable = '';
    /**
     * @var string
     */
    protected $refAlias = '';
    /**
     * @var int
     */
    protected $type = '';
    /**
     * @var string
     */
    protected $onDelete = '';
    /**
     * @var string
     */
    protected $onUpdate = '';
    /**
     * @var string
     */
    protected $orderBy = '';
    /**
     * @var ReferenceMap
     */
    private $map = null;


    /**
     * constructor
     * @param string    $ownerAlias
     * @param string    $ownerTable
     * @param string    $ownerField
     * @param string    $refAlias
     * @param string    $refTable
     * @param string    $refField
     * @param int       $type
     * @param string    $onDelete
     * @param string    $onUpdate
     * @param string    $orderBy
     * @throws \InvalidArgumentException
     */
    public function __construct(
        $ownerAlias,
        $ownerTable,
        $ownerField,
        $refAlias,
        $refTable,
        $refField,
        $type,
        $onDelete = '',
        $onUpdate = '',
        $orderBy = ''
    ) {
        if ($ownerAlias == $refAlias) {
            throw new \InvalidArgumentException('Owner alias and referenced alias must not be equal!');
        }

        $this->ownerAlias = $ownerAlias;
        $this->ownerTable = $ownerTable;
        $this->ownerField = $ownerField;
        $this->refAlias   = $refAlias;
        $this->refTable   = $refTable;
        $this->refField   = $refField;
        $this->type       = $type;
        $this->onDelete   = $onDelete;
        $this->onUpdate   = $onUpdate;
        $this->orderBy    = $orderBy;

        $this->map = new ReferenceMap($this);
    }


    public function getDefinition()
    {
        $definition = array(
            'ownerAlias' => $this->ownerAlias,
            'ownerTable' => $this->ownerTable,
            'ownerField' => $this->ownerField,
            'refAlias'   => $this->refAlias,
            'refTable'   => $this->refTable,
            'refField'   => $this->refField,
            'type'       => $this->type,
            'onDelete'   => $this->onDelete,
            'onUpdate'   => $this->onUpdate,
            'orderBy'    => $this->orderBy,
        );
        return $definition;
    }


    /**
     * @param string $orderBy
     */
    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
    }


    /**
     * Gets order by for relation
     *
     * @return string
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }


    /**
     * @return bool
     */
    public function isOneToOne()
    {
        return $this->type === self::ONE_TO_ONE;
    }


    /**
     * @return bool
     */
    public function isOneToMany()
    {
        return $this->type === self::ONE_TO_MANY;
    }


    /**
     * Returns true, if relation name refers to the owning side
     *
     * @param  string $relationName
     * @return bool
     */
    public function isOwningSide($relationName)
    {
        return $relationName === $this->ownerAlias;
    }


    /**
     * Gets referencing owning relation name
     *
     * @return string
     */
    public function getOwnerAlias()
    {
        return $this->ownerAlias;
    }


    /**
     * Gets reference owning table name
     *
     * @return string
     */
    public function getOwnerTable()
    {
        return $this->ownerTable;
    }


    /**
     * @return string
     */
    public function getOwnerField()
    {
        return $this->ownerField;
    }


    /**
     * @return string
     */
    public function getReferencedAlias()
    {
        return $this->refAlias;
    }


    /**
     * @return string
     */
    public function getReferencedTable()
    {
        return $this->refTable;
    }


    /**
     * @return string
     */
    public function getReferencedField()
    {
        return $this->refField;
    }


    /**
     * @return string
     */
    public function getOnDelete()
    {
        return $this->onDelete;
    }


    /**
     * @return string
     */
    public function getOnUpdate()
    {
        return $this->onUpdate;
    }


    /**
     * Gets join table name
     *
     * @param   string $relationName
     * @return  string
     */
    public function getJoinTableName($relationName)
    {
        if ($relationName === $this->ownerAlias) {
            return $this->refTable;
        }
        return $this->ownerTable;
    }


    /**
     * Gets join table
     *
     * @param  \Dive\RecordManager  $rm
     * @param  string               $relationName
     * @return \Dive\Table
     */
    public function getJoinTable(RecordManager $rm, $relationName)
    {
        $joinTableName = $this->getJoinTableName($relationName);
        return $rm->getTable($joinTableName);
    }


    /**
     * Gets join condition
     *
     * @param  string $relationAlias
     * @param  string $tabAlias
     * @param  string $refTabAlias
     * @param  string $quote
     * @return string
     */
    public function getJoinOnCondition($relationAlias, $tabAlias, $refTabAlias, $quote = '')
    {
        $ownerField = $quote . $this->ownerField . $quote;
        $refField = $quote . $this->refField . $quote;
        $tabAliasQuoted = $quote . $tabAlias . $quote;
        $refAliasQuoted = $quote . $refTabAlias . $quote;

        if ($this->isOwningSide($relationAlias)) {
            $ownerField = $tabAliasQuoted . '.' . $ownerField;
            $refField = $refAliasQuoted . '.' . $refField;
        }
        else {
            $ownerField = $refAliasQuoted . '.' . $ownerField;
            $refField = $tabAliasQuoted . '.' . $refField;
        }
        return $ownerField . ' = ' . $refField;
    }


    /**
     * Gets record referenced identifiers
     *
     * @param  Record $record
     * @param  string $relationName
     * @return bool|null|array|string
     *   false:  reference has not been loaded, yet
     *   null:   null-reference / not related
     *   array:  one-to-many related ids
     *   string: referenced id
     */
    public function getRecordReferencedIdentifiers(Record $record, $relationName)
    {
        $id = $record->getIntId();
        $isOwningSide = $this->isOwningSide($relationName);
        if ($isOwningSide) {
            return $record->get($this->ownerField);
        }
        else if (!$this->map->hasReferenced($id)) {
            return false;
        }
        return $this->map->getOwning($id);
    }


    /**
     * Gets query for loading related records
     *
     * @param  Record $record
     * @param  string $relationName
     * @param  array  $identifiers
     * @return \Dive\Query\Query
     */
    private function getReferenceQuery(Record $record, $relationName, array $identifiers)
    {
        $rm = $record->getTable()->getRecordManager();
        $relatedTable = $this->getJoinTable($rm, $relationName);

        $query = $relatedTable->createQuery('a');
        $query->distinct();
        if ($this->isOwningSide($relationName)) {
            $query
                ->leftJoin("a.$this->refAlias b")
                ->whereIn("b.$this->refField", $identifiers);
        }
        else {
            $query->whereIn("a.$this->ownerField", $identifiers);
            if ($this->isOneToMany() && $this->orderBy) {
                if (false !== ($pos = strpos($this->orderBy, '.'))) {
                    list($orderByRelationAlias, $orderByField) = explode('.', $this->orderBy);
                    $query
                        ->leftJoin("a.$orderByRelationAlias b")
                        ->orderBy("b.$orderByField");
                }
                else if ($relatedTable->hasField($this->orderBy)) {
                    $query->orderBy("a.$this->orderBy");
                }
            }
        }
        return $query;
    }


    /**
     * Gets reference for given record
     *
     * @param   Record $record
     * @param   string $relationName
     * @return  null|RecordCollection|Record[]|Record
     */
    public function getReferenceFor(Record $record, $relationName)
    {
        $related = $this->getRecordRelatedByReferences($record, $relationName);
        if ($related !== false) {
            return $related;
        }
        $this->loadReferenceFor($record, $relationName);

        return $this->getRecordRelatedByReferences($record, $relationName);
    }


    /**
     * TODO $related should we allow an array of records for OneToMany-Relations, too??
     *
     * Sets reference for given record
     *
     * @param  Record                                $record
     * @param  string                                $relationName
     * @param  null|RecordCollection|Record[]|Record $related
     * @throws RelationException
     */
    public function setReferenceFor(Record $record, $relationName, $related)
    {
        $id = $record->getInternalIdentifier();

        // owning side (one-to-one/one-to-many)
        if ($this->isOwningSide($relationName)) {
            $this->throwReferenceMustBeRecordOrNullException($relationName, $related);
            $this->updateRecordReference($record, $related);
        }
        // one-to-many (referenced side)
        else if ($this->isOneToMany()) {
            $this->throwReferenceMustBeRecordCollectionException($relationName, $related);
            $map = $this->map;

            // when exchanging collection, we have to unlink all related records
            $relatedCollection = $this->map->getRelatedCollection($record->getOid());
            if ($relatedCollection && $relatedCollection !== $related) {
                foreach ($relatedCollection as $owningRecord) {
                    $owningOid = $owningRecord->getOid();
                    $map->removeFieldMapping($owningOid);
                    $owningRecord->set($this->ownerField, null);
                }
            }

            // set references for new related records
            $oid = $record->getOid();
            $this->map->setReference($id, $related->getIdentifiers());
            if (!$record->exists()) {
                foreach ($related as $relatedRecord) {
                    $map->setFieldMapping($relatedRecord->getOid(), $oid);
                }
            }
            $map->setRelatedCollection($oid, $related);
        }
        // one-to-one (referenced side)
        else {
            $this->throwReferenceMustBeRecordOrNullException($relationName, $related);
            $this->updateRecordReference($related, $record);
        }
    }


    /**
     * Checks, if record has loaded references, or not
     *
     * @param  Record $record
     * @param  string $relationName
     * @return bool
     */
    public function hasReferenceFor(Record $record, $relationName)
    {
        if (!$this->isOwningSide($relationName) && $this->isOneToMany()) {
            $reference = $this->map->getRelatedCollection($record->getOid());
            if ($reference) {
                return true;
            }
        }
        else {
            $refId = $this->getRecordReferencedIdentifiers($record, $relationName);
            if (!$refId) {
                return false;
            }
            $rm = $record->getTable()->getRecordManager();
            $refTable = $this->getJoinTable($rm, $relationName);
            if (is_string($refId) && $refTable->isInRepository($refId)) {
                return true;
            }
        }
        return false;
    }


    private function getRecordRelatedByReferences(Record $record, $relationName)
    {
        // is reference expected as collection
        if (!$this->isOwningSide($relationName) && $this->isOneToMany()) {
            $reference = $this->map->getRelatedCollection($record->getOid());
            if (!$reference) {
                $reference = $this->createRelatedCollection($record);
            }
            if ($reference) {
                return $reference;
            }
        }
        // is reference expected as record
        else {
            $refId = $this->getRecordReferencedIdentifiers($record, $relationName);
            // is a NULL-reference
            if (null === $refId) {
                return null;
            }

            $rm = $record->getTable()->getRecordManager();
            $refTable = $this->getJoinTable($rm, $relationName);
            if (is_string($refId) && $refTable->isInRepository($refId)) {
                return $refTable->getFromRepository($refId);
            }
        }

        return false;
    }


    private function createRelatedCollection(Record $record)
    {
        $relationName = $this->refAlias;
        if (!$this->isOneToMany()) {
            throw new RelationException("Reference type for relation '$relationName' must be a collection!");
        }

        $oid = $record->getOid();
        $refIds = $this->getRecordReferencedIdentifiers($record, $relationName);
        if (is_array($refIds)) {
            $rm = $record->getTable()->getRecordManager();
            $refTable = $this->getJoinTable($rm, $relationName);
            $collection = new RecordCollection($refTable, $record, $this);
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
                $this->map->setRelatedCollection($oid, $collection);
                return $collection;
            }
        }
        return false;
    }


    /**
     * Loads reference for a given record
     *
     * @param  Record $record
     * @param  string $relationName
     */
    private function loadReferenceFor(Record $record, $relationName)
    {
        $recordCollection = $record->getResultCollection();
        if (!$recordCollection) {
            $recordCollection = new RecordCollection($record->getTable());
            $recordCollection->add($record);
        }

        $identifiers = $recordCollection->getIdentifiers();

        $query = $this->getReferenceQuery($record, $relationName, $identifiers);

        /** @var \Dive\Record[]|\Dive\Collection\RecordCollection $relatedCollection */
        $relatedCollection = $query->execute(RecordManager::FETCH_RECORD_COLLECTION);
        $isOwningSide = $this->isOwningSide($relationName);
        if ($isOwningSide) {
            $ownerCollection = $recordCollection;
            $referencedCollection = $relatedCollection;
        }
        else {
            $referencedCollection = $recordCollection;
            $ownerCollection = $relatedCollection;
        }

        foreach ($ownerCollection as $refRecord) {
            $refId = $refRecord->get($this->ownerField);
            $ownerId = $refRecord->getIntId();
            if ($this->isOneToMany()) {
                $this->map->addReference($refId, $ownerId);
            }
            else {
                $this->map->setReference($refId, $ownerId);
            }
        }

        foreach ($referencedCollection as $refRecord) {
            $id = $refRecord->getIntId();
            if (!$this->map->hasReferenced($id)) {
                $this->map->setReference($id, $this->isOneToMany() ? array() : null);
            }
        }
    }


    /**
     * Gets referenced record (for owner field)
     *
     * @param  Record $record
     * @param  string $relationName
     * @throws RelationException
     * @return Record|null
     */
    public function getReferencedRecord(Record $record, $relationName)
    {
        if ($this->isOwningSide($relationName)) {
            return $this->getRecordForOwningSide($record);
        }
        if ($this->isOneToOne()) {
            return $this->getRecordForReferencedSide($record);
        }
        throw new RelationException("Relation '$relationName' does not expected a record as reference!");
    }


    private function getRecordForOwningSide(Record $record)
    {
        $refId = $record->get($this->ownerField);
        if ($refId === null) {
            $oid = $record->getOid();
            if ($this->map->hasFieldMapping($oid)) {
                $refOid = $this->map->getFieldMapping($oid);
                $refRepository = $this->getRefRepository($record, $this->ownerAlias);
                return $refRepository->getByOid($refOid);
            }
        }
        else {
            $refRepository = $this->getRefRepository($record, $this->ownerAlias);
            return $refRepository->getByInternalId($refId);
        }
        return null;
    }


    private function getRecordForReferencedSide(Record $record)
    {
        if ($this->isOneToMany()) {
            throw new RelationException("Relation '$this->refAlias' does not expected a record as reference!");
        }
        $id = $record->getInternalIdentifier();
        if ($this->map->hasReferenced($id)) {
            $refId = $this->map->getOwning($id);
            $refRepository = $this->getRefRepository($record, $this->refAlias);
            return $refRepository->getByInternalId($refId);
        }
        return null;
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
        $tableName = $this->isOwningSide($relationName) ? $this->refTable : $this->ownerTable;
        $refTable = $rm->getTable($tableName);
        return $refTable->getRepository();
    }


    /**
     * @param Record $record
     * @param string $newId
     * @param string $oldId
     */
    public function updateOwningReferenceByForeignKey(Record $record, $newId, $oldId)
    {
        if ($newId != $oldId) {
            // remove old reference
            $this->removeOwningReferenceForeignKey($record, $oldId);
            // setting new reference
            $this->setOwningReferenceByForeignKey($record, $newId);
        }
    }


    private function updateOwningFieldOidMapping(Record $owningRecord, Record $referencedRecord = null)
    {
        $oid = $owningRecord->getOid();
        if ($referencedRecord && !$referencedRecord->exists()) {
            $this->map->setFieldMapping($oid, $referencedRecord->getOid());
        }
        else {
            $this->map->removeFieldMapping($oid);
        }
    }


    /**
     * TODO to be moved!
     * @param Record $owningRecord
     * @return bool|string
     */
    private function getOldReferencedId(Record $owningRecord)
    {
        $oid = $owningRecord->getOid();
        $oldRefId = $owningRecord->getModifiedFieldValue($this->ownerField);
        if (!$oldRefId && $this->map->hasFieldMapping($oid)) {
            $oldRefId = Record::NEW_RECORD_ID_MARK . $this->map->getFieldMapping($oid);
        }
        return $oldRefId;
    }


    /**
     * TODO to be moved!
     * @param        $owningId
     * @param Record $referencedRecord
     */
    private function updateReference($owningId, Record $referencedRecord)
    {
        $refId = $referencedRecord->getInternalIdentifier();
        // add new referenced record id
        if ($this->isOneToOne()) {
            $this->map->setReference($refId, $owningId);
        }
        else if ($owningId) {
            $this->map->addReference($refId, $owningId);
        }
    }


    /**
     * TODO to be moved!
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

        $refId = $referencedRecord ? $referencedRecord->getInternalIdentifier() : null;
        if ($referencedRecord && $this->isOneToOne() && $this->map->hasReferenced($refId)) {
            $oldOwningId = $this->map->getOwning($refId);
            $repositoryOwningSide = $this->getRefRepository($referencedRecord, $this->refAlias);
            if ($repositoryOwningSide->hasByInternalId($oldOwningId)) {
                $oldOwningRecord = $repositoryOwningSide->getByInternalId($oldOwningId);
                $oldOwningRecord->set($this->getOwnerField(), null);
                $this->map->removeFieldMapping($oldOwningRecord->getOid());
            }
        }

        if ($owningRecord) {
            // set field reference id, if referenced record exists in database
            if (!$referencedRecord || $referencedRecord->exists()) {
                $owningRecord->set($this->ownerField, $refId);
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
                $relatedCollection = $this->map->getRelatedCollection($referencedRecord->getOid());
                // TODO exception, or if not set create one??
                if ($relatedCollection) {
                    $relatedCollection->add($owningRecord, null);
                }
            }
        }
    }


    /**
     * TODO to be moved!
     * @param Record $record
     * @param string $oldRefId
     */
    private function removeOwningReferenceForeignKey(Record $record, $oldRefId)
    {
        if (!$oldRefId || !$this->map->hasReferenced($oldRefId)) {
            return;
        }
        if ($this->isOneToMany()) {
            $owningId = $record->getInternalIdentifier();
            if ($this->map->hasReferenced($oldRefId)) {
                $owningReferences = $this->map->getOwning($oldRefId);
                $pos = array_search($owningId, $owningReferences);
                if ($pos !== false) {
                    array_splice($owningReferences, $pos, 1);
                    $this->map->setReference($oldRefId, $owningReferences);
                }
            }

            $refRepository = $this->getRefRepository($record, $this->ownerAlias);
            $oldRefRecord = $refRepository->getByInternalId($oldRefId);
            if ($oldRefRecord) {
                $relatedCollection = $this->map->getRelatedCollection($oldRefRecord->getOid());
                // TODO exception, or if not set create one??
                if ($relatedCollection) {
                    $relatedCollection->remove($owningId);
                }
            }
        }
        else {
            $this->map->setReference($oldRefId, null);
        }
    }


    /**
     * @TODO to be moved!
     * @param Record $record
     * @param string $newId
     */
    private function setOwningReferenceByForeignKey(Record $record, $newId)
    {
        if ($newId === null) {
            return;
        }
        $id = $record->getInternalIdentifier();
        if ($this->isOneToMany()) {
            $refRepository = $this->getRefRepository($record, $this->ownerAlias);
            $newRefRecord = $refRepository->getByInternalId($newId);
            if ($newRefRecord) {
                $relatedCollection = $this->map->getRelatedCollection($newRefRecord->getOid());
                // TODO exception, or if not set create one??
                if ($relatedCollection) {
                    $relatedCollection->add($record);
                }
            }
            $this->map->addReference($newId, $id);
        }
        else {
            $this->map->setReference($newId, $id);
        }
    }


    private function throwReferenceMustBeRecordOrNullException($relationName, $reference)
    {
        if ($reference !== null) {
            if (!($reference instanceof Record)) {
                throw new RelationException(
                    "Reference for relation '$relationName' must be NULL or an instance of \\Dive\\Record!"
                );
            }
            $joinTableName = $this->getJoinTableName($relationName);
            if ($reference->getTable()->getTableName() !== $joinTableName) {
                throw new RelationException(
                    "Reference for relation '$relationName' must be an record of table '$joinTableName'!"
                );
            }
        }
    }


    private function throwReferenceMustBeRecordCollectionException($relationName, $reference)
    {
        if (!($reference instanceof RecordCollection)) {
            throw new RelationException(
                "Reference for relation '$relationName' must be an instance of \\Dive\\Collection\\RecordCollection!"
            );
        }
        $joinTableName = $this->getJoinTableName($relationName);
        if ($reference->getTable()->getTableName() !== $joinTableName) {
            throw new RelationException(
                "RecordCollection for relation '$relationName' must be a collection for table '$joinTableName'!"
            );
        }
    }


    /**
     * Gets references
     *
     * @return array
     *   keys:   owner ids,
     *   values:
     *      one-to-many: referencing ids as array
     *      one-to-one:  referencing id as string
     */
    public function getReferences()
    {
        return $this->map->getMapping();
    }


    /**
     * Clears references
     */
    public function clearReferences()
    {
        $this->map->clear();
    }

}
