<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Record;

use Dive\Record\Generator\RecordGenerator;
use Dive\RecordManager;
use Dive\TestSuite\Model\Author;
use Dive\TestSuite\Model\User;
use Dive\TestSuite\TestCase;
use Dive\Util\FieldValuesGenerator;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @date   10.02.2017
 */
class CreateRecordWithReferenceTest extends TestCase
{

    /** @var RecordManager */
    private $rm;

    /** @var Author */
    private $author;

    /** @var string */
    private $userId;


    public function testRelationReferenceIsSetForNewRecordWhenSettingForeignKeyField()
    {
        $this->givenIHaveARecordManager();
        $this->givenIHaveStoredAUser('Joe');
        $this->givenIHaveClearedTheUserTableRepository();
        $this->givenIHaveCreatedAnAuthor();

        $this->whenISetUserIdForAuthor();

        $this->thenUserIdShouldBeSetForAuthor();
        $this->thenAuthorShouldHaveReferenceSetToUser();
    }


    private function givenIHaveARecordManager()
    {
        $this->rm = self::createDefaultRecordManager();
    }


    /**
     * @param string $userName
     */
    private function givenIHaveStoredAUser($userName)
    {
        $recordGenerator = new RecordGenerator($this->rm, new FieldValuesGenerator());
        $recordGenerator->setTableRows('user', [$userName]);
        $recordGenerator->setTablesMapField(['user' => 'username']);
        $recordGenerator->generate();
        $this->userId = current($recordGenerator->getRecordIds('user'));
    }


    private function givenIHaveCreatedAnAuthor()
    {
        $this->author = $this->rm->getTable('author')->createRecord();
    }


    private function givenIHaveClearedTheUserTableRepository()
    {
        $userTable = $this->rm->getTable('user');
        $userTable->clearRepository();
    }


    private function whenISetUserIdForAuthor()
    {
        $this->author->set('user_id', $this->userId);
    }


    private function thenUserIdShouldBeSetForAuthor()
    {
        $this->assertSame($this->userId, $this->author->get('user_id'));
    }


    private function thenAuthorShouldHaveReferenceSetToUser()
    {
        $user = $this->author->get('User');
        $this->assertNotFalse($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('user', $user->getTable()->getTableName());
    }

}