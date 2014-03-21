<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Schema\DataTypeMapper;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 05.11.12
 */
class MysqlDataTypeMapper extends DataTypeMapper
{

    /**
     * constructor
     *
     * @param array $mapping
     * @param array $ormTypeMapping
     */
    public function __construct(array $mapping = array(), array $ormTypeMapping = array())
    {
        $defaultMapping = array(
            'bool'          => 'boolean',
            'boolean'       => 'boolean',

            'int'           => 'integer',
            'integer'       => 'integer',
            'tinyint'       => 'integer',
            'smallint'      => 'integer',
            'mediumint'     => 'integer',
            'bigint'        => 'integer',

            'double'        => 'double',
            'float'         => 'double',
            'real'          => 'double',
            'decimal'       => 'decimal',
            'numeric'       => 'decimal',

            'char'          => 'string',
            'varchar'       => 'string',
            'varbinary'     => 'string',
            'text'          => 'string',
            'tinytext'      => 'string',
            'mediumtext'    => 'string',
            'longtext'      => 'string',

            'date'          => 'date',
            'datetime'      => 'datetime',
            'timestamp'     => 'timestamp',
            'time'          => 'time',
            'year'          => 'integer',

            'tinyblob'      => 'blob',
            'blob'          => 'blob',
            'mediumblob'    => 'blob',
            'longblob'      => 'blob',

            'enum'          => 'enum'
        );

        $defaultOrmTypeMapping = array(
            'integer' => array(
                'default'   => 'int',
                'unit'      => self::UNIT_BYTES,
                'types'     => array(
                    'tinyint'       => 1,
                    'smallint'      => 2,
                    'mediumint'     => 3,
                    'int'           => 4,
                    'bigint'        => 8
                )
            ),
            'string' => array(
                'default'   => 'text',
                'unit'      => self::UNIT_CHARS,
                'types'     => array(
                    'char'          => 1,
                    'varchar'       => 21845,       // utf8 character uses up to 3 bytes
                    'mediumtext'    => 5592405,     // utf8 character uses up to 3 bytes
                    'longtext'      => 1431655765   // utf8 character uses up to 3 bytes
                )
            ),
            'time' => 'char'
        );

        $mapping = array_merge($defaultMapping, $mapping);
        $ormTypeMapping = array_merge($defaultOrmTypeMapping, $ormTypeMapping);

        parent::__construct($mapping, $ormTypeMapping);
    }

}
