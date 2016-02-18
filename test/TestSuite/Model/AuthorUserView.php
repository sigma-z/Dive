<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\TestSuite\Model;

use Dive\TestSuite\Record\Record;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 15.11.13
 *
 * @property string $id
 * @property string $firstname
 * @property string $lastname
 * @property string $email
 * @property string $user_id
 * @property string $username
 * @property string $password
 * @property User $User
 */
class AuthorUserView extends Record
{

    public function __toString()
    {
        return parent::__toString() . ' ' . $this->get('username');
    }

}
