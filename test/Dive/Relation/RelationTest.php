<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Relation;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 30.10.12
 */
class RelationTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Dive\Relation\Relation
     */
    private $relation;


    protected function setUp()
    {
        parent::setUp();

        $ownerTable = 'author';
        $ownerField = 'user_id';
        $ownerAlias = 'User';
        $refTable = 'user';
        $refField = 'id';
        $refAlias = 'Author';
        $type = \Dive\Relation\Relation::ONE_TO_ONE;
        $this->relation = new \Dive\Relation\Relation(
            $ownerAlias,
            $ownerTable,
            $ownerField,
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
