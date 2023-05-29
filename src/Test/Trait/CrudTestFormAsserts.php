<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Test\Trait;

use PHPUnit\Framework\Constraint\LogicalNot;
use Symfony\Component\DomCrawler\Test\Constraint\CrawlerSelectorTextSame;

trait CrudTestFormAsserts
{
    use CrudTestSelectors;

    protected function assertFormFieldExists(string $fieldName, ?string $message = null): void
    {
        $message ??= sprintf('The field %s is not existing in the form', $fieldName);

        self::assertSelectorExists($this->getFormFieldSelector($fieldName), $message);
    }

    protected function assertFormFieldNotExists(string $fieldName, ?string $message = null): void
    {
        $message ??= sprintf('The field %s is existing in the form', $fieldName);

        self::assertSelectorNotExists($this->getFormFieldSelector($fieldName), $message);
    }

    protected function assertFormFieldHasLabel(string $fieldName, string $label, ?string $message = null): void
    {
        $message ??= sprintf('The field %s has not the correct label %s', $fieldName, $label);

        self::assertSelectorExists(
            $this->getFormFieldLabelSelector($fieldName),
            sprintf('There is no label for the field %s', $fieldName)
        );
        self::assertSelectorTextSame($this->getFormFieldLabelSelector($fieldName), $label, $message);
    }

    protected function assertFormFieldNotHasLabel(string $fieldName, string $label, ?string $message = null): void
    {
        $message ??= sprintf('The field %s has the label %s', $fieldName, $label);

        self::assertSelectorExists(
            $this->getFormFieldLabelSelector($fieldName),
            sprintf('There is no label for the field %s', $fieldName)
        );

        self::assertThat(
            $this->client->getCrawler(),
            new LogicalNot(new CrawlerSelectorTextSame($this->getFormFieldLabelSelector($fieldName), $label)),
            $message
        );
    }
}
