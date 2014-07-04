<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Validation\FieldValidator;

use Dive\Record;
use Dive\Schema\DataTypeMapper\DataTypeMapper;
use Dive\Validation\ValidationException;
use Dive\Validation\ValidatorInterface;

/**
 * Class FieldTypeValidator
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 10.06.2014
 */
class FieldTypeValidator implements ValidatorInterface
{

    /** @var DataTypeMapper */
    private $dataTypeMapper;


    /**
     * constructor
     * @param DataTypeMapper $dataTypeMapper
     */
    public function __construct(DataTypeMapper $dataTypeMapper)
    {
        $this->dataTypeMapper = $dataTypeMapper;
    }


    /**
     * @param  Record $record
     * @throws \InvalidArgumentException
     * @return bool
     */
    public function validate($record)
    {
        if (!($record instanceof Record)) {
            throw new \InvalidArgumentException("Expects record instance as #1 argument");
        }
        return $this->validateRecord($record);
    }


    /**
     * @param  Record $record
     * @return bool
     */
    protected function validateRecord(Record $record)
    {
        $table = $record->getTable();
        $modifiedFields = $record->getModifiedFields();
        foreach ($modifiedFields as $fieldName => $oldValue) {
            $field = $table->getField($fieldName);
            $validator = $this->getDataTypeValidator($field['type']);
            $value = $record->get($fieldName);
            if ($validator->validate($value) === false) {
                return false;
            }
        }
        return true;
    }




    /**
     * @param string $fieldType
     * @throws \Dive\Validation\ValidationException
     * @return \Dive\Validation\ValidatorInterface
     */
    public function getDataTypeValidator($fieldType)
    {
        $validator = $this->dataTypeMapper->getOrmTypeInstance($fieldType);
        if ($validator) {
            return $validator;
        }
        throw new ValidationException("No orm data type defined for field type '$fieldType'!");
    }

}