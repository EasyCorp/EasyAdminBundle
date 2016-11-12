<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppTestBundle\Entity\FunctionalTests\User;

class LoadUsers extends AbstractFixture implements OrderedFixtureInterface
{
    public function getOrder()
    {
        return 10;
    }

    public function load(ObjectManager $manager)
    {
        foreach (range(1, 20) as $i) {
            $user = new User();
            $user->setUsername('user'.$i);
            $user->setEmail('user'.$i.'@example.com');

            $this->addReference('user-'.$i, $user);
            $manager->persist($user);
        }

        $manager->flush();
    }
}
