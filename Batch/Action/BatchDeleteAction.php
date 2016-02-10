<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Batch\Action;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Batch delete action.
 *
 * @author unexge <unexge@yandex.com>
 */
class BatchDeleteAction implements BatchActionInterface
{
    /**
     * @var Registry
     */
    protected $doctrine;


    /**
     * BatchDeleteAction constructor.
     *
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @inheritDoc
     */
    public function process($data, $entity, $options)
    {
        /** @var EntityManagerInterface $manager */
        $manager = $this->doctrine->getManagerForClass($entity['class']);

        $qb = $manager
            ->createQueryBuilder()
            ->delete($entity['class'], 'e')
        ;

        if (is_array($data)) {
            $qb
                ->where($qb->expr()->in(
                    'e.' . $entity['primary_key_field_name'],
                    ':data'
                ))
                ->setParameter('data', $data)
            ;
        }

        if ( ! $qb->getQuery()->execute()) {
            return array(false, 'Something went wrong.');
        }

        $message = is_string($data)
            ? 'Success! All entities deleted.'
            : 'Success! Selected entities deleted.'
        ;

        return array(true, $message);
    }

    /**
     * @inheritDoc
     */
    public function supports($entity, $action)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'delete';
    }

}
