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

use JMS\Serializer\Annotation\Exclude;
use ONGR\ElasticsearchBundle\Annotation\Document;
use ONGR\ElasticsearchBundle\Annotation\Id;
use ONGR\ElasticsearchBundle\Annotation\Property;
use Ramsey\Uuid\Uuid;
use Sulu\Component\ActivityLog\Model\ActivityLogInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Indexable document for activity logs.
 *
 * @Document(type="sulu_activity_log")
 */
class ActivityLoggerViewDocument implements ActivityLogInterface
{
    /**
     * @var string
     *
     * @Id
     */
    protected $uuid;

    /**
     * @var string
     *
     * @Property(
     *     type="keyword",
     *     options={
     *         "fields":{
     *            "raw":{"type":"keyword"}
     *         }
     *     }
     * )
     */
    protected $type;

    /**
     * @var string
     *
     * @Property(
     *     type="keyword",
     *     options={
     *         "fields":{
     *            "raw":{"type":"keyword"}
     *         }
     *     }
     * )
     */
    protected $title;

    /**
     * @var string
     *
     * @Property(
     *     type="keyword",
     *     options={
     *         "fields":{
     *            "raw":{"type":"keyword"}
     *         }
     *     }
     * )
     */
    protected $message;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     *
     * @Property(
     *     type="keyword",
     *     name="dataString",
     *     options={
     *         "fields":{
     *            "raw":{"type":"keyword"}
     *         }
     *     }
     * )
     *
     * @Exclude
     */
    protected $dataString;

    /**
     * @var \DateTime
     *
     * @Property(
     *     type="date",
     *     options={
     *         "fields":{
     *            "raw":{"type":"keyword"}
     *         }
     *     }
     * )
     */
    protected $created;

    /**
     * @var UserObject
     *
     * @Property(
     *     type="integer",
     *     name="creatorId",
     *     options={
     *         "fields":{
     *            "raw":{"type":"integer"}
     *         }
     *     }
     * )
     */
    protected $creatorId;

    /**
     * @var ActivityLogInterface
     */
    protected $parent;

    /**
     * @var string
     *
     * @Property(
     *     type="keyword",
     *     name="parentUuid",
     *     options={
     *         "fields":{
     *            "raw":{"type":"keyword"}
     *         }
     *     }
     * )
     */
    protected $parentUuid;

    /**
     * @var UserInterface
     */
    protected $creator;

    /**
     * @var string
     *
     * @Property(
     *     type="keyword",
     *     name="entityId",
     *     options={
     *         "fields":{
     *            "raw":{"type":"keyword"}
     *         }
     *     }
     * )
     */
    protected $entityId;

    /**
     * @param string $type
     * @param string $uuid
     */
    public function __construct($type = null, $uuid = null)
    {
        $this->type = $type;
        $this->uuid = $uuid ?: Uuid::uuid4()->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set uuid.
     *
     * @param string $uuid
     *
     * @return $this
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function setData($data)
    {
        $this->data = $data;
        $this->dataString = serialize($data);
        $this->entityId = array_key_exists('id', $data) ? $data['id'] : null;

        return $this;
    }

    /**
     * Returns data-string.
     *
     * @return string
     */
    public function getDataString()
    {
        return $this->dataString;
    }

    /**
     * Set data-string.
     *
     * @param string $dataString
     *
     * @return $this
     */
    public function setDataString($dataString)
    {
        $this->dataString = $dataString;
        $this->data = unserialize($dataString);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function setParent(ActivityLogInterface $parent = null)
    {
        $this->parent = $parent;

        $this->parentUuid = null;
        if ($parent) {
            $this->parentUuid = $parent->getUuid();
        }

        return $this;
    }

    /**
     * Returns parentUuid.
     *
     * @return string
     */
    public function getParentUuid()
    {
        return $this->parentUuid;
    }

    /**
     * Set parentUuid.
     *
     * @param string $parentUuid
     *
     * @return $this
     */
    public function setParentUuid($parentUuid)
    {
        $this->parentUuid = $parentUuid;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Returns createdBy.
     *
     * @return UserObject
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }

    /**
     * Set createdBy.
     *
     * @param UserObject $creatorId
     *
     * @return $this
     */
    public function setCreatorId($creatorId)
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->creator = $creator;

        $this->creatorId = null;
        if ($creator) {
            $this->creatorId = $creator->getId();
        }

        return $this;
    }

    /**
     * Returns entityId.
     *
     * @return string
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set entityId.
     *
     * @param string $entityId
     *
     * @return $this
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }
}
