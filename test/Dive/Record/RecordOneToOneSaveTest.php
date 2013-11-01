<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Record;

use Dive\Record;
use Dive\Table;
use Dive\TestSuite\ChangeForCommitTestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 02.08.13
 */
class RecordOneToOneSaveTest extends ChangeForCommitTestCase
{

    /**
     * @var array
     */
    protected $tableRows = array(
        'JohnD' => array(
            'user' => array('JohnD'),
            'author' => array(
                'John' => array(
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'jdo@example.com',
                    'User' => 'JohnD',
                    'Editor' => 'SallyK'
                )
            )
        ),
        'SallyK' => array(
            'user' => array('SallyK'),
            'author' => array(
                'SallyK' => array(
                    'firstname' => 'Sally',
                    'lastname' => 'Kingston',
                    'email' => 'ski@example.com',
                    'User' => 'SallyK'
                )
            )
        )
    );


    public function testIncomplete()
    {
        $this->markTestIncomplete();

        //$rm = self::createDefaultRecordManager();
        //$user = $this->createUserWithAuthor($rm, 'JohnD');
    }

}
