<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extension that injects EasyAdmin related information in the view used to
 * render the form.
 *
 * @author Maxime Steinhausser
 */
class EasyAdminExtension extends AbstractTypeExtension
{
    /** @var Request|null */
    private $request;

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (null === $this->request) {
            return;
        }

        if ($this->request->attributes->has('easyadmin')) {
            $easyadmin = $this->request->attributes->get('easyadmin');
            $entity = $easyadmin['entity'];
            $action = $easyadmin['view'];
            $fields = $entity[$action]['fields'];
            $view->vars['easyadmin'] = array(
                'entity' => $entity,
                'view' => $action,
                'item' => $easyadmin['item'],
                'field' => isset($fields[$view->vars['name']]) ? $fields[$view->vars['name']] : null,
            );
        }
    }

    /**
     * BC for SF < 2.4.
     * To be replaced by the usage of the request stack when 2.3 support is dropped.
     *
     * @param Request|null $request
     *
     * @return $this
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
