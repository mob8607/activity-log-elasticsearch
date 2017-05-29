<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ElasticsearchActivityLogBundle\Tests\Storage;

use ONGR\ElasticsearchBundle\Service\Manager;
use Prophecy\Argument;
use Sulu\Bundle\ElasticsearchActivityLogBundle\Storage\ElasticsearchActivityStorage;
use Sulu\Component\ActivityLog\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ElasticsearchActivityStorageTest extends KernelTestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ElasticsearchActivityStorage
     */
    private $storage;

    /**
     * @var UserInterface
     */
    private $user;

    public function setUp()
    {
        /** @var Manager $manager */
        $manager = $this->getContainer()->get('es.manager.live');
        $manager->dropAndCreateIndex();

        $userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->user = $this->prophesize(TestUserInterface::class);

        $userRepository->findOneById(Argument::any())->willReturn($this->user->reveal());

        $this->storage = new ElasticsearchActivityStorage(
            $manager,
            $userRepository->reveal()
        );
    }

    public function testPersist()
    {
        $activity = $this->storage->create('test')->setData(['test' => 'test'])->setCreator($this->user->reveal());

        $this->storage->persist($activity);
        $this->storage->flush();

        $result = $this->storage->find($activity->getUuid());
        $this->assertEquals($activity->getUuid(), $result->getUuid());
        $this->assertEquals($activity->getData(), $result->getData());
        $this->assertEquals($activity->getCreator(), $this->user->reveal());

        return $activity;
    }

    public function testPersistChildren()
    {
        $parentActivity = $this->storage->create('test');
        $childActivity = $this->storage->create('test')->setParent($parentActivity)->setCreator($this->user->reveal());

        $this->storage->persist($parentActivity);
        $this->storage->persist($childActivity);
        $this->storage->flush();

        $result = $this->storage->findByParent($parentActivity, 1, 10);

        $this->assertCount(1, $result);
        $this->assertEquals($childActivity->getUuid(), $result[0]->getUuid());
    }

    public function testFind()
    {
        $parentActivity = $this->storage->create('test');
        $childActivity = $this->storage->create('test')->setParent($parentActivity)->setCreator($this->user->reveal());

        $this->storage->persist($parentActivity);
        $this->storage->persist($childActivity);
        $this->storage->flush();

        $result = $this->storage->find($childActivity->getUuid());

        $this->assertEquals($childActivity->getUuid(), $result->getUuid());
        $this->assertEquals($parentActivity->getUuid(), $result->getParent()->getUuid());
        $this->assertEquals($childActivity->getCreator(), $this->user->reveal());
    }

    public function testFindAll()
    {
        $parentActivity = $this->storage->create('test')->setCreator($this->user->reveal());
        $childActivity = $this->storage->create('test')->setParent($parentActivity)->setCreator($this->user->reveal());

        $this->storage->persist($parentActivity);
        $this->storage->persist($childActivity);
        $this->storage->flush();

        $result = $this->storage->findAll(1, 10);

        $this->assertCount(2, $result);
        $this->assertEquals($parentActivity->getUuid(), $result[0]->getUuid());
        $this->assertEquals($childActivity->getUuid(), $result[1]->getUuid());
        $this->assertEquals($parentActivity->getUuid(), $result[1]->getParent()->getUuid());
        $this->assertEquals($childActivity->getCreator(), $this->user->reveal());
        $this->assertEquals($parentActivity->getCreator(), $this->user->reveal());
    }

    public function testFindAllWithSearch()
    {
        $activity = $this->storage->create('test')->setMessage('Message which should be found');
        $activity2 = $this->storage->create('test')->setMessage('Secret message');
        $activity3 = $this->storage->create('test')->setMessage('Another message which should be found');
        $activity4 = $this->storage->create('test')->setMessage('Another secret message');
        $activity5 = $this->storage->create('test')->setMessage('Third message which should be found');
        $activity6 = $this->storage->create('test')->setMessage('Third secret message');

        $this->storage->persist($activity);
        $this->storage->persist($activity2);
        $this->storage->persist($activity3);
        $this->storage->persist($activity4);
        $this->storage->persist($activity5);
        $this->storage->persist($activity6);
        $this->storage->flush();

        $result = $this->storage->findAllWithSearch();
        $resultWithSearch = $this->storage->findAllWithSearch('found', ['message']);
        $sortedResultWithSearch = $this->storage->findAllWithSearch('found', ['message'], 1, 2, 'message', 'desc');

        $this->assertCount(6, $result);

        $this->assertCount(3, $resultWithSearch);
        $this->assertEquals($activity->getUuid(), $resultWithSearch[0]->getUuid());
        $this->assertEquals($activity3->getUuid(), $resultWithSearch[1]->getUuid());
        $this->assertEquals($activity5->getUuid(), $resultWithSearch[2]->getUuid());

        $this->assertCount(2, $sortedResultWithSearch);
        $this->assertEquals($activity->getUuid(), $sortedResultWithSearch[1]->getUuid());
        $this->assertEquals($activity5->getUuid(), $sortedResultWithSearch[0]->getUuid());
    }

    public function testFindAllWithSearchAndSorting()
    {
        $activity = $this->storage->create('test')->setMessage('Message which should be found');
        $activity2 = $this->storage->create('test')->setMessage('Secret message');
        $activity3 = $this->storage->create('test')->setMessage('Another message which should be found');
        $activity4 = $this->storage->create('test')->setMessage('Another secret message');

        $this->storage->persist($activity);
        $this->storage->persist($activity2);
        $this->storage->persist($activity3);
        $this->storage->persist($activity4);
        $this->storage->flush();

        $result = $this->storage->findAllWithSearch('found', ['message']);

        $this->assertCount(2, $result);
        $this->assertEquals($activity->getUuid(), $result[0]->getUuid());
        $this->assertEquals($activity3->getUuid(), $result[1]->getUuid());
    }

    /**
     * Return the test container.
     *
     * This container will use the default configuration of the kernel.
     *
     * If you require the container for a different kernel environment
     * you should create a new Kernel with the `getKernel` method and
     * retrieve the Container from that.
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        if ($this->container) {
            return $this->container;
        }

        self::bootKernel();

        $this->container = self::$kernel->getContainer();

        return $this->container;
    }
}

interface TestUserInterface extends UserInterface
{
    public function getId();
}
