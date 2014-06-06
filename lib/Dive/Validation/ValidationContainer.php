<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Validation;

use Dive\Record;

/**
 * Class recordValidationContainer
 *
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 06.06.2014
 */
class ValidationContainer implements ValidatorInterface
{

    /** @var ValidatorInterface[] */
    protected $validators = array();


    /**
     * @param string             $name
     * @param ValidatorInterface $validator
     */
    public function addValidator($name, ValidatorInterface $validator)
    {
        $this->validators[$name] = $validator;
    }


    /**
     * @param string $name
     * @return ValidatorInterface
     * @throws ValidationException
     */
    public function getValidator($name)
    {
        if ($this->hasValidator($name)) {
            return $this->validators[$name];
        }
        throw new ValidationException("Validator with name '$name' is not defined!");
    }


    /**
     * @param  string $name
     * @return bool
     */
    protected function hasValidator($name)
    {
        return isset($this->validators[$name]);
    }


    /**
     * @param  mixed $value
     * @return bool
     */
    public function validate($value)
    {
        foreach ($this->validators as $validator) {
            if (!$validator->validate($value)) {
                return false;
            }
        }
        return true;
    }


}