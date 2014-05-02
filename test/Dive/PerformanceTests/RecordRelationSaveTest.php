<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\PerformanceTests;

use Dive\Collection\RecordCollection;
use Dive\Expression;
use Dive\TestSuite\Model\Article;
use Dive\TestSuite\Model\Author;
use Dive\TestSuite\Model\User;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 31.01.14
 */
class RecordRelationSaveTest extends TestCase
{
    /**
     * @param string $database
     * @dataProvider provideDatabaseAwareTestCases
     */
    public function test($database)
    {
        $rm = self::createRecordManager($database);
        $authorTable = $rm->getTable('author');
        $articleTable = $rm->getTable('article');
        $userTable = $rm->getTable('user');

        /** @var User $user */
        $userData = array('username' => 'UserOne', 'password' => 'top-secret');
        $user = $userTable->createRecord($userData);

        /** @var Author $author */
        $authorData = array('firstname' => 'User', 'lastname' => 'One', 'email' => 'user.one@example.com');
        $author = $authorTable->createRecord($authorData);
        $author->User = $user;

        /** @var Article[]|RecordCollection $articles */
        $articles = new RecordCollection($articleTable);
        $itemCount = 1000;
        for ($i = 0; $i < $itemCount; $i++) {
            $articleData = array(
                'title' => 'Title ' . $i,
                'teaser' => 'Teaser ' . $i,
                'text' => 'Text ' . $i,
                'changed_on' => new Expression('CURRENT_TIMESTAMP')
            );
            $articles[] = $articleTable->createRecord($articleData);
        }
        $author->Article = $articles;

        $this->assertCount($itemCount, $author->Article);

        $start = microtime(true);
        $rm->save($author)->commit();
        echo "\n$i: " . (microtime(true) - $start) . "\n";

        $this->assertEquals($itemCount, $articleTable->createQuery()->count());
    }

}
