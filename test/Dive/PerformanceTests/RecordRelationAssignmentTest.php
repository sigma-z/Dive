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
use Dive\TestSuite\Model\Article;
use Dive\TestSuite\Model\Author;
use Dive\TestSuite\TestCase;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 31.01.14
 */
class RecordRelationAssignmentTest extends TestCase
{

    public function test()
    {
        $rm = self::createDefaultRecordManager();
        $authorTable = $rm->getTable('author');
        $articleTable = $rm->getTable('article');

        /** @var Author $author */
        $author = $authorTable->createRecord();

        $start = microtime(true);
        /** @var Article[]|RecordCollection $articles */
        $articles = new RecordCollection($articleTable);
        $itemCount = 10000;
        for ($i = 0; $i < $itemCount; $i++) {
            $articles[] = $articleTable->createRecord();
        }
        $author->Article = $articles;
        echo "\n$i: " . (microtime(true) - $start) . "\n";

        $this->assertCount($itemCount, $author->Article);
    }

}
