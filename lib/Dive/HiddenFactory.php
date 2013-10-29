<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive;


/**
 * @author Steven Nikolic <steven@nindoo.de>
 * Date: 24.10.13
 */
abstract class HiddenFactory
{
    /**
     * constructor is not public so it is only acceccible from an instance of HiddenFactory
     */
    protected function __construct()
    {

    }

    /**
     * record factory
     * @param string $recordClass
     * @param Table  $table
     * @param array  $data
     * @param bool   $exists
     * @return Record
     */
    final protected static function createTableRecord(
        $recordClass,
        Table $table,
        array $data = array(),
        $exists = false
    ) {
        return new $recordClass($table, $exists);
    }


    /**
     * @param string        $tableClass
     * @param RecordManager $recordManager
     * @param array         $tableDefinition
     * @return Table
     */
    final protected static function createRecordManagerTable(
        $tableClass,
        RecordManager $recordManager,
        array $tableDefinition
    ) {
        return new $tableClass(
            $recordManager,
            $tableDefinition['tableName'],
            $tableDefinition['recordClass'],
            $tableDefinition['fields'],
            $tableDefinition['relations'],
            $tableDefinition['indexes']
        );
    }

}
