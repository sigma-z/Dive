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
     * sets reference for a referenced id
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
