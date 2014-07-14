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
        if ($value === null) {
            $this->validateNull($record, $fieldName);
        }
        else {
            $table = $record->getTable();
            $fieldType = $table->getFieldType($fieldName);
            $validator = $this->getDataTypeValidator($fieldType);
            if ($validator->validate($value) === false) {
                $errorStack = $record->getErrorStack();
                $errorStack->add($fieldName, 'type');
            }
        }
    }


    /**
     * @param Record $record
     * @param string $fieldName
     */
    private function validateNull(Record $record, $fieldName)
    {
        $table = $record->getTable();
        if ($table->isFieldNullable($fieldName)) {
            return;
        }

        $referencedRelations = $table->getReferencedRelationsIndexedByOwningField();
        if (isset($referencedRelations[$fieldName])) {
            $relation = $referencedRelations[$fieldName];
            if ($relation->hasReferenceLoadedFor($record, $relation->getReferencedAlias())) {
                return;
            }
        }

        $errorStack = $record->getErrorStack();
        $errorStack->add($fieldName, 'notnull');
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

}
