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
 * Class RecordValidator
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 24.07.2014
 */
abstract class RecordValidator implements ValidatorInterface
{

    const CODE_RECORD_UNIQUE = 'unique';
    const CODE_FIELD_NOTNULL = 'notnull';
    const CODE_FIELD_TYPE = 'type';
    const CODE_FIELD_LENGTH = 'length';


    /** @var array */
    private $disabledChecks = array();


    /**
     * @param string $check
     */
    public function addDisabledCheck($check)
    {
        if (!in_array($check, $this->disabledChecks, true)) {
            $this->disabledChecks[] = $check;
        }
    }


    /**
     * @param array $checks
     */
    public function setDisabledChecks(array $checks)
    {
        $this->disabledChecks = $checks;
    }


    /**
     * @return array
     */
    public function getDisabledChecks()
    {
        return $this->disabledChecks;
    }


    /**
     * @param  string $check
     * @return bool
     */
    protected function isCheckDisabled($check)
    {
        return in_array($check, $this->disabledChecks, true);
    }

}
