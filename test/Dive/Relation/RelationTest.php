<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Relation;

use Dive\Relation\Relation;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 30.10.12
 */
class RelationTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Relation
     */
    private $relation;


    protected function setUp()
    {
        parent::setUp();

        $owningTable = 'author';
        $owningField = 'user_id';
        $owningAlias = 'User';
        $refTable = 'user';
        $refField = 'id';
        $refAlias = 'Author';
        $type = Relation::ONE_TO_ONE;
        $this->relation = new Relation(
            $owningAlias,
            $owningTable,
            $owningField,
            $refAlias,
            $refTable,
            $refField,
            $type
        );
    }


    public function testGetJoinTable()
    {
        $this->assertEquals('author', $this->relation->getJoinTableName('Author'));
        $this->assertEquals('user', $this->relation->getJoinTableName('User'));
    }


    public function testIsOwningSide()
    {
        $this->assertTrue($this->relation->isOwningSide('User'));
        $this->assertFalse($this->relation->isOwningSide('Author'));
    }

}
