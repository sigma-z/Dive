<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\TestSuite\Record;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 17.12.13
 */
class Record extends \Dive\Record
{

    /**
     * @param string $fieldName
     */
    public function markFieldAsModified($fieldName)
    {
        if (!$this->isFieldModified($fieldName)) {
            $this->_modifiedFields[$fieldName] = $this->get($fieldName);
        }
    }

}
