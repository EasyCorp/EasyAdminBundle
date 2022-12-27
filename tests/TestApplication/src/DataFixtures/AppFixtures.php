<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\User;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i < 30; ++$i) {
            $category = (new Category())
                ->setName('Category '.$i)
                ->setSlug('category-'.$i);

            $this->addReference('category'.$i, $category);
            $manager->persist($category);
        }

        for ($i = 0; $i < 5; ++$i) {
            $user = (new User())
                ->setName('User '.$i)
                ->setEmail('user'.$i.'@example.com');

            $this->addReference('user'.$i, $user);
            $manager->persist($user);
        }

        for ($i = 0; $i < 20; ++$i) {
            $blogPost = (new BlogPost())
                ->setTitle('Blog Post '.$i)
                ->setSlug('blog-post-'.$i)
                ->setContent('Lorem Ipsum Dolor Sit Amet.')
                ->setCreatedAt(new \DateTime('2020-11-'.($i + 1).' 09:00:00'))
                ->setPublishedAt(new \DateTimeImmutable('2020-11-'.($i + 1).' 11:00:00'))
                ->addCategory($this->getReference('category'.($i % 10)))
                ->setAuthor($this->getReference('user'.($i % 5)));

            $manager->persist($blogPost);
        }

        $manager->flush();
    }
}
