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
use Dive\TestSuite\TestCase;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @date   27.10.2017
 */
class RecordDeleteConstraintViewTest extends TestCase
{

    /** @var RecordManager */
    private $rm;

    /** @var RecordGenerator */
    private $recordGenerator;


    public function testDeleteConstraintWillBeIgnoredForViews()
    {
        $author = $this->givenIHaveSavedAnAuthor();
        $user = $author->User;
        $this->rm->scheduleDelete($user);
        $this->rm->commit();
    }


    private function givenIHaveARecordGenerator()
    {
        if (!$this->recordGenerator) {
            $this->rm = self::createDefaultRecordManager();
            $this->recordGenerator = self::createRecordGenerator($this->rm);
        }
    }


    /**
     * @return Author
     */
    private function givenIHaveSavedAnAuthor()
    {
        $this->givenIHaveARecordGenerator();
        $userId = $this->recordGenerator->generateRecord('user', [['username' => 'Erwin']]);
        $authorId = $this->recordGenerator->generateRecord('author', [['user_id' => $userId]]);
        return $this->rm->getTable('author')->findByPk($authorId);
    }

}