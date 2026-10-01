<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Factory;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityPaginatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PaginatorDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\PaginatorFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

class PaginatorFactoryEagerFetchedAssociationsTest extends KernelTestCase
{
    public function testToOneAssociationsDisplayedOnThePageAreFetchedEagerly(): void
    {
        $fields = new FieldCollection([
            TextField::new('title'),
            TextField::new('author'),
            Field::new('publisher')->hideOnIndex(),
            AssociationField::new('categories'),
            AssociationField::new('author.name'),
            TextField::new('virtualProperty'),
        ]);

        $this->assertSame([BlogPost::class => ['author']], $this->createPaginatorDto($fields, Crud::PAGE_INDEX)->getEagerFetchedAssociations());
        $this->assertSame([BlogPost::class => ['author', 'publisher']], $this->createPaginatorDto($fields, Crud::PAGE_DETAIL)->getEagerFetchedAssociations());
    }

    public function testFirstAssociationOfNestedPropertiesIsFetchedEagerly(): void
    {
        $fields = new FieldCollection([
            TextField::new('title'),
            AssociationField::new('author.name'),
            TextField::new('categories.name'),
        ]);

        $this->assertSame([BlogPost::class => ['author']], $this->createPaginatorDto($fields, Crud::PAGE_INDEX)->getEagerFetchedAssociations());
    }

    public function testNothingIsFetchedEagerlyWhenNoFieldsAreGiven(): void
    {
        $this->assertSame([], $this->createPaginatorDto(null, Crud::PAGE_INDEX)->getEagerFetchedAssociations());
    }

    public function testAssociationsOfPreviousPaginatorsAreOverwritten(): void
    {
        $this->assertSame([], $this->createPaginatorDto(null, Crud::PAGE_INDEX, [BlogPost::class => ['author']])->getEagerFetchedAssociations());
    }

    /**
     * @param array<class-string, list<string>> $previousEagerFetchedAssociations
     */
    private function createPaginatorDto(?FieldCollection $fields, string $pageName, array $previousEagerFetchedAssociations = []): PaginatorDto
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityDto = new EntityDto(BlogPost::class, $entityManager->getClassMetadata(BlogPost::class));

        $crudDto = new CrudDto();
        $paginatorDto = new PaginatorDto(20, 3, 1, true, null);
        $paginatorDto->setEagerFetchedAssociations($previousEagerFetchedAssociations);
        $crudDto->setPaginator($paginatorDto);
        $crudDto->setPageName($pageName);

        $adminContext = AdminContext::forTesting(RequestContext::forTesting(new Request()), CrudContext::forTesting($crudDto, $entityDto));
        $adminContextProvider = $this->createMock(AdminContextProviderInterface::class);
        $adminContextProvider->method('getContext')->willReturn($adminContext);

        $capturedPaginatorDto = null;
        $entityPaginator = $this->createMock(EntityPaginatorInterface::class);
        $entityPaginator->method('paginate')->willReturnCallback(static function (PaginatorDto $paginatorDto) use (&$capturedPaginatorDto, $entityPaginator) {
            $capturedPaginatorDto = $paginatorDto;

            return $entityPaginator;
        });

        (new PaginatorFactory($adminContextProvider, $entityPaginator))->create($entityManager->createQueryBuilder(), $fields);

        return $capturedPaginatorDto;
    }
}
