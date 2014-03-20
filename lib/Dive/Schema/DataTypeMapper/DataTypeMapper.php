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
class DataTypeMapper
{

    const UNIT_BYTES = 'bytes';
    const UNIT_CHARS = 'chars';


    /** @var array */
    protected static $ormTypes = array(
        'boolean', 'integer', 'double', 'decimal', 'string', 'datetime', 'date', 'time', 'timestamp', 'blob', 'enum'
    );

    /** @var array */
    protected $dataTypeMapping = array();

    /** @var array */
    protected $ormTypeMapping = array();


    /**
     * constructor
     *
     * @param array $mapping
     * @param array $ormTypeMapping
     */
    public function __construct(array $mapping = array(), array $ormTypeMapping = array())
    {
        $this->dataTypeMapping = $mapping;
        $this->ormTypeMapping = $ormTypeMapping;
    }


    /**
     * adds mapping for data type to orm type
     *
     * @param   string  $dataType
     * @param   string  $ormDataType
     * @return  DataTypeMapper
     */
    public function addDataType($dataType, $ormDataType)
    {
        $this->dataTypeMapping[$dataType] = $ormDataType;
        return $this;
    }


    /**
     * adds mapping for orm type to data type
     *
     * @param   string      $ormDataType
     * @param   string      $dataType
     * @param   int|string  $dataTypeMaxLength
     * @return  DataTypeMapper
     */
    public function addOrmType($ormDataType, $dataType, $dataTypeMaxLength = 'default')
    {
        if ($dataTypeMaxLength === 'default'
            && (!isset($this->ormTypeMapping[$ormDataType]) || is_string($this->ormTypeMapping[$ormDataType])))
        {
            $this->ormTypeMapping[$ormDataType] = $dataType;
        }
        else if ($dataTypeMaxLength === 'default') {
            $this->ormTypeMapping[$ormDataType]['default'] = $dataType;
        }
        else {
            $this->ormTypeMapping[$ormDataType]['types'][$dataType] = $dataTypeMaxLength;
        }
        return $this;
    }


    /**
     * removes a data type
     *
     * @param   string $dataType
     * @return  DataTypeMapper
     */
    public function removeDataType($dataType)
    {
        if ($this->hasDataType($dataType)) {
            unset($this->dataTypeMapping[$dataType]);
        }
        return $this;
    }


    /**
     * removes an orm type
     *
     * @param   string $ormDataType
     * @return  DataTypeMapper
     */
    public function removeOrmType($ormDataType)
    {
        if ($this->hasOrmType($ormDataType)) {
            unset($this->ormTypeMapping[$ormDataType]);
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
        return isset($this->ormTypeMapping[$ormDataType]);
    }


    /**
     * gets orm data type
     *
     * @param  string $dataType
     * @return string|null
     */
    public function getOrmType($dataType)
    {
        return $this->hasDataType($dataType)
            ? $this->dataTypeMapping[$dataType]
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
            && (!isset($mapping['types'][$defaultType]) || $mapping['types'][$defaultType] >= $length))
        {
            return $defaultType;
        }
        return $recommendedDataType ?: $biggestDataType;
    }

}
