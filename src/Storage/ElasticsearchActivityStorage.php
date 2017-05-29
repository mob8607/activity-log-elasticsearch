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

use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\WildcardQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use Sulu\Bundle\ElasticsearchActivityLogBundle\Document\ActivityLoggerViewDocument;
use Sulu\Component\ActivityLog\Model\ActivityLogInterface;
use Sulu\Component\ActivityLog\Repository\UserRepositoryInterface;
use Sulu\Component\ActivityLog\Storage\ActivityLogStorageInterface;

/**
 * Implements activity-storage with elasticsearch.
 */
class ElasticsearchActivityStorage implements ActivityLogStorageInterface
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @param Manager $manager
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(Manager $manager, UserRepositoryInterface $userRepository)
    {
        $this->manager = $manager;
        $this->repository = $manager->getRepository(ActivityLoggerViewDocument::class);
        $this->userRepository = $userRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function create($type, $uuid = null)
    {
        return new ActivityLoggerViewDocument($type, $uuid);
    }

    /**
     * {@inheritdoc}
     */
    public function find($uuid)
    {
        /** @var ActivityLoggerViewDocument $activityLog */
        $activityLog = $this->repository->find($uuid);

        if ($activityLog->getCreatorId()) {
            $activityLog->setCreator($this->userRepository->find($activityLog->getCreatorId()));
        }

        if ($activityLog->getParentUuid()) {
            $activityLog->setParent($this->find($activityLog->getParentUuid()));
        }

        return $activityLog;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($page = 1, $pageSize = null)
    {
        $search = $this->repository->createSearch()->addQuery(new MatchAllQuery());

        if (!$pageSize) {
            $search->setScroll('10m');
        } else {
            $search = $search->setFrom(($page - 1) * $pageSize)->setSize($pageSize);
        }

        return $this->execute($search);
    }

    /**
     * {@inheritdoc}
     */
    public function findAllWithSearch(
        $query = null,
        $fields = null,
        $page = 1,
        $pageSize = null,
        $sortColumn = null,
        $sortOrder = null
    ) {
        $search = $this->createQueryForSearch($query, $fields);

        if ($sortColumn && $sortOrder) {
            $search->addSort(new FieldSort($sortColumn . '.raw', $sortOrder));
        }

        if (!$pageSize) {
            $search->setScroll('10m');
        } else {
            $search = $search->setFrom(($page - 1) * $pageSize)->setSize($pageSize);
        }

        return $this->execute($search);
    }

    /**
     * {@inheritdoc}
     */
    public function getCountForAllWithSearch($query = null, $fields = null)
    {
        $search = $this->createQueryForSearch($query, $fields);

        $search->setScroll('10m');

        return $this->repository->count($search);
    }

    /**
     * {@inheritdoc}
     */
    public function findByParent(ActivityLogInterface $activityLog, $page = 1, $pageSize = null)
    {
        $search = $this->repository->createSearch()->addQuery(new TermQuery('parentUuid', $activityLog->getUuid()));

        if (!$pageSize) {
            $search->setScroll('10m');
        } else {
            $search->setFrom(($page - 1) * $pageSize)->setSize($pageSize);
        }

        return $this->execute($search);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(ActivityLogInterface $activityLog)
    {
        $this->manager->persist($activityLog);

        return $activityLog;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->manager->commit();
        $this->manager->flush();
    }

    /**
     * Executes given search and append parents and creators.
     *
     * @param Search $search
     *
     * @return ResultIterator
     */
    private function execute(Search $search)
    {
        $search->addAggregation(new TermsAggregation('parentUuid', 'parentUuid'))
            ->addAggregation(new TermsAggregation('creatorId', 'creatorId'));

        $result = $this->repository->findDocuments($search);

        $aggregation = $result->getAggregation('parentUuid');
        $parentUuids = [];
        foreach ($aggregation->getBuckets() as $bucket) {
            $parentUuids[] = $bucket->getValue();
        }

        $parents = null;
        if (0 < count($parentUuids)) {
            $parents = $this->repository->findByIds($parentUuids);
        }

        $aggregation = $result->getAggregation('creatorId');
        $creatorIds = [];
        foreach ($aggregation->getBuckets() as $bucket) {
            $creatorIds[] = $bucket->getValue();
        }

        $creators = null;
        if (0 < count($creatorIds)) {
            $creators = $this->userRepository->findBy(['id' => $creatorIds]);
        }

        return iterator_to_array(new ResultIterator($result, $parents, $creators));
    }

    /**
     * @param string $query
     * @param array $fields
     *
     * @return Search
     */
    private function createQueryForSearch($query = null, $fields = null)
    {
        $search = $this->repository->createSearch()->addQuery(new MatchAllQuery());

        if (isset($query)) {
            $boolQuery = new BoolQuery();

            foreach ($fields as $field) {
                $boolQuery->add(new WildcardQuery($field, '*' . $query . '*'), BoolQuery::SHOULD);
            }

            $search->addQuery($boolQuery);
        }

        return $search;
    }
}
