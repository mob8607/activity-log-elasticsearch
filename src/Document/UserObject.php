<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ElasticsearchActivityLogBundle\Document;

use Ferrandini\Urlizer;
use ONGR\ElasticsearchBundle\Annotation\Object;
use ONGR\ElasticsearchBundle\Annotation\Property;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * @Object
 */
class UserObject
{
    /**
     * @var int
     *
     * @Property(type="integer")
     */
    public $id;

    /**
     * @var string
     *
     * @Property(
     *     type="string",
     *     options={
     *         "fields":{
     *            "raw":{"type":"string", "index":"not_analyzed"},
     *            "folded":{"type":"string", "analyzer":"folding"},
     *            "value":{"type":"string"}
     *         }
     *     }
     * )
     */
    public $username;

    /**
     * Set data.
     *
     * @param UserInterface $user
     *
     * @return $this
     */
    public function setData(UserInterface $user)
    {
        $this->id = $user->getId();
        $this->username = $user->getUsername();

        return $this;
    }

    /**
     * Return username slug.
     *
     * @param string $fallback
     *
     * @return string
     */
    public function getSlug($fallback = 'user')
    {
        return Urlizer::urlize($this->username) ?: $fallback;
    }

    public function __toString()
    {
        return $this->username;
    }
}
