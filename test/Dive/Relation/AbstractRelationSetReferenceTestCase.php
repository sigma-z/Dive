<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 24.04.13
 */

namespace Dive\Test\Relation;

use Dive\RecordManager;
use Dive\TestSuite\TestCase;

class AbstractRelationSetReferenceTestCase extends TestCase
{

    /**
     * @var RecordManager
     */
    private $rm = null;


    protected function setUp()
    {
        parent::setUp();
        $this->rm = self::createDefaultRecordManager();
    }


    protected function tearDown()
    {
        parent::tearDown();
        $this->rm->clearTables();
    }


    protected function createUser($username)
    {
        $table = $this->rm->getTable('user');
        $user = $table->createRecord(array('username' => $username, 'password' => 'my-secret'));
        return $user;
    }


    protected function createAuthor($lastname, $firstname = null)
    {
        $table = $this->rm->getTable('author');
        $authorData = array(
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => 'mail@' . $lastname . '.local'
        );
        $author = $table->createRecord($authorData);
        return $author;
    }


}