<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 15.11.12
 */

return array(
    'sqlite:' . FIXTURE_DIR . '/test.db',
    array(
        'dsn' => 'mysql:host=127.0.0.1;dbname=dive',
        'user' => 'root',
        'password' => ''
    )
);
