<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Collection;

/**
 * @deprecated since 4.28.2 and removed in 5.0.0 without a replacement.
 *
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
