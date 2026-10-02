<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Orm;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\ToManyAssociationCounter;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ToManyAssociationCounterTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ToManyAssociationCounter $counter;

    protected function setUp(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager = $entityManager;
        $this->entityManager->clear();
        $this->counter = new ToManyAssociationCounter(static::getContainer()->get('doctrine'), static::getContainer()->get('property_accessor'));
    }

    public function testCountsAreNullBeforePreloading(): void
    {
        $blogPosts = $this->createEntityCollection(BlogPost::class, 5);

        $this->assertNull($this->getPreloadedCount($blogPosts->first(), 'categories'));
    }

    public function testCountsMatchTheCollectionsAfterPreloading(): void
    {
        // categories 0 to 9 have 2 blog posts each and the rest have none
        $categories = $this->createEntityCollection(Category::class, 30);
        $this->counter->preload($categories, new FieldCollection([TextField::new('name'), AssociationField::new('blogPosts')]), Crud::PAGE_INDEX);

        $preloadedCounts = array_map(fn (EntityDto $categoryDto) => $this->getPreloadedCount($categoryDto, 'blogPosts'), iterator_to_array($categories));
        $this->assertContains(0, $preloadedCounts);
        $this->assertNull($this->getPreloadedCount($categories->first(), 'name'));

        // the real collections are counted after reading the preloaded values because counting initializes them
        foreach ($categories as $categoryId => $categoryDto) {
            $this->assertSame(\count($categoryDto->getInstance()->getBlogPosts()), $preloadedCounts[$categoryId]);
        }
    }

    public function testGenericFieldsOfToManyAssociationsAreCounted(): void
    {
        $blogPosts = $this->createEntityCollection(BlogPost::class, 20);
        $this->counter->preload($blogPosts, new FieldCollection([Field::new('categories'), Field::new('author')]), Crud::PAGE_INDEX);

        foreach ($blogPosts as $blogPostDto) {
            $this->assertSame(1, $this->getPreloadedCount($blogPostDto, 'categories'));
        }
    }

    public function testFieldsNotDisplayedOnThePageAreNotCounted(): void
    {
        $blogPosts = $this->createEntityCollection(BlogPost::class, 20);
        $this->counter->preload($blogPosts, new FieldCollection([AssociationField::new('categories')->hideOnIndex()]), Crud::PAGE_INDEX);

        $this->assertNull($this->getPreloadedCount($blogPosts->first(), 'categories'));
    }

    public function testSingleEntityIsPreloaded(): void
    {
        $blogPosts = $this->createEntityCollection(BlogPost::class, 1);
        $this->counter->preload($blogPosts, new FieldCollection([AssociationField::new('categories')]), Crud::PAGE_INDEX);

        $this->assertSame(1, $this->getPreloadedCount($blogPosts->first(), 'categories'));
    }

    public function testInitializedCollectionsAreNotCountedButTheOthersAre(): void
    {
        $blogPosts = $this->createEntityCollection(BlogPost::class, 20);
        $blogPosts->first()->getInstance()->getCategories()->count();
        $this->counter->preload($blogPosts, new FieldCollection([AssociationField::new('categories')]), Crud::PAGE_INDEX);

        $this->assertNull($this->getPreloadedCount($blogPosts->first(), 'categories'));
        $this->assertSame(1, $this->getPreloadedCount($blogPosts->get('2'), 'categories'));
        $this->assertSame(1, $this->getPreloadedCount($blogPosts->get('20'), 'categories'));
    }

    public function testCollectionsWithUnflushedChangesAreNotCounted(): void
    {
        $blogPosts = $this->createEntityCollection(BlogPost::class, 20);
        $this->counter->preload($blogPosts, new FieldCollection([AssociationField::new('categories')]), Crud::PAGE_INDEX);
        $this->assertSame(1, $this->getPreloadedCount($blogPosts->first(), 'categories'));

        $blogPosts->first()->getInstance()->addCategory(new Category());

        $this->assertNull($this->getPreloadedCount($blogPosts->first(), 'categories'));
        $this->assertSame(1, $this->getPreloadedCount($blogPosts->get('2'), 'categories'));
    }

    public function testCollectionsOfOtherEntitiesAreNotCounted(): void
    {
        $blogPosts = $this->createEntityCollection(BlogPost::class, 20);
        $this->counter->preload($blogPosts, new FieldCollection([AssociationField::new('categories')]), Crud::PAGE_INDEX);
        $this->assertSame(1, $this->getPreloadedCount($blogPosts->first(), 'categories'));

        $categoriesOfAnotherBlogPost = $blogPosts->get('2')->getInstance()->getCategories();

        $this->assertNull($this->counter->getCount($blogPosts->first(), 'categories', $categoriesOfAnotherBlogPost));
    }

    public function testPreloadingTwiceMergesTheCounts(): void
    {
        $categories = $this->createEntityCollection(Category::class, 30);
        $firstPage = new EntityCollection(\array_slice(iterator_to_array($categories), 0, 10, true));
        $secondPage = new EntityCollection(\array_slice(iterator_to_array($categories), 10, 10, true));
        $fields = new FieldCollection([AssociationField::new('blogPosts')]);

        $this->counter->preload($firstPage, $fields, Crud::PAGE_INDEX);
        $this->counter->preload($secondPage, $fields, Crud::PAGE_INDEX);

        $this->assertSame(2, $this->getPreloadedCount($firstPage->first(), 'blogPosts'));
        $this->assertSame(0, $this->getPreloadedCount($secondPage->first(), 'blogPosts'));

        $this->counter->reset();
        $this->assertNull($this->getPreloadedCount($firstPage->first(), 'blogPosts'));
    }

    private function getPreloadedCount(EntityDto $entityDto, string $propertyName): ?int
    {
        return $this->counter->getCount($entityDto, $propertyName, static::getContainer()->get('property_accessor')->getValue($entityDto->getInstance(), $propertyName));
    }

    /**
     * @param class-string $entityFqcn
     */
    private function createEntityCollection(string $entityFqcn, int $maxResults): EntityCollection
    {
        $entityDto = new EntityDto($entityFqcn, $this->entityManager->getClassMetadata($entityFqcn));

        $entityDtos = [];
        foreach ($this->entityManager->getRepository($entityFqcn)->findBy([], ['id' => 'ASC'], $maxResults) as $entity) {
            $newEntityDto = $entityDto->newWithInstance($entity);
            $entityDtos[$newEntityDto->getPrimaryKeyValueAsString()] = $newEntityDto;
        }

        return new EntityCollection($entityDtos);
    }
}
