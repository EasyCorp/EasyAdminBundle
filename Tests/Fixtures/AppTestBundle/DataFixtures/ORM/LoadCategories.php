<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppTestBundle\Entity\FunctionalTests\Category;

class LoadCategories extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        foreach (range(1, 100) as $i) {
            $category = new Category();
            $category->setName('Parent Category #'.$i);

            $this->addReference('parent-category-'.$i, $category);
            $manager->persist($category);
        }

        $manager->flush();

        foreach (range(1, 100) as $i) {
            $category = new Category();
            $category->setName('Category #'.$i);
            $category->setParent($this->getReference('parent-category-'.$i));

            $this->addReference('category-'.$i, $category);
            $manager->persist($category);
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 10;
    }
}
