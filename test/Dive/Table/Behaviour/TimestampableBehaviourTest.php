<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Table\Behaviour;

use Dive\Record;
use Dive\Table\Behaviour\TimestampableBehaviour;
use Dive\TestSuite\Model\Article;
use Dive\TestSuite\Model\Author;
use Dive\TestSuite\Model\User;
use Dive\TestSuite\TestCase;

/**
 * Class TimestampableBehaviourTest
 *
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class TimestampableBehaviourTest extends TestCase
{

    /** @var TimestampableBehaviour */
    private $timestampable;


    protected function setUp()
    {
        parent::setUp();
        $this->timestampable = new TimestampableBehaviour();
    }


    public function testGetTimestamp()
    {
        $actual = $this->timestampable->getTimestamp();
        $this->assertEquals(date('Y-m-d H:i:s'), $actual);
    }


    /**
     * @return Article
     */
    public function testTimestampFieldsAreNull()
    {
        $rm = self::createDefaultRecordManager();

        $userTable = $rm->getTable('user');
        /** @var User $user */
        $user = self::getRecordWithRandomData($userTable);

        $authorTable = $rm->getTable('author');
        /** @var Author $author */
        $author = self::getRecordWithRandomData($authorTable);

        $articleTable = $rm->getTable('article');
        /** @var Article $article */
        $article = self::getRecordWithRandomData($articleTable);

        $user->Author = $author;
        $author->Article[] = $article;

        $this->assertNull($article->created_on);
        $this->assertNull($article->saved_on);
        $this->assertNull($article->changed_on);

        return $article;
    }


    /**
     * @depends testTimestampFieldsAreNull
     * @param  Article $article
     * @return Article
     */
    public function testTimestampOnInsert(Article $article)
    {
        $this->assertFalse($article->exists());

        $rm = $article->getRecordManager();
        $rm->save($article)->commit();

        $this->assertNotNull($article->created_on);
        $this->assertNotNull($article->saved_on);
        $this->assertNull($article->changed_on);

        return $article;
    }


    /**
     * @depends testTimestampOnInsert
     * @param Article $article
     */
    public function testTimestampOnUpdate(Article $article)
    {
        $this->assertTrue($article->exists());

        $article->teaser = 'Teaser changed ...';
        $rm = $article->getRecordManager();
        $rm->save($article)->commit();

        $this->assertNotNull($article->created_on);
        $this->assertNotNull($article->saved_on);
        $this->assertEquals($article->saved_on, $article->changed_on);
    }

}
