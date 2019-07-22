<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
trait FilterTypeTrait
{
    protected static $uniqueAliasId = 0;

    /**
     * Generates dynamic alias from a given name.
     */
    protected static function createAlias(string $name): string
    {
        return str_replace('.', '_', $name).'_'.++static::$uniqueAliasId;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metadata)
    {
        $alias = current($queryBuilder->getRootAliases());
        $property = $metadata['property'];
        $paramName = static::createAlias($property);
        $value = $form->getData();

        $queryBuilder->andWhere(sprintf('%s.%s = :%s', $alias, $property, $paramName))
            ->setParameter($paramName, $value);
    }
}
