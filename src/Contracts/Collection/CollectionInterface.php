<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Collection;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @template TKey
 * @template TValue
 *
 * @extends \ArrayAccess<TKey, TValue>
 * @extends \IteratorAggregate<TKey, TValue>
 */
interface CollectionInterface extends \ArrayAccess, \Countable, \IteratorAggregate
{
}
