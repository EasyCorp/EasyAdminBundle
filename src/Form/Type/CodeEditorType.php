<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class CodeEditorType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['height'] = $options['height'];
        $view->vars['tabSize'] = $options['tab_size'];
        $view->vars['indentWithTabs'] = $options['indent_with_tabs'];
        $view->vars['language'] = $options['language'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'height' => null,
            'tab_size' => 4,
            'indent_with_tabs' => false,
            // the code editor can't autodetect the language, so 'markdown' is used when
            // no language is selected explicitly (because it's the most similar to regular text)
            'language' => 'markdown',
        ]);
        $resolver->setAllowedTypes('height', ['null', 'int']);
        $resolver->setAllowedTypes('tab_size', 'int');
        $resolver->setAllowedTypes('indent_with_tabs', 'bool');
        $resolver->setAllowedTypes('language', 'string');
        $resolver->setAllowedValues('language', ['css', 'dockerfile', 'js', 'javascript', 'markdown', 'nginx', 'php', 'shell', 'sql', 'twig', 'xml', 'yaml-frontmatter', 'yaml']);

        // define some programming language shortcuts for better UX (e.g. 'js' === 'javascript')
        $resolver->setNormalizer('language', static function (Options $options, $language) {
            if ('js' === $language) {
                $language = 'javascript';
            }

            return $language;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return TextareaType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'easyadmin_code_editor';
    }
}
