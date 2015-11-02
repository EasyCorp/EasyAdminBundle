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
    const PRE_INITIALIZE = 'easy_admin.pre_initialize';
    const POST_INITIALIZE = 'easy_admin.post_initialize';

    // Events related to backend views
    const PRE_DELETE = 'easy_admin.pre_delete';
    const POST_DELETE = 'easy_admin.post_delete';
    const PRE_EDIT = 'easy_admin.pre_edit';
    const POST_EDIT = 'easy_admin.post_edit';
    const PRE_LIST = 'easy_admin.pre_list';
    const POST_LIST = 'easy_admin.post_list';
    const PRE_NEW = 'easy_admin.pre_new';
    const POST_NEW = 'easy_admin.post_new';
    const PRE_SEARCH = 'easy_admin.pre_search';
    const POST_SEARCH = 'easy_admin.post_search';
    const PRE_SHOW = 'easy_admin.pre_show';
    const POST_SHOW = 'easy_admin.post_show';

    // Events related to Doctrine entities
    const PRE_PERSIST = 'easy_admin.pre_persist';
    const POST_PERSIST = 'easy_admin.post_persist';
    const PRE_UPDATE = 'easy_admin.pre_update';
    const POST_UPDATE = 'easy_admin.post_update';
    const PRE_REMOVE = 'easy_admin.pre_remove';
    const POST_REMOVE = 'easy_admin.post_remove';
}
