<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 30.10.12
 */

namespace Dive\Relation;

use Dive\Collection\RecordCollection;
use Dive\Record;
use Dive\RecordManager;

class Relation
{

    const ONE_TO_ONE = '1-1';
    const ONE_TO_MANY = '1-m';

    /**
     * @var string
     */
    protected $ownerField;
    /**
     * @var string
     */
    protected $ownerTable;
    /**
     * @var string
     */
    protected $ownerAlias;
    /**
     * @var string
     */
    protected $refField;
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
     * @var array
     */
    protected $references = array();
    /**
     * @var RecordCollection[]
     */
    private $relatedCollections = array();
    /**
     * owning field references
     * @var array
     * keys:   oid (record with foreign key field)
     * values: oid (referenced not-yet-persisted record)
     */
    private $ownerFieldOidMapping = array();


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
    }


    public function getDefinition()
    {
        $definition = array(
            'ownerAlias' => $this->ownerAlias,
            'ownerTable' => $this->ownerTable,
            'ownerField' => $this->ownerField,
            'refAlias'  => $this->refAlias,
            'refTable'  => $this->refTable,
            'refField'  => $this->refField,
            'type'      => $this->type,
            'onDelete'  => $this->onDelete,
            'onUpdate'  => $this->onUpdate,
            'orderBy'   => $this->orderBy,
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
        $id = $record->getInternalIdentifier();
        $isOwningSide = $this->isOwningSide($relationName);
        if ($isOwningSide) {
            return $record->get($this->ownerField);
        }
        else if (!array_key_exists($id, $this->references)) {
            return false;
        }
        return $this->references[$id];
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
     * TODO!!
     * TODO $reference could be an array of records
     *
     * Sets reference for given record
     *
     * @param  Record                                $record
     * @param  string                                $relationName
     * @param  null|RecordCollection|Record[]|Record $reference
     * @throws RelationException
     */
    public function setReferenceFor(Record $record, $relationName, $reference)
    {
        $id = $record->getInternalIdentifier();
        $oid = $record->getOid();

        if ($this->isOwningSide($relationName)) {
            $this->throwReferenceMustBeRecordOrNullException($relationName, $reference);

            if ($reference && !$reference->exists()) {
                $this->ownerFieldOidMapping[$oid] = $reference->getOid();
            }
            else {
                unset($this->ownerFieldOidMapping[$oid]);
            }

            $oldRefId = $record->getModifiedFieldValue($this->ownerField);
            if ($oldRefId && false !== ($pos = array_search($id, $this->references[$oldRefId]))) {
                array_splice($this->references[$oldRefId], $pos, 1);
            }
            if ($reference !== null) {
                $refId = $record->get($this->ownerField);
                // TODO what about new records??
                if ($refId) {
                    $this->addReference($refId, $record->getInternalIdentifier());
                }
            }
        }
        else if ($this->isOneToMany()) {
            $this->throwReferenceMustBeRecordCollectionException($relationName, $reference);
            $this->references[$id] = $reference->getIdentifiers();
            $this->relatedCollections[$oid] = $reference;
        }
        else {
            $this->throwReferenceMustBeRecordOrNullException($relationName, $reference);
            $this->references[$id] = $reference ? $reference->getInternalIdentifier() : null;
            if (!$record->exists()) {
                $this->ownerFieldOidMapping[$reference->getOid()] = $oid;
            }
        }
    }


    private function getRecordRelatedByReferences(Record $record, $relationName)
    {
        // is reference expected as collection
        if (!$this->isOwningSide($relationName) && $this->isOneToMany()) {
            $reference = $this->getRelatedCollection($record);
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


    private function getRelatedCollection(Record $record)
    {
        $relationName = $this->refAlias;
        if (!$this->isOneToMany()) {
            throw new RelationException("Reference type for relation '$relationName' must be a collection!");
        }

        $oid = $record->getOid();
        if (isset($this->relatedCollections[$oid])) {
            return $this->relatedCollections[$oid];
        }

        $refIds = $this->getRecordReferencedIdentifiers($record, $relationName);
        if (is_array($refIds)) {
            $rm = $record->getTable()->getRecordManager();
            $refTable = $this->getJoinTable($rm, $relationName);
            $collection = new RecordCollection($refTable);
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
                $this->relatedCollections[$oid] = $collection;
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
            if ($this->isOneToMany()) {
                $this->addReference($refId, $refRecord->getInternalIdentifier());
            }
            else {
                $this->setReference($refId, $refRecord->getInternalIdentifier());
            }
        }

        foreach ($referencedCollection as $refRecord) {
            $id = $refRecord->getInternalIdentifier();
            if (!array_key_exists($id, $this->references)) {
                $this->references[$id] = $this->isOneToMany() ? array() : null;
            }
        }
    }


    /**
     * Gets referenced record (for owner field)
     *
     * @param  Record $record
     * @return Record|null
     */
    public function getReferencedRecord(Record $record)
    {
        $refId = $record->get($this->ownerField);
        $rm = $record->getTable()->getRecordManager();
        $refTable = $rm->getTable($this->refTable);
        $refRepository = $refTable->getRepository();
        if ($refId === null) {
            $oid = $record->getOid();
            if (isset($this->ownerFieldOidMapping[$oid])) {
                $refOid = $this->ownerFieldOidMapping[$oid];
                $refRecord = $refRepository->getByOid($refOid);
                return $refRecord;
            }
        }
        else {
            return $refRepository->getByInternalId($refId);
        }

        return null;
    }


    private function throwReferenceMustBeRecordOrNullException($relationName, $reference)
    {
        if ($reference !== null && !($reference instanceof Record)) {
            throw new RelationException(
                "Reference for relation '$relationName' must be NULL or an instance of \\Dive\\Record!"
            );
        }
    }


    private function throwReferenceMustBeRecordCollectionException($relationName, $reference)
    {
        if (!($reference instanceof RecordCollection)) {
            throw new RelationException(
                "Reference for relation '$relationName' must be an instance of \\Dive\\Collection\\RecordCollection!"
            );
        }
    }


    /**
     * Sets reference for a referenced id
     *
     * @param  string $id
     * @param  array|string $ownerIdentifier
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setReference($id, $ownerIdentifier)
    {
        if ($this->isOneToOne() && !is_string($ownerIdentifier)) {
            throw new \InvalidArgumentException(
                "One-To-One relation expects referencing identifier to be string!\nYou gave me: "
                    . gettype($ownerIdentifier)
            );
        }
        if ($this->isOneToMany() && !is_array($ownerIdentifier)) {
            throw new \InvalidArgumentException(
                "One-To-One relation expects referencing identifier to be array!\nYou gave me: "
                    . gettype($ownerIdentifier)
            );
        }

        $this->references[$id] = $ownerIdentifier;
        return $this;
    }


    /**
     * Adds owning id for a referenced id
     *
     * @param  string $id
     * @param  string $ownerIdentifier
     * @return $this
     */
    public function addReference($id, $ownerIdentifier)
    {
        $this->references[$id][] = $ownerIdentifier;
        return $this;
    }


    /**
     * Merges references for a referenced id
     *
     * @param  string   $id
     * @param  array    $ownerIdentifier
     * @return $this
     */
    public function mergeReference($id, array $ownerIdentifier)
    {
        if (isset($this->references[$id]) && is_array($this->references[$id])) {
            $ownerIdentifier = array_merge($this->references[$id], $ownerIdentifier);
        }
        return $this->setReference($id, $ownerIdentifier);
    }


    /**
     * Unset reference
     *
     * @param  string $id
     * @return $this
     */
    public function unsetReference($id)
    {
        unset($this->references[$id]);
//        unset($this->relatedCollections[$id]);
        return $this;
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
        return $this->references;
    }


    /**
     * Clears references
     */
    public function clearReferences()
    {
        $this->references = array();
        $this->relatedCollections = array();
        $this->ownerFieldOidMapping = array();
    }

}
