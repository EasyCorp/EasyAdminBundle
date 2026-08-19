<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormEnumTranslationController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Enum\BlogPostStateEnum;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormEnumTranslationControllerTest extends AbstractCrudTestCase
{
    protected EntityRepository $blogPosts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        $this->blogPosts = $this->entityManager->getRepository(BlogPost::class);
    }

    protected function getControllerFqcn(): string
    {
        return FormEnumTranslationController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testFieldsFormatValue()
    {
        $translator = static::getContainer()->get(TranslatorInterface::class);
        static::assertInstanceOf(TranslatorInterface::class, $translator);
        $this->client->request('GET', $this->generateNewFormUrl());

        foreach (BlogPostStateEnum::cases() as $case) {
            self::assertAnySelectorTextSame('option', $case->trans($translator, locale: 'en'));
        }
    }
}
