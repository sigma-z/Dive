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
use Dive\Validation\ValidationException;
use Dive\Validation\ValidatorInterface;

/**
 * Class FieldTypeValidator
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 10.06.2014
 */
class FieldTypeValidator implements ValidatorInterface
{

    /**
     * @var ValidatorInterface[]
     * keys: field type names
     */
    private $fieldValidators;


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
     * @param string             $fieldType
     * @param ValidatorInterface $validator
     */
    public function addFieldValidator($fieldType, ValidatorInterface $validator)
    {
        $this->fieldValidators[$fieldType] = $validator;
    }


    /**
     * @param  Record $record
     * @return bool
     */
    protected function validateRecord(Record $record)
    {
        $table = $record->getTable();
        $fields = $table->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($this->hasFieldValidator($field['type'])) {
                $validator = $this->getFieldValidator($field['type']);
                if (!$validator->validate($record->get($fieldName))) {
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * @param  string $fieldType
     * @return bool
     */
    public function hasFieldValidator($fieldType)
    {
        return isset($this->fieldValidators[$fieldType]);
    }


    /**
     * @param string $fieldType
     * @throws \Dive\Validation\ValidationException
     * @return \Dive\Validation\ValidatorInterface
     */
    public function getFieldValidator($fieldType)
    {
        if ($this->hasFieldValidator($fieldType)) {
            return $this->fieldValidators[$fieldType];
        }
        throw new ValidationException("No validator defined for field type '$fieldType'!");
    }


    /**
     * @param string $fieldType
     */
    public function removeFieldValidator($fieldType)
    {
        unset($this->fieldValidators[$fieldType]);
    }

}