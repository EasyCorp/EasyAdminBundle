<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

/**
 * The 'group' form type is used to display a THIRD LEVEL design element
 * needed to create complex form layouts. This "fake" type just displays some HTML tags.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Mathieu Poisbeau <contact@freepius.net>
 */
class EaFormTabType extends EaFormPanelType
{
    protected const TYPE = 'group';
}
