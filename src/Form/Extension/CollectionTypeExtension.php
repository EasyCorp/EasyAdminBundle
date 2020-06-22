<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Extension that allows using collection_entry_* blocks in form themes.
 * This is the same code added in https://github.com/symfony/symfony/pull/36088
 * which is only available when using Symfony 5.1 or higher.
 *
 * @author Jules Pietri <heah@heahprod.com>
 */
class CollectionTypeExtension extends AbstractTypeExtension
{
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $prefixOffset = -2;
        // check if the entry type also defines a block prefix
        /** @var FormInterface $entry */
        foreach ($form as $entry) {
            if ($entry->getConfig()->getOption('block_prefix')) {
                --$prefixOffset;
            }

            break;
        }

        foreach ($view as $entryView) {
            // needed to avoid 'Unable to render the form because the block names array contains duplicates'
            if (\in_array('collection_entry', $entryView->vars['block_prefixes'], true)) {
                continue;
            }

            array_splice($entryView->vars['block_prefixes'], $prefixOffset, 0, 'collection_entry');
        }

        /** @var FormInterface $prototype */
        if ($prototype = $form->getConfig()->getAttribute('prototype')) {
            if ($prefixOffset > -3 && $prototype->getConfig()->getOption('block_prefix')) {
                --$prefixOffset;
            }

            // needed to avoid 'Unable to render the form because the block names array contains duplicates'
            if (!\in_array('collection_entry', $view->vars['prototype']->vars['block_prefixes'], true)) {
                array_splice($view->vars['prototype']->vars['block_prefixes'], $prefixOffset, 0, 'collection_entry');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [CollectionType::class];
    }
}
