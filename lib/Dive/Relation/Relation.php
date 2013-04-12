<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Relation;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 30.10.12
 */
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
     * checks if alias refers to the owning side
     *
     * @param  string $alias
     * @return bool
     */
    public function isOwningSide($alias)
    {
        return $alias === $this->ownerAlias;
    }


    /**
     * @return string
     */
    public function getOwnerAlias()
    {
        return $this->ownerAlias;
    }


    /**
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
     * @param   string $alias
     * @return  string
     */
    public function getJoinTableName($alias)
    {
        if ($alias === $this->ownerAlias) {
            return $this->refTable;
        }
        return $this->ownerTable;
    }


    /**
     * Gets join table
     *
     * @param  \Dive\RecordManager  $rm
     * @param  string               $alias
     * @return \Dive\Table
     */
    public function getJoinTable(RecordManager $rm, $alias)
    {
        $joinTableName = $this->getJoinTableName($alias);
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
     * possible return values are:
     *   false:  relations is not loaded, yet
     *   null:   not related
     *   array:  to many relation
     *   string: to one relation
     * @param  Record $record
     * @param  string $relationAlias
     * @return bool|null|array|string
     */
    public function getRecordReferencedIdentifiers(Record $record, $relationAlias)
    {
        $id = $record->getInternalIdentifier();
        $isOwningSide = $this->isOwningSide($relationAlias);
        if (!$isOwningSide && !array_key_exists($id, $this->references)) {
            return false;
        }

        if ($isOwningSide) {
            return $record->getOwnerFieldInternalIdentifier($this->ownerField);
        }
        return $this->references[$id];
    }


    /**
     * Loads references (via result collection from record)
     *
     * @param Record $record
     * @param string $relationName
     */
    public function loadReferences(Record $record, $relationName)
    {
        $recordCollection = $record->getResultCollection();
        if (!$recordCollection) {
            return;
        }

        $query = $this->getReferenceQuery($record, $relationName, $recordCollection->getIdentifiers());
        /** @var \Dive\Record[] $relatedCollection */
        $relatedCollection = $query->execute(RecordManager::FETCH_RECORD_COLLECTION);

        if ($this->isOwningSide($relationName)) {
            $referencingCollection = $recordCollection;
            $referencedCollection = $relatedCollection;
        }
        else {
            $referencedCollection = $recordCollection;
            $referencingCollection = $relatedCollection;
        }

        foreach ($referencingCollection as $record) {
            $referencingField = $this->ownerField;
            $referencingId = $record->get($referencingField);
            $this->addReference($referencingId, $record->getInternalIdentifier());
        }

        foreach ($referencedCollection as $record) {
            $id = $record->getInternalIdentifier();
            if (!array_key_exists($id, $this->references)) {
                $this->references[$id] = $this->isOneToMany() ? array() : null;
            }
        }
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
     * @param   Record $record
     * @param   string $relationName
     * @return  null|RecordCollection|Record[]|Record
     */
    public function getReferenceFor(Record $record, $relationName)
    {
        $relatedIds = $this->getRecordReferencedIdentifiers($record, $relationName);
        // is a NULL-reference
        if (null === $relatedIds) {
            return null;
        }

        $rm = $record->getTable()->getRecordManager();
        $relatedTable = $this->getJoinTable($rm, $relationName);
        if (!$this->isOwningSide($relationName) && $this->isOneToMany()) {
            $oid = $record->getOid();
            if (isset($this->relatedCollections[$oid])) {
                return $this->relatedCollections[$oid];
            }
            if (is_array($relatedIds)) {
                $related = new RecordCollection($relatedTable);
                $recordsInRepository = true;
                foreach ($relatedIds as $relatedId) {
                    if (!$relatedTable->isInRepository($relatedId)) {
                        $recordsInRepository = false;
                        break;
                    }
                    $relatedRecord = $relatedTable->getFromRepository($relatedId);
                    $related->add($relatedRecord, $relatedId);
                }
                if ($recordsInRepository) {
                    $this->relatedCollections[$oid] = $related;
                    return $related;
                }
            }
        }
        else if (is_string($relatedIds) && $relatedTable->isInRepository($relatedIds)) {
            return $relatedTable->getFromRepository($relatedIds);
        }

        return $this->loadReferenceFor($record, $relationName);
    }


    /**
     * Loads reference for a given record
     *
     * @param  Record   $record
     * @param  string   $relationName
     * @return Record|Record[]|RecordCollection
     */
    private function loadReferenceFor(Record $record, $relationName)
    {
        $fetchMode = RecordManager::FETCH_RECORD;
        if ($this->isOwningSide($relationName)) {
            $ownerField = $this->ownerField;
            if (null === $record->get($ownerField)) {
                return null;
            }
        }
        else if ($this->isOneToMany()) {
            $fetchMode = RecordManager::FETCH_RECORD_COLLECTION;
        }
        $identifier = $record->getIdentifierAsString();
        $query = $this->getReferenceQuery($record, $relationName, array($identifier));
        return $query->execute($fetchMode);
    }


    /**
     * Sets reference for a referenced id
     *
     * @param  string $id
     * @param  array|string $ownerIdentifier
     * @throws \InvalidArgumentException
     * @return Relation
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
     * adds referencing id for a referenced id
     *
     * @param string $id
     * @param string $ownerIdentifier
     * @return Relation
     */
    public function addReference($id, $ownerIdentifier)
    {
        $this->references[$id][] = $ownerIdentifier;
        return $this;
    }


    /**
     * merges references for a referenced id
     *
     * @param  string   $id
     * @param  array    $ownerIdentifier
     * @return Relation
     */
    public function mergeReference($id, array $ownerIdentifier)
    {
        if (isset($this->references[$id]) && is_array($this->references[$id])) {
            $ownerIdentifier = array_merge($this->references[$id], $ownerIdentifier);
        }
        return $this->setReference($id, $ownerIdentifier);
    }


    /**
     * unset reference
     *
     * @param  string $id
     * @return Relation
     */
    public function unsetReference($id)
    {
        unset($this->references[$id]);
//        unset($this->relatedCollections[$id]);
        return $this;
    }


    /**
     * gets references
     *
     * @return array keys: referenced ids, values: array of referencing ids
     */
    public function getReferences()
    {
        return $this->references;
    }


    /**
     * clear references
     */
    public function clearReferences()
    {
        $this->references = array();
//        $this->relatedCollections = array();
    }

}
