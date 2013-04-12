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
 * @created 05.04.13
 */

namespace Dive\Record;

use Dive\TestSuite\TestCase;
use Dive\Util\FieldValuesGenerator;
use Dive\Record\Generator\RecordGenerator;

class RelationLoadingTest extends TestCase
{

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::createTestData();
    }


    /**
     * creates test data
     * Users
     *  - JohnD
     *  - JamieTK
     *  - AdamE
     *  - BartS
     *  - CelineH
     *
     * Author
     *  - John Doe      (JohnD)
     *  - Jamie T. Kirk (JamieTK)
     *
     * Tags
     *  - News
     *  - Release Notes
     *  - Feature
     *  - Documentation
     *
     * Article
     *  - Hello world sample                    (by John Doe)
     *
     *  - Dive ORM Framework released           (by John Doe)
     *    Tags: Release Notes, News
     *    Comment:
     *    - Can't wait to see more of this...   (by BartS)
     *
     *  - Added table support                   (by Jamie T. Kirk)
     *    Tags: Feature, News
     *    Comment:
     *    - Will views be supported, too?       (by AdamE)
     *    - Plz wait until next release         (by JamieTK)
     *    - When will be the next release?      (by AdamE)
     *    - Not sure, yet, but it should be at the end of next month    (by JamieTK)
     */
    private static function createTestData()
    {
        $users = array('JohnD', 'JamieTK', 'AdamE', 'BartS', 'CelineH');
        $authors = array(
            'John Doe' => array(
                'firstname' => 'John',
                'lastname' => 'Doe',
                'User' => 'JohnD'
            ),
            'Jamie T. Kirk' => array(
                'firstname' => 'Jamie T',
                'lastname' => 'Kirk',
                'User' => 'JamieTK'
            ),
        );
        $tags = array('News', 'Release Notes', 'Feature', 'Documentation');
        $articles = array(
            array(
                'title' => 'Hello world sample',
                'Author' => 'John Doe',
                'is_published' => false
            ),
            array(
                'title' => 'Dive ORM Framework released',
                'Author' => 'John Doe',
                'is_published' => true,
                'datetime' => '2013-01-10 13:48:00',
                'Article2tagHasMany' => array(
                    array('Tag' => 'Release Notes'),
                    array('Tag' => 'News')
                ),
                'Comment' => array(
                    array(
                        'title' => 'Can\'t wait to see more of this...',
                        'User' => 'BartS',
                        'datetime' => '2013-01-15 17:25:00'
                    )
                )
            ),
            array(
                'title' => 'Added table support',
                'Author' => 'Jamie T. Kirk',
                'is_published' => true,
                'datetime' => '2013-01-28 14:28:00',
                'Article2tagHasMany' => array(
                    array('Tag' => 'Feature'),
                    array('Tag' => 'News')
                ),
                'Comment' => array(
                    array(
                        'title' => 'Will views be supported, too?',
                        'User' => 'AdamE',
                        'datetime' => '2013-02-01 12:59:00'
                    ),
                    array(
                        'title' => 'Plz wait until next release',
                        'User' => 'JamieTK',
                        'datetime' => '2013-02-01 14:12:00'
                    ),
                    array(
                        'title' => 'When will be the next release?',
                        'User' => 'AdamE',
                        'datetime' => '2013-02-01 20:32:00'
                    ),
                    array(
                        'title' => 'Not sure, yet, but it should be at the end of next month',
                        'User' => 'JamieTK',
                        'datetime' => '2013-02-01 09:25:00'
                    )
                )
            ),
        );

        $rm = self::createDefaultRecordManager();
        $fvGenerator = new FieldValuesGenerator();
        $recordGenerator = new RecordGenerator($rm, $fvGenerator);
        $recordGenerator->setTableRows('user', $users, 'username');
        $recordGenerator->setTableRows('author', $authors);
        $recordGenerator->setTableRows('tag', $tags, 'name');
        $recordGenerator->setTableRows('article', $articles);
        $recordGenerator->generate();
    }


    public function test()
    {
        $this->markTestIncomplete();
    }

}