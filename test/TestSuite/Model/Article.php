<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\TestSuite\Model;

use Dive\Collection\RecordCollection;
use Dive\TestSuite\Record\Record;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 15.11.13
 *
 * @property string $id
 * @property string $author_id
 * @property bool $is_published
 * @property string $title
 * @property string $teaser
 * @property string $text
 * @property string $created_on
 * @property string $saved_on
 * @property string $changed_on
 * @property Author $Author
 * @property Comment[]|RecordCollection $Comment
 * @property Article2tag[]|RecordCollection $Article2tagHasMany
 */
class Article extends Record
{

    public function __toString()
    {
        return parent::__toString() . ' ' . $this->get('title');
    }

}
