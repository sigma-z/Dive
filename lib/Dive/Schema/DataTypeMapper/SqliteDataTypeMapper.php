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

    /**
     * constructor
     *
     * @param array $mapping
     * @param array $ormTypeMapping
     */
    public function __construct(array $mapping = array(), array $ormTypeMapping = array())
    {
        $defaultDataTypeMapping = array(
            'boolean'       => self::OTYPE_BOOLEAN,

            'int'           => self::OTYPE_INTEGER,
            'integer'       => self::OTYPE_INTEGER,
            'tinyint'       => self::OTYPE_INTEGER,
            'smallint'      => self::OTYPE_INTEGER,
            'mediumint'     => self::OTYPE_INTEGER,
            'bigint'        => self::OTYPE_INTEGER,

            'double'        => self::OTYPE_DECIMAL,
            'float'         => self::OTYPE_DECIMAL,
            'real'          => self::OTYPE_DECIMAL,
            'decimal'       => self::OTYPE_DECIMAL,
            'numeric'       => self::OTYPE_DECIMAL,

            'character'     => self::OTYPE_STRING,
            'varchar'       => self::OTYPE_STRING,
            'text'          => self::OTYPE_STRING,

            'date'          => self::OTYPE_DATE,
            'datetime'      => self::OTYPE_DATETIME,
            'time'          => self::OTYPE_TIME,

            'blob'          => self::OTYPE_BLOB
        );

        $defaultOrmTypeMapping = array(
            self::OTYPE_INTEGER => array(
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
            self::OTYPE_STRING => array(
                'default'   => 'text',
                'unit'      => self::UNIT_CHARS,
                'types' => array(
                    'varchar'   => 255
                )
            ),
            self::OTYPE_TIME => 'character',
            self::OTYPE_ENUM => 'varchar'
        );

        $mapping = array_merge($defaultDataTypeMapping, $mapping);
        $ormTypeMapping = array_merge($defaultOrmTypeMapping, $ormTypeMapping);

        parent::__construct($mapping, $ormTypeMapping);
    }

}
