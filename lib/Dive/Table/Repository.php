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
 * @created 27.03.13
 */


namespace Dive\Table;

use Dive\Record;
use Dive\Table;


class Repository
{

    /**
     * @var \Dive\Table
     */
    protected $table = null;
    /**
     * @var \Dive\Record[]
     * keys: oid
     */
    protected $records = array();
    /**
     * mapping of record identifiers of existing records to their oid
     * @var array
     * keys: identifiers
     * values: oid's
     */
    protected $identityMap = array();


    /**
     * constructor
     *
     * @param Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }


    /**
     * Gets table
     *
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }


    /**
     * Clears repository
     */
    public function clear()
    {
        $this->records = array();
        $this->identityMap = array();
    }


    /**
     * Adds record to repository
     *
     * @param Record $record
     */
    public function add(Record $record)
    {
        $oid = $record->getOid();
        $this->records[$oid] = $record;
        if ($record->exists()) {
            $this->identityMap[$record->getIdentifierAsString()] = $oid;
        }
    }


    /**
     * Removes record to repository
     *
     * @param Record $record
     */
    public function remove(Record $record)
    {
        $oid = $record->getOid();
        unset($this->records[$oid]);
        if ($record->exists()) {
            unset($this->identityMap[$record->getIdentifierAsString()]);
        }
    }


    /**
     * Returns true, if oid does exist in repository
     *
     * @param  string $oid
     * @return bool
     */
    public function has($oid)
    {
        return isset($this->records[$oid]);
    }


    /**
     * Gets record by oid
     *
     * @param  string $oid
     * @return Record
     * @throws RepositoryException
     */
    public function getByOid($oid)
    {
        if (!$this->has($oid)) {
            throw new RepositoryException("Illegal offset! There is no record for oid '$oid' in repository!");
        }
        return $this->records[$oid];
    }


    /**
     * Returns true, internal id does exist in repository
     *
     * @param  string $id
     * @return bool
     */
    public function hasByInternalId($id)
    {
        $id = (string)$id;
        if ($id && $id[0] == Record::NEW_RECORD_ID_MARK) {
            $oid = substr($id, 1);
            return $this->has($oid);
        }
        return isset($this->identityMap[$id]);
    }


    /**
     * Gets record for given internal id, or false, if record is not in repository
     *
     * @param  string $id
     * @return bool|Record
     */
    public function getByInternalId($id)
    {
        $id = (string)$id;
        $oid = null;
        if ($id && $id[0] == Record::NEW_RECORD_ID_MARK) {
            $oid = substr($id, 1);
        }
        else if (isset($this->identityMap[$id])) {
            $oid = $this->identityMap[$id];
        }
        if ($oid && $this->has($oid)) {
            return $this->records[$oid];
        }
        return false;
    }


    /**
     * Updates identity map on record insert/update or removes identity map entry on record delete
     * Refreshes record identity in repository
     *
     * @param Record $record
     */
    public function refreshIdentity(Record $record)
    {
        $oid = $record->getOid();
        if (!$this->has($oid)) {
            return;
        }
        $id = $record->getIdentifierAsString();
        if ($record->exists()) {
            $this->identityMap[$id] = $record->getOid();
        }
        else {
            unset($this->identityMap[$id]);
        }
    }


    /**
     * Returns the number of records in the repository
     *
     * @return int
     */
    public function count()
    {
        return count($this->records);
    }
}