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
 * Date: 09.11.12
 */
class SqliteDataTypeMapper extends DataTypeMapper
{

    public function __construct(array $mapping = array(), array $ormTypeMapping = array())
    {
        $defaultDataTypeMapping = array(
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

            'character'     => 'string',
            'varchar'       => 'string',
            'text'          => 'string',

            'date'          => 'date',
            'datetime'      => 'datetime',
            'time'          => 'string',

            'blob'          => 'blob'
        );

        $defaultOrmTypeMapping = array(
            'integer' => array(
                'default'   => 'integer',
                'unit'      => self::UNIT_BYTES,
                'types'     => array(
                    'tinyint'       => 1,
                    'smallint'      => 2,
                    'mediumint'     => 3,
                    'integer'       => 4,
                    'int'           => 4,
                    'bigint'        => 8
                )
            ),
            'string' => array(
                'default'   => 'text',
                'unit'      => self::UNIT_CHARS,
                'types' => array(
                    'varchar'   => 255
                )
            ),
            'time'      => 'character',
            'timestamp' => 'integer',
            'enum'      => 'varchar'
        );

        $mapping = array_merge($defaultDataTypeMapping, $mapping);
        $ormTypeMapping = array_merge($defaultOrmTypeMapping, $ormTypeMapping);

        parent::__construct($mapping, $ormTypeMapping);
    }

}
