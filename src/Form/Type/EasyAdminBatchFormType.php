<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class EasyAdminBatchFormType extends AbstractType
{
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', HiddenType::class);
        $builder->add('ids', HiddenType::class);

        $builder->get('ids')->addModelTransformer(new CallbackTransformer(
            function ($value) { return $value; },
            function ($value) { return explode(',', $value); }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $entityConfig = $this->configManager->getEntityConfig($options['entity']);
        $disabledActions = $entityConfig['disabled_actions'];
        $batchActions = $entityConfig['list']['batch_actions'];

        $view->vars['batch_actions'] = array_filter($batchActions, function ($batchAction) use ($disabledActions) {
            return !\in_array($batchAction['name'], $disabledActions, true);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        // This input is not intended to be rendered
        // It's used to map the clicked batch button
        $view->children['name']->setRendered()->setMethodRendered();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('entity');
        $resolver->setDefaults([
            'allow_extra_fields' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'easyadmin_batch';
    }
}
