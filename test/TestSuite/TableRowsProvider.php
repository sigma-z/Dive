<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\TestSuite;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 15.11.13
 */
class TableRowsProvider
{

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
     *  - Bart Simon    (BartS)
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
     * @return array
     */
    public static function provideTableRows()
    {
        $tableRows = array();
        $tableRows['user'] = array('JohnD', 'JamieTK', 'AdamE', 'BartS', 'CelineH');
        $tableRows['author'] = array(
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
            'Bart Simon' => array(
                'firstname' => 'Bart',
                'lastname' => 'Simon',
                'User' => 'BartS',
                'Editor' => 'John Doe'
            ),
        );
        $tableRows['tag'] = array('News', 'Release Notes', 'Feature', 'Documentation');
        $tableRows['article'] = array(
            'helloWorld' => array(
                'title' => 'Hello world sample',
                'Author' => 'John Doe',
                'is_published' => false
            ),
            'DiveORM released' => array(
                'title' => 'Dive ORM Framework released',
                'Author' => 'John Doe',
                'is_published' => true,
                'datetime' => '2013-01-10 13:48:00',
                'Article2tagHasMany' => array(
                    'DiveORM released#Release Notes' => array('Tag' => 'Release Notes'),
                    'DiveORM released#News' => array('Tag' => 'News')
                ),
                'Comment' => array(
                    'DiveORM released#1' => array(
                        'title' => 'Can\'t wait to see more of this...',
                        'User' => 'BartS',
                        'datetime' => '2013-01-15 17:25:00'
                    )
                )
            ),
            'tableSupport' => array(
                'title' => 'Added table support',
                'Author' => 'Jamie T. Kirk',
                'is_published' => true,
                'datetime' => '2013-01-28 14:28:00',
                'Article2tagHasMany' => array(
                    'tableSupport#Feature' => array('Tag' => 'Feature'),
                    'tableSupport#News' => array('Tag' => 'News')
                ),
                'Comment' => array(
                    'tableSupport#1' => array(
                        'title' => 'Will views be supported, too?',
                        'User' => 'AdamE',
                        'datetime' => '2013-02-01 12:59:00'
                    ),
                    'tableSupport#2' => array(
                        'title' => 'Plz wait until next release',
                        'User' => 'JamieTK',
                        'datetime' => '2013-02-01 14:12:00'
                    ),
                    'tableSupport#3' => array(
                        'title' => 'When will be the next release?',
                        'User' => 'AdamE',
                        'datetime' => '2013-02-01 20:32:00'
                    ),
                    'tableSupport#4' => array(
                        'title' => 'Not sure, yet, but it should be at the end of next month',
                        'User' => 'JamieTK',
                        'datetime' => '2013-02-01 09:25:00'
                    )
                )
            ),
        );

        return $tableRows;
    }


    /**
     * @return array
     */
    public static function provideTableMapFields()
    {
        return array('user' => 'username', 'tag' => 'name');
    }

}
