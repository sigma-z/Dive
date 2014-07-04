<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Validation;

use Dive\Schema\DataTypeMapper\DataTypeMapper;

/**
 * Class FieldLengthValidator
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 04.07.2014
 */
class FieldLengthValidator implements FieldLengthValidatorInterface
{

    /**
     * @param  array $field
     * @param  mixed $value
     * @return bool
     */
    public function validateLength($field, $value)
    {
        $type = isset($field['type']) ? $field['type'] : null;
        switch ($type) {
            case DataTypeMapper::OTYPE_STRING:
                return $this->validateStringLength($field, $value);
        }

        return false;
    }


    private function validateStringLength($field, $value)
    {
    }

}