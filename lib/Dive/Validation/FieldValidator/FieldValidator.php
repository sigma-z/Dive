<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Validation\FieldValidator;

use Dive\Expression;
use Dive\Record;
use Dive\Schema\DataTypeMapper\DataTypeMapper;
use Dive\Validation\RecordValidator;
use Dive\Validation\ValidationException;

/**
 * Class FieldTypeValidator
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 10.06.2014
 */
class FieldValidator extends RecordValidator
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
        $fields = $this->getFieldNamesForValidation($record);
        foreach ($fields as $fieldName) {
            $this->validateFieldValue($record, $fieldName);
        }
        return $record->getErrorStack()->isEmpty();
    }


    /**
     * @param Record $record
     * @param string $fieldName
     */
    protected function validateFieldValue(Record $record, $fieldName)
    {
        $value = $record->get($fieldName);
        if (!$this->canValueBeValidated($value)) {
            return;
        }

        if ($value === null) {
            $isValid = $this->validateNull($record, $fieldName);
            if (!$isValid) {
                $this->addErrorToStack($record, $fieldName, 'notnull');
            }
        }
        else {
            $isValid = $this->validateFieldType($record, $fieldName);
            if (!$isValid) {
                $this->addErrorToStack($record, $fieldName, 'type');
            }
            if ($isValid) {
                $isValid = $this->validateFieldLength($record, $fieldName);
                if (!$isValid) {
                    $this->addErrorToStack($record, $fieldName, 'length');
                }
            }
        }
    }


    /**
     * @param  mixed $value
     * @return bool
     */
    protected function canValueBeValidated($value)
    {
        return !($value instanceof Expression);
    }


    /**
     * @param  Record $record
     * @param  string $fieldName
     * @return bool
     */
    protected function validateNull(Record $record, $fieldName)
    {
        if ($this->isCheckDisabled(self::CODE_FIELD_NOTNULL)) {
            return true;
        }

        $table = $record->getTable();
        if ($table->isFieldNullable($fieldName)) {
            return true;
        }

        $referencedRelations = $table->getReferencedRelationsIndexedByOwningField();
        if (isset($referencedRelations[$fieldName])) {
            $relation = $referencedRelations[$fieldName];
            if ($relation->hasReferenceLoadedFor($record, $relation->getReferencedAlias())) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param  Record $record
     * @param  string $fieldName
     * @return bool
     */
    protected function validateFieldType(Record $record, $fieldName)
    {
        if ($this->isCheckDisabled(self::CODE_FIELD_TYPE)) {
            return true;
        }

        $value = $record->get($fieldName);
        $field = $record->getTable()->getField($fieldName);
        $validator = $this->getDataTypeValidator($field['type']);
        return $validator->validateType($value, $field);
    }


    /**
     * @param  Record $record
     * @param  string $fieldName
     * @return bool
     */
    protected function validateFieldLength(Record $record, $fieldName)
    {
        if ($this->isCheckDisabled(self::CODE_FIELD_LENGTH)) {
            return true;
        }

        $value = $record->get($fieldName);
        $field = $record->getTable()->getField($fieldName);
        $validator = $this->getDataTypeValidator($field['type']);
        return $validator->validateLength($value, $field);
    }


    /**
     * @param string $fieldType
     * @throws \Dive\Validation\ValidationException
     * @return \Dive\Validation\FieldValidator\FieldValidatorInterface
     */
    public function getDataTypeValidator($fieldType)
    {
        $validator = $this->dataTypeMapper->getOrmTypeInstance($fieldType);
        if ($validator) {
            return $validator;
        }
        throw new ValidationException("No orm data type defined for field type '$fieldType'!");
    }


    /**
     * @param  Record $record
     * @return array
     */
    private function getFieldNamesForValidation(Record $record)
    {
        if ($record->exists()) {
            $fields = $record->getModifiedFields();
        }
        else {
            $table = $record->getTable();
            $fields = $table->getFields();
            if ($table->hasAutoIncrementTrigger()) {
                $idFields = $table->getIdentifierFields();
                foreach ($idFields as $idFieldName) {
                    unset($fields[$idFieldName]);
                }
            }
        }
        return array_keys($fields);
    }


    /**
     * @param Record $record
     * @param string $fieldName
     * @param string $errorCode
     */
    private function addErrorToStack(Record $record, $fieldName, $errorCode)
    {
        $record->getErrorStack()->add($fieldName, $errorCode);
    }
}
