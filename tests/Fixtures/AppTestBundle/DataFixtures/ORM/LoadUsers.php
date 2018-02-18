<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\DataFixtures\ORM;

use AppTestBundle\Entity\FunctionalTests\LegacyUser;
use AppTestBundle\Entity\FunctionalTests\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUsers extends AbstractFixture implements OrderedFixtureInterface
{
    public function getOrder()
    {
        return 10;
    }

    public function load(ObjectManager $manager)
    {
        foreach (range(1, 20) as $i) {
            // TODO: remove this check when PHP 5.3 is no longer supported
            $user = class_exists('\DateTimeImmutable') ? new User() : new LegacyUser();
            $user->setUsername('user'.$i);
            $user->setEmail('user'.$i.'@example.com');

            // TODO: remove this check when PHP 5.3 is no longer supported
            if (class_exists('\DateTimeImmutable')) {
                $user->setCreatedAtDateTimeImmutable(new \DateTimeImmutable('October 18th 2005 16:27:36'));
                $user->setCreatedAtDateImmutable(new \DateTimeImmutable('October 18th 2005'));
                $user->setCreatedAtTimeImmutable(new \DateTimeImmutable('16:27:36'));
            } else {
                $user->setCreatedAtDateTimeImmutable(new \DateTime('October 18th 2005 16:27:36'));
                $user->setCreatedAtDateImmutable(new \DateTime('October 18th 2005'));
                $user->setCreatedAtTimeImmutable(new \DateTime('16:27:36'));
            }

            $this->addReference('user-'.$i, $user);
            $manager->persist($user);
        }

        $manager->flush();
    }
}
