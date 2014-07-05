<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Validation;

use Dive\Exception;
use Dive\Record;

/**
 * Class RecordInvalidException
 *
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 05.07.2014
 */
class RecordInvalidException extends Exception
{

    /**
     * @param  Record $record
     * @return RecordInvalidException
     */
    public static function createByRecord(Record $record)
    {
        $errorStack = $record->getErrorStack();
        $message = "Record $record is invalid!\n" . $errorStack;
        return new self($message);
    }

}
