<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ElasticsearchActivityLogBundle\Storage;

use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use Sulu\Bundle\ElasticsearchActivityLogBundle\Document\ActivityLoggerViewDocument;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Take result iterator, parents and creator - and combines them.
 */
class ResultIterator extends \IteratorIterator
{
    /**
     * @var ActivityLoggerViewDocument[]
     */
    private $parents = [];

    /**
     * @var UserInterface[]
     */
    private $creators = [];

    /**
     * @param DocumentIterator $iterator
     * @param DocumentIterator $parents
     * @param array $creators
     */
    public function __construct(DocumentIterator $iterator, DocumentIterator $parents = null, array $creators = null)
    {
        parent::__construct($iterator);

        if (null !== $parents) {
            foreach ($parents as $parent) {
                $this->parents[$parent->getUuid()] = $parent;
            }
        }

        if (null !== $creators) {
            foreach ($creators as $creator) {
                $this->creators[$creator->getId()] = $creator;
            }
        }
    }

    /**
     * Set creator and parent to document.
     *
     * @return ActivityLoggerViewDocument
     */
    public function current()
    {
        $result = parent::current();
        if (!$result) {
            return $result;
        }

        $result->setParent(
            array_key_exists($result->getParentUuid(), $this->parents) ? $this->parents[$result->getParentUuid()] : null
        );

        $result->setCreator(
            array_key_exists($result->getCreatorId(), $this->creators) ? $this->creators[$result->getCreatorId()] : null
        );

        return $result;
    }
}
