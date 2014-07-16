<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Validation\FieldValidator;

use Dive\Validation\ValidatorInterface;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 16.07.2014
 */
interface FieldValidatorInterface extends ValidatorInterface
{

    /**
     * @param  mixed $value
     * @param  array $field
     * @return bool
     */
    public function validateLength($value, array $field);

}
