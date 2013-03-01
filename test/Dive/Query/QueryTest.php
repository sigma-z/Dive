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
 * @created 01.03.13
 */

namespace Dive\Test\Query;

use Dive\Query\Query;
use Dive\Table;
use Dive\TestSuite\DatasetTestCase;

class QueryTest extends DatasetTestCase
{

    /**
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function testFetchOneAsObject($database)
    {
        // prepare
        $rm = self::createRecordManager($database);
        $table = $rm->getTable('user');
        $data = array(
            'username' => 'John Doe',
            'password' => 'my secret'
        );
        $id = self::insertDataset($table, $data);

        // execute unit
        /** @var $query Query */
        $query = $table->createQuery()
            ->where('id = ?', $id);
        $record = $query->fetchOneAsObject();

        // assert
        $this->assertInstanceOf('\Dive\Record', $record);
        $this->assertEquals('user', $record->getTable()->getTableName());
        $this->assertEquals($id, $record->id);
    }

}