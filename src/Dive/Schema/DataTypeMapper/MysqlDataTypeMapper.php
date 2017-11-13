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
        $defaultMapping = [
            'bool'          => self::OTYPE_BOOLEAN,
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

            'char'          => self::OTYPE_STRING,
            'varchar'       => self::OTYPE_STRING,
            'varbinary'     => self::OTYPE_STRING,
            'text'          => self::OTYPE_STRING,
            'tinytext'      => self::OTYPE_STRING,
            'mediumtext'    => self::OTYPE_STRING,
            'longtext'      => self::OTYPE_STRING,

            'date'          => self::OTYPE_DATE,
            'datetime'      => self::OTYPE_DATETIME,
            'time'          => self::OTYPE_TIME,
            'timestamp'     => self::OTYPE_TIMESTAMP,
            'year'          => self::OTYPE_INTEGER,

            'tinyblob'      => self::OTYPE_BLOB,
            'blob'          => self::OTYPE_BLOB,
            'mediumblob'    => self::OTYPE_BLOB,
            'longblob'      => self::OTYPE_BLOB,

            'enum'          => self::OTYPE_ENUM
        ];

        $defaultOrmTypeMapping = [
            self::OTYPE_INTEGER => [
                'default'   => 'int',
                'unit'      => self::UNIT_BYTES,
                'types'     => [
                    'tinyint'       => 1,
                    'smallint'      => 2,
                    'mediumint'     => 3,
                    'int'           => 4,
                    'bigint'        => 8
                ]
            ],
            self::OTYPE_STRING => [
                'default'   => 'text',
                'unit'      => self::UNIT_CHARS,
                'types'     => [
                    'char'          => 1,
                    'varchar'       => 21845,       // utf8 character uses up to 3 bytes
                    'mediumtext'    => 5592405,     // utf8 character uses up to 3 bytes
                    'longtext'      => 1431655765   // utf8 character uses up to 3 bytes
                ]
            ],
            self::OTYPE_BOOLEAN => 'tinyint'
        ];

        $mapping = array_merge($defaultMapping, $mapping);
        $ormTypeMapping = array_merge($defaultOrmTypeMapping, $ormTypeMapping);

        parent::__construct($mapping, $ormTypeMapping);
    }

}
