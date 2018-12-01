<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use EasyCorp\Bundle\EasyAdminBundle\Form\EventListener\EasyAdminAutocompleteSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Autocomplete form type.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class EasyAdminAutocompleteType extends AbstractType implements DataMapperInterface
{
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventSubscriber(new EasyAdminAutocompleteSubscriber())
            ->setDataMapper($this);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (null === $config = $this->configManager->getEntityConfigByClass($options['class'])) {
            throw new \InvalidArgumentException(\sprintf('The configuration of the "%s" entity is not available (this entity is used as the target of the "%s" autocomplete field).', $options['class'], $form->getName()));
        }

        $view->vars['autocomplete_entity_name'] = $config['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        // Add a custom block prefix to inner field to ease theming:
        \array_splice($view['autocomplete']->vars['block_prefixes'], -1, 0, 'easyadmin_autocomplete_inner');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'multiple' => false,
            // force display errors on this form field
            'error_bubbling' => false,
        ]);

        $resolver->setRequired(['class']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'easyadmin_autocomplete';
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($data, $forms)
    {
        $form = \current(\iterator_to_array($forms));
        $form->setData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData($forms, &$data)
    {
        $form = \current(\iterator_to_array($forms));
        $data = $form->getData();
    }
}
