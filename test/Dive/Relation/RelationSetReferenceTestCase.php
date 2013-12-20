<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Relation;

use Dive\RecordManager;
use Dive\TestSuite\Model\Author;
use Dive\TestSuite\Model\User;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 24.04.13
 */
abstract class RelationSetReferenceTestCase extends TestCase
{

    /**
     * @var RecordManager
     */
    protected $rm = null;


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


    /**
     * @param  string $username
     * @return User
     */
    protected function createUser($username)
    {
        $table = $this->rm->getTable('user');
        $user = $table->createRecord(array('username' => $username, 'password' => 'my-secret'));
        return $user;
    }


    /**
     * @param  string $lastname
     * @return Author
     */
    protected function createAuthor($lastname)
    {
        $table = $this->rm->getTable('author');
        $authorData = array(
            'firstname' => $lastname,
            'lastname' => $lastname,
            'email' => 'mail@' . $lastname . '.local'
        );
        $author = $table->createRecord($authorData);
        return $author;
    }


}