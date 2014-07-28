<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Schema\DataTypeMapper;

use Dive\Schema\OrmDataType\OrmDataTypeInterface;
use Dive\Util\CamelCase;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 05.11.12
 */
class DataTypeMapper
{

    const UNIT_BYTES = 'bytes';
    const UNIT_CHARS = 'chars';

    const OTYPE_BOOLEAN     = 'boolean';
    const OTYPE_INTEGER     = 'integer';
    const OTYPE_DECIMAL     = 'decimal';
    const OTYPE_STRING      = 'string';
    const OTYPE_DATETIME    = 'datetime';
    const OTYPE_DATE        = 'date';
    const OTYPE_TIME        = 'time';
    const OTYPE_TIMESTAMP   = 'timestamp';
    const OTYPE_BLOB        = 'blob';
    const OTYPE_ENUM        = 'enum';


    /** @var array */
    protected static $ormTypes = array(
        self::OTYPE_BOOLEAN,
        self::OTYPE_INTEGER,
        self::OTYPE_DECIMAL,
        self::OTYPE_STRING,
        self::OTYPE_DATETIME,
        self::OTYPE_DATE,
        self::OTYPE_TIME,
        self::OTYPE_TIMESTAMP,
        self::OTYPE_BLOB,
        self::OTYPE_ENUM
    );

    /** @var array */
    protected $dataTypeMapping = array();

    /** @var array */
    protected $ormTypeMapping = array();

    /** @var OrmDataTypeInterface[] */
    protected $ormDataTypeInstance = array();


    /**
     * constructor
     *
     * @param array                  $mapping
     * @param array                  $ormTypeMapping
     * @param OrmDataTypeInterface[] $ormDataTypeInstance
     */
    public function __construct(array $mapping = array(), array $ormTypeMapping = array(), array $ormDataTypeInstance = array())
    {
        $this->dataTypeMapping = $mapping;
        $this->ormTypeMapping = $ormTypeMapping;
        $this->ormDataTypeInstance = $ormDataTypeInstance;

        $this->createMissingOrmDataTypeInstances();
    }


    private function createMissingOrmDataTypeInstances()
    {
        foreach (self::$ormTypes as $ormDataType) {
            if (!isset($this->ormDataTypeInstance[$ormDataType])) {
                $this->ormDataTypeInstance[$ormDataType] = $this->createOrmDataType($ormDataType);
            }
        }
    }


    /**
     * @param  string $ormDataType
     * @return OrmDataTypeInterface
     */
    private function createOrmDataType($ormDataType)
    {
        $class = "\\Dive\\Schema\\OrmDataType\\" . CamelCase::toCamelCase($ormDataType) . 'OrmDataType';
        return new $class($ormDataType);
    }


    /**
     * adds mapping for data type to orm type
     *
     * @param   string  $dataType
     * @param   string  $ormDataType
     * @return  $this
     */
    public function addDataType($dataType, $ormDataType)
    {
        $this->dataTypeMapping[$dataType] = $ormDataType;
        return $this;
    }


    /**
     * adds mapping for orm type to data type
     *
     * @param   OrmDataTypeInterface $ormDataType
     * @param   string      $dataType
     * @param   int|string  $dataTypeMaxLength
     * @return  $this
     */
    public function addOrmType($ormDataType, $dataType, $dataTypeMaxLength = 'default')
    {
        $type = $ormDataType->getType();
        $this->ormDataTypeInstance[$type] = $ormDataType;

        if ($dataTypeMaxLength === 'default' && (!isset($this->ormTypeMapping[$type]) || is_string($this->ormTypeMapping[$type]))) {
            $this->ormTypeMapping[$type] = $dataType;
        }
        else if ($dataTypeMaxLength === 'default') {
            $this->ormTypeMapping[$type]['default'] = $dataType;
        }
        else {
            $this->ormTypeMapping[$type]['types'][$dataType] = $dataTypeMaxLength;
        }
        return $this;
    }


    /**
     * removes a data type
     *
     * @param   string $dataType
     * @return  $this
     */
    public function removeDataType($dataType)
    {
        if ($this->hasDataType($dataType)) {
            unset($this->dataTypeMapping[$dataType]);
        }
        return $this;
    }


    /**
     * checks, if data type is defined
     *
     * @param  string $dataType
     * @return bool
     */
    public function hasDataType($dataType)
    {
        return isset($this->dataTypeMapping[$dataType]);
    }


    /**
     * checks, if orm data type is defined
     *
     * @param  string $ormDataType
     * @return bool
     */
    public function hasOrmType($ormDataType)
    {
        return isset($this->ormDataTypeInstance[$ormDataType]);
    }


    /**
     * gets orm data type
     *
     * @param  string $dataType
     * @return string|null
     */
    public function getMappedOrmType($dataType)
    {
        return $this->hasDataType($dataType)
            ? $this->dataTypeMapping[$dataType]
            : null;
    }


    /**
     * @param  string $ormDataType
     * @return OrmDataTypeInterface|null
     */
    public function getOrmTypeInstance($ormDataType)
    {
        return isset($this->ormDataTypeInstance[$ormDataType])
            ? $this->ormDataTypeInstance[$ormDataType]
            : null;
    }


    /**
     * gets mapped data type for given orm type
     *
     * @param   string  $ormType
     * @param   int     $length optional, default is null, which stands for undefined
     * @return  string
     */
    public function getMappedDataType($ormType, $length = null)
    {
        if (!isset($this->ormTypeMapping[$ormType])) {
            return $ormType;
        }

        $recommendedDataType = null;
        $biggestDataType = null;
        $defaultType = null;
        $maxLength = null;
        $mapping = $this->ormTypeMapping[$ormType];
        $unit = isset($mapping['unit']) ? $mapping['unit'] : 'chars';

        if (is_string($mapping)) {
            return $mapping;
        }
        else if (isset($mapping['default'])) {
            $defaultType = $mapping['default'];
        }

        if ($length <= 0 && $defaultType) {
            return $defaultType;
        }

        if ($unit === self::UNIT_BYTES) {
            $length = (int)ceil(((log(pow(10, $length)) / log(2))) / 8);
        }
        if (!isset($mapping['types'])) {
            return $defaultType;
        }
        foreach ($mapping['types'] as $dataType => $lengthCompare) {
            if (($maxLength === null || $maxLength > $lengthCompare) && $length <= $lengthCompare) {
                $recommendedDataType = $dataType;
                $maxLength = $lengthCompare;
            }
            else if ($biggestDataType === null || $lengthCompare > $mapping['types'][$biggestDataType]) {
                $biggestDataType = $dataType;
            }
        }

        if (empty($recommendedDataType)
            && $defaultType
            && (!isset($mapping['types'][$defaultType]) || $mapping['types'][$defaultType] >= $length)
        ) {
            return $defaultType;
        }
        return $recommendedDataType ?: $biggestDataType;
    }

}
