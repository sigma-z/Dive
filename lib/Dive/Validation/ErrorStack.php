<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Validation;

/**
 * Class ErrorStack
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 05.07.2014
 */
class ErrorStack implements \IteratorAggregate
{

    /** @var array */
    protected $errors = array();


    /**
     * @param string $fieldName
     * @param string $errorCode
     */
    public function add($fieldName, $errorCode)
    {
        $this->errors[$fieldName][] = $errorCode;
    }


    /**
     * @param  string $fieldName
     * @return array
     */
    public function get($fieldName)
    {
        return isset($this->errors[$fieldName])
            ? $this->errors[$fieldName]
            : array();
    }


    /**
     * @param string      $fieldName
     * @param string|null $errorCode
     */
    public function remove($fieldName, $errorCode = null)
    {
        if (!isset($this->errors[$fieldName])) {
            return;
        }

        if ($errorCode) {
            $pos = array_search($errorCode, $this->errors[$fieldName]);
            if ($pos !== false) {
                unset($this->errors[$fieldName][$pos]);
            }
        }
        else {
            unset($this->errors[$fieldName]);
        }
    }


    /**
     * @param string $errorCode
     */
    public function removeErrorCodeInFields($errorCode)
    {
        foreach ($this->errors as $fieldName => $errorCodes) {
            $this->remove($fieldName, $errorCode);
        }
    }


    /**
     * @return int
     */
    public function count()
    {
        return count($this->errors);
    }


    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->errors);
    }


    /**
     * @return array
     */
    public function toArray()
    {
        return $this->errors;
    }


    /**
     * @return string
     */
    public function __toString()
    {
        $string = '';
        foreach ($this->errors as $fieldName => $errorCodes) {
            $string .= '  ' . $fieldName . ': ' . implode(', ', $errorCodes) . "\n";
        }
        return $string;
    }


    /**
     * @return \ArrayIterator|\Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->errors);
    }

}
