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
use Dive\Record\Generator\RecordGenerator;
use Dive\RecordManager;
use Dive\Table;
use Dive\TestSuite\TestCase;
use Dive\Util\FieldValuesGenerator;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 02.08.13
 */
class RecordSaveGraphTest extends TestCase
{

    /** @var RecordManager */
    private $rm = null;


    protected function setUp()
    {
        parent::setUp();
        $this->rm = self::createDefaultRecordManager();
    }


    public function testOneToOneRelationOwningSideOnExistingRecord()
    {
        $tableRows = array(
            'user' => array(
                'JohnD' => array('username' => 'JohnD', 'password' => 'secret'),
                'SallyK' => array('username' => 'SallyK', 'password' => 'secret')
            ),
            'author' => array(
                array(
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'jdo@example.com',
                    'User' => 'SallyK'
                ),
                array(
                    'firstname' => 'Sally',
                    'lastname' => 'Kingston',
                    'email' => 'ski@example.com',
                    'User' => 'JohnD'
                )
            )
        );
        $this->createTestRecords($tableRows);

        $userTable = $this->rm->getTable('user');
        $userJohn = $userTable->createQuery()->where('username = ?', 'JohnD')->fetchOneAsObject();
        $this->assertInstanceOf('\Dive\Record', $userJohn);
        $userSally = $userTable->createQuery()->where('username = ?', 'SallyK')->fetchOneAsObject();
        $this->assertInstanceOf('\Dive\Record', $userSally);

        $swapAuthor = $userJohn->Author;
        $userJohn->Author = $userSally->Author;
        $userSally->Author = $swapAuthor;

        $this->rm->save($userJohn);
        $this->rm->save($userSally);
        $this->rm->commit();
    }


    public function testOneToOneRelationReferencedSide()
    {
    }


    /**
     * @param array $tablesRows
     * @param array $tablesMapFields
     */
    private function createTestRecords(array $tablesRows, array $tablesMapFields = array())
    {
        $fvGenerator = new FieldValuesGenerator();
        $recordGenerator = new RecordGenerator($this->rm, $fvGenerator);
        $recordGenerator
            ->setTablesMapField($tablesMapFields)
            ->setTablesRows($tablesRows)
            ->generate();
    }

}
