<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity\DemoEntity;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // create 100 entities for pagination/sorting/search tests
        for ($i = 1; $i <= 100; ++$i) {
            $entity = new DemoEntity();
            $entity->setName('Demo Item '.str_pad((string) $i, 3, '0', \STR_PAD_LEFT));

            // every 3rd item has null values (for hideNullValues test)
            if (0 !== $i % 3) {
                $entity->setPrice('99.99');
                $entity->setQuantity($i * 10);
                $entity->setCreatedAt(new \DateTime('2024-01-'.str_pad((string) (($i % 28) + 1), 2, '0', \STR_PAD_LEFT).' 10:00:00'));
                $entity->setDateField(new \DateTime('2024-06-'.str_pad((string) (($i % 28) + 1), 2, '0', \STR_PAD_LEFT)));
                $entity->setTimeField(new \DateTime('1970-01-01 '.str_pad((string) ($i % 24), 2, '0', \STR_PAD_LEFT).':30:00'));
            }

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
