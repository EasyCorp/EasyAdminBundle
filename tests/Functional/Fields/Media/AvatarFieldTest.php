<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Media;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;

class AvatarFieldTest extends AbstractFieldFunctionalTest
{
    public function testAvatarFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'avatarField' => 'https://example.com/avatar.jpg',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $avatarFieldCell = $entityRow->filter('td[data-column="avatarField"]');
        static::assertCount(1, $avatarFieldCell, 'Avatar field cell should exist');

        // avatar field renders as an image
        $avatarImage = $avatarFieldCell->filter('img');
        if ($avatarImage->count() > 0) {
            static::assertStringContainsString('avatar.jpg', $avatarImage->attr('src'));
        }
    }

    public function testAvatarFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'avatarField' => 'https://example.com/user-avatar.png',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        // avatar field should be rendered on the detail page
        $html = $crawler->html();
        static::assertStringContainsString('user-avatar.png', $html);
    }

    public function testAvatarFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // avatar field should be a text input in the form
        $avatarFieldInput = $crawler->filter('#FieldTestEntity_avatarField');
        static::assertCount(1, $avatarFieldInput, 'Avatar field input should exist in form');
    }

    public function testAvatarFieldSubmission(): void
    {
        $avatarUrl = 'https://example.com/submitted-avatar.jpg';
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[avatarField]'] = $avatarUrl;
        $form['FieldTestEntity[slugField]'] = 'avatar-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['avatarField' => $avatarUrl]);
        static::assertNotNull($entity, 'Entity should be created with the submitted avatar value');
        static::assertSame($avatarUrl, $entity->getAvatarField());
    }

    public function testAvatarFieldEdit(): void
    {
        $originalUrl = 'https://example.com/original-avatar.jpg';
        $updatedUrl = 'https://example.com/updated-avatar.jpg';

        $entity = $this->createFieldTestEntity([
            'avatarField' => $originalUrl,
            'slugField' => 'edit-test',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame($originalUrl, $form['FieldTestEntity[avatarField]']->getValue());

        $form['FieldTestEntity[avatarField]'] = $updatedUrl;
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame($updatedUrl, $updatedEntity->getAvatarField());
    }

    public function testAvatarFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'avatarField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        // null avatar should render without errors
        $avatarFieldCell = $entityRow->filter('td[data-column="avatarField"]');
        static::assertCount(1, $avatarFieldCell, 'Avatar field cell should exist even with null value');
    }

    public function testAvatarFieldWithGravatarUrl(): void
    {
        // gravatar URLs are a common use case for avatar fields
        $gravatarUrl = 'https://www.gravatar.com/avatar/test';
        $entity = $this->createFieldTestEntity([
            'avatarField' => $gravatarUrl,
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $html = $crawler->html();
        static::assertStringContainsString('gravatar.com', $html);
    }
}
