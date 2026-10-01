<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Orm;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\ToOneAssociationLoader;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Trait\ExecutedQueriesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ToOneAssociationLoaderTest extends KernelTestCase
{
    use ExecutedQueriesTrait;

    private EntityManagerInterface $entityManager;
    private ToOneAssociationLoader $loader;

    protected function setUp(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager = $entityManager;
        $this->entityManager->clear();
        $this->loader = new ToOneAssociationLoader(static::getContainer()->get('doctrine'));
    }

    public function testDisplayedToOneAssociationsAreLoadedWithOneQuery(): void
    {
        $blogPosts = $this->findBlogPosts();
        $this->loader->load($blogPosts, BlogPost::class, new FieldCollection([TextField::new('title'), AssociationField::new('author'), AssociationField::new('publisher')]), Crud::PAGE_INDEX);

        // 'author' and 'publisher' point to the same entity, so they are loaded together
        $this->assertCount(1, $this->getExecutedQueries());
        foreach ($blogPosts as $blogPost) {
            $this->assertFalse($this->isUninitialized($blogPost->getAuthor()));
            $this->assertFalse($this->isUninitialized($blogPost->getPublisher()));
        }
    }

    public function testLoadedEntitiesAreTheSameObjectsReferencedByTheAssociations(): void
    {
        $blogPosts = $this->findBlogPosts();
        $author = $blogPosts[0]->getAuthor();
        $this->loader->load($blogPosts, BlogPost::class, new FieldCollection([AssociationField::new('author')]), Crud::PAGE_INDEX);

        $this->assertSame($author, $blogPosts[0]->getAuthor());
        $this->assertSame($author, $this->entityManager->find($author::class, $author->getId()));
    }

    public function testFirstAssociationOfNestedPropertiesIsLoaded(): void
    {
        $blogPosts = $this->findBlogPosts();
        $this->loader->load($blogPosts, BlogPost::class, new FieldCollection([TextField::new('author.name')]), Crud::PAGE_INDEX);

        $this->assertFalse($this->isUninitialized($blogPosts[0]->getAuthor()));
    }

    public function testFieldsNotDisplayedOnThePageAreNotLoaded(): void
    {
        $fields = new FieldCollection([AssociationField::new('author')->hideOnIndex()]);

        $blogPosts = $this->findBlogPosts();
        $this->loader->load($blogPosts, BlogPost::class, $fields, Crud::PAGE_INDEX);
        $this->assertCount(0, $this->getExecutedQueries());
        $this->assertTrue($this->isUninitialized($blogPosts[0]->getAuthor()));

        $this->loader->load($blogPosts, BlogPost::class, $fields, Crud::PAGE_DETAIL);
        $this->assertFalse($this->isUninitialized($blogPosts[0]->getAuthor()));
    }

    public function testInitializedAssociationsAreNotLoadedAgain(): void
    {
        $blogPosts = $this->findBlogPosts();
        foreach ($blogPosts as $blogPost) {
            $this->entityManager->getUnitOfWork()->initializeObject($blogPost->getAuthor());
        }
        $this->resetExecutedQueries();

        $this->loader->load($blogPosts, BlogPost::class, new FieldCollection([AssociationField::new('author')]), Crud::PAGE_INDEX);

        $this->assertCount(0, $this->getExecutedQueries());
    }

    public function testNonRewindableResultsAreIgnored(): void
    {
        $blogPosts = $this->findBlogPosts();
        $this->loader->load((static fn () => yield from $blogPosts)(), BlogPost::class, new FieldCollection([AssociationField::new('author')]), Crud::PAGE_INDEX);

        $this->assertCount(0, $this->getExecutedQueries());
        $this->assertTrue($this->isUninitialized($blogPosts[0]->getAuthor()));
    }

    /**
     * @return list<BlogPost>
     */
    private function findBlogPosts(): array
    {
        $blogPosts = $this->entityManager->createQuery(sprintf('SELECT entity FROM %s entity ORDER BY entity.id ASC', BlogPost::class))->setMaxResults(20)->getResult();
        $this->resetExecutedQueries();

        return $blogPosts;
    }

    private function isUninitialized(?object $entity): bool
    {
        return $this->entityManager->getUnitOfWork()->isUninitializedObject($entity);
    }
}
