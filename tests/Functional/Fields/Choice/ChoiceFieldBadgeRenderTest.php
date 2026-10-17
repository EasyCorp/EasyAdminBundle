<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Choice;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Translation\TranslatableChoiceMessage;
use EasyCorp\Bundle\EasyAdminBundle\Translation\TranslatableChoiceMessageCollection;
use function Symfony\Component\Translation\t;

/**
 * Rendering tests for crud/field/choice.html.twig when a ChoiceField is displayed
 * as badges, i.e. the integration between the formatted value built by
 * ChoiceConfigurator and the <twig:ea:Badge> component used in the template.
 */
class ChoiceFieldBadgeRenderTest extends AbstractFieldFunctionalTestCase
{
    /**
     * @param TranslatableChoiceMessage[] $choices
     */
    private function renderChoiceField(array $choices, bool $isRenderedAsBadge): string
    {
        $field = new FieldDto();
        $field->setFormattedValue(new TranslatableChoiceMessageCollection($choices, $isRenderedAsBadge));

        return trim(static::getContainer()->get('twig')->render('@EasyAdmin/crud/field/choice.html.twig', [
            'field' => $field,
        ]));
    }

    public function testSingleChoiceIsRenderedAsBadge(): void
    {
        $html = $this->renderChoiceField([
            new TranslatableChoiceMessage(t('Published'), 'success'),
        ], true);

        self::assertSame('<span class="badge badge-success">Published</span>', $html);
    }

    public function testMultipleChoicesAreRenderedAsSeparateBadges(): void
    {
        $html = $this->renderChoiceField([
            new TranslatableChoiceMessage(t('Draft'), 'secondary'),
            new TranslatableChoiceMessage(t('Archived'), 'warning'),
        ], true);

        self::assertSame(
            '<span class="badge badge-secondary">Draft</span><span class="badge badge-warning">Archived</span>',
            $html
        );
    }

    public function testHtmlInBadgeLabelIsRenderedAsHtmlAndNotEscaped(): void
    {
        $html = $this->renderChoiceField([
            new TranslatableChoiceMessage(t('<b>Active</b>'), 'success'),
        ], true);

        // the choice label must be rendered as HTML (matching the behavior before the
        // <twig:ea:Badge> refactor), not escaped to "&lt;b&gt;Active&lt;/b&gt;"
        self::assertStringContainsString('<b>Active</b>', $html);
        self::assertStringNotContainsString('&lt;b&gt;', $html);
    }
}
