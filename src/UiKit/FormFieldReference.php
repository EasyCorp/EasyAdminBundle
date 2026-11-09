<?php

namespace EasyCorp\Bundle\EasyAdminBundle\UiKit;

/**
 * The object edited by the form field reference of the UI Kit. It's a plain PHP class
 * (not a Doctrine entity) so the UI Kit works in any application.
 *
 * @internal
 */
final class FormFieldReference
{
    public ?string $text = 'Lorem ipsum dolor sit amet';
    public ?string $slug = 'lorem-ipsum-dolor-sit-amet';
    public ?string $textarea = "Lorem ipsum dolor sit amet, consectetur adipiscing elit.\nSed do eiusmod tempor incididunt ut labore et dolore magna aliqua.";
    public ?string $textEditor = '<p>Lorem ipsum <strong>dolor sit amet</strong>, consectetur adipiscing elit.</p>';
    public ?string $codeEditor = "<?php\n\nfunction greet(string \$name): string\n{\n    return sprintf('Hello %s!', \$name);\n}\n";
    public ?string $email = 'jane.doe@example.com';
    public ?string $telephone = '+1 555 0100';
    public ?string $url = 'https://example.com';
    public ?string $avatar = 'https://example.com/images/avatar.png';
    public ?string $color = '#4f46e5';
    public ?string $hidden = 'hidden value';

    public bool $boolean = true;
    public ?int $choice = 1;
    /** @var list<int> */
    public array $choiceMultiple = [0, 2];
    public ?int $choiceExpanded = 1;
    /** @var list<int> */
    public array $choiceExpandedMultiple = [0, 2];
    /** @var list<int> */
    public array $choiceAutocomplete = [0, 2];

    public ?int $integer = 42;
    public ?float $number = 3.1416;
    public ?float $percent = 0.25;
    public ?int $money = 199900;

    public ?\DateTimeImmutable $date;
    public ?\DateTimeImmutable $time;
    public ?\DateTimeImmutable $dateTime;
    public ?string $timezone = 'Europe/Madrid';

    public ?string $country = 'SV';
    public ?string $currency = 'JPY';
    public ?string $language = 'es';
    public ?string $locale = 'sw_KE';

    /** @var list<string> */
    public array $array = ['Lorem', 'Ipsum'];
    /** @var list<string> */
    public array $collection = ['Lorem', 'Ipsum'];

    public ?string $image = null;
    public ?string $file = null;

    public ?int $id = 1;

    public function __construct()
    {
        $this->date = new \DateTimeImmutable('2026-01-15');
        $this->time = new \DateTimeImmutable('2026-01-15 09:30');
        $this->dateTime = new \DateTimeImmutable('2026-01-15 09:30');
    }
}
