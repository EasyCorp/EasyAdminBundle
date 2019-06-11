<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;

/**
 * The filter interface that all filters must to implement.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
interface FilterInterface
{
    /**
     * @param QueryBuilder  $queryBuilder The list QueryBuilder instance
     * @param FormInterface $form         The form filter instance
     * @param array         $metadata     The configured filter options
     *
     * @return void|false Returns false if the filter wasn't applied
     */
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metadata);
}
