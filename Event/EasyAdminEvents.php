<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Event;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EasyAdminEvents
{
    // Events related to initialization
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const PRE_INITIALIZE = 'easy_admin.pre_initialize';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_INITIALIZE = 'easy_admin.post_initialize';

    // Events related to backend views
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const PRE_DELETE = 'easy_admin.pre_delete';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_DELETE = 'easy_admin.post_delete';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const PRE_EDIT = 'easy_admin.pre_edit';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_EDIT = 'easy_admin.post_edit';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const PRE_LIST = 'easy_admin.pre_list';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_LIST = 'easy_admin.post_list';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const PRE_NEW = 'easy_admin.pre_new';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_NEW = 'easy_admin.post_new';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const PRE_SEARCH = 'easy_admin.pre_search';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_SEARCH = 'easy_admin.post_search';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const PRE_SHOW = 'easy_admin.pre_show';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_SHOW = 'easy_admin.post_show';

    // Events related to Doctrine entities
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const PRE_PERSIST = 'easy_admin.pre_persist';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_PERSIST = 'easy_admin.post_persist';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const PRE_UPDATE = 'easy_admin.pre_update';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_UPDATE = 'easy_admin.post_update';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const PRE_REMOVE = 'easy_admin.pre_remove';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_REMOVE = 'easy_admin.post_remove';

    // Events related to Doctrine Query Builder usage
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_LIST_QUERY_BUILDER = 'easy_admin.post_list_query_builder';
    /** @Event("Symfony\Component\EventDispatcher\GenericEvent") */
    const POST_SEARCH_QUERY_BUILDER = 'easy_admin.post_search_query_builder';
}
