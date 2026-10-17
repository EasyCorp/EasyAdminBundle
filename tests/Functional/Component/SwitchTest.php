<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Size;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

class SwitchTest extends AbstractFieldFunctionalTestCase
{
    public function testDefaultSizeEmitsNoSizeClass(): void
    {
        $html = $this->renderSwitch('<twig:ea:Switch />');

        self::assertStringContainsString('class="ea-switch"', $html);
        self::assertStringNotContainsString('ea-switch-sm', $html);
        self::assertStringNotContainsString('ea-switch-md', $html);
    }

    public function testSmallSizeAsString(): void
    {
        self::assertStringContainsString(
            'class="ea-switch ea-switch-sm"',
            $this->renderSwitch('<twig:ea:Switch size="sm" />')
        );
    }

    public function testSmallSizeAsEnum(): void
    {
        self::assertStringContainsString(
            'class="ea-switch ea-switch-sm"',
            $this->renderSwitch('<twig:ea:Switch size="{{ size }}" />', ['size' => Size::Small])
        );
    }

    public function testMediumSizeAsEnumEmitsNoSizeClass(): void
    {
        self::assertStringContainsString(
            'class="ea-switch"',
            $this->renderSwitch('<twig:ea:Switch size="{{ size }}" />', ['size' => Size::Medium])
        );
    }

    public function testExtraAttributesAreRenderedOnTheInput(): void
    {
        $html = $this->renderSwitch('<twig:ea:Switch data-foo="bar" />');

        self::assertMatchesRegularExpression('/<input[^>]+data-foo="bar"/', $html);
    }

    public function testCustomClassIsMergedIntoTheInputClass(): void
    {
        $html = $this->renderSwitch('<twig:ea:Switch class="custom" />');

        self::assertMatchesRegularExpression('/<input[^>]+class="ea-switch-input custom"/', $html);
        self::assertStringContainsString('<span class="ea-switch">', $html);
    }

    public function testFormAttrOptionIsRenderedOnTheSwitchInput(): void
    {
        $html = $this->renderCheckboxThroughFormTheme($this->createSwitchForm());

        self::assertMatchesRegularExpression('/<input[^>]+role="switch"/', $html);
        self::assertMatchesRegularExpression('/<input[^>]+data-device-target="monoblock"/', $html);
    }

    public function testInvalidSwitchInputGetsTheInvalidClass(): void
    {
        $form = $this->createSwitchForm();
        $form->submit(['monoblock' => '1']);
        $form->get('monoblock')->addError(new FormError('Some error'));

        $html = $this->renderCheckboxThroughFormTheme($form);

        self::assertMatchesRegularExpression('/<input[^>]+class="ea-switch-input is-invalid"/', $html);
    }

    private function createSwitchForm(): FormInterface
    {
        return static::getContainer()->get('form.factory')
            ->createNamedBuilder('device', options: ['csrf_protection' => false])
            ->add('monoblock', CheckboxType::class, [
                'label_attr' => ['class' => 'checkbox-switch'],
                'attr' => ['data-device-target' => 'monoblock'],
            ])
            ->getForm();
    }

    private function renderCheckboxThroughFormTheme(FormInterface $form): string
    {
        $template = <<<'TWIG'
            {% form_theme form '@EasyAdmin/symfony-form-themes/bootstrap_5_layout.html.twig' %}
            {{ form_widget(form.monoblock) }}
            TWIG;

        return $this->renderSwitch($template, ['form' => $form->createView()]);
    }

    private function renderSwitch(string $template, array $context = []): string
    {
        return static::getContainer()->get('twig')->createTemplate($template)->render($context);
    }
}
