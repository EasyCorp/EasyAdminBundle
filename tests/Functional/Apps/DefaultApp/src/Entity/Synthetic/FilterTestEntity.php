<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class FilterTestEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // text filters
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $textFilter = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $textareaFilter = null;

    // numeric filters
    #[ORM\Column(nullable: true)]
    private ?int $numericFilter = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $decimalFilter = null;

    // dateTime filters
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateFilter = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateTimeFilter = null;

    // boolean filter
    #[ORM\Column(nullable: true)]
    private ?bool $booleanFilter = null;

    // choice filters
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $choiceFilter = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $arrayFilter = [];

    // comparison filters (for testing different comparison operators)
    #[ORM\Column(nullable: true)]
    private ?int $comparisonFilter = null;

    // intl filters
    #[ORM\Column(length: 2, nullable: true)]
    private ?string $countryFilter = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $languageFilter = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $localeFilter = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $timezoneFilter = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $currencyFilter = null;

    // null comparison
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nullFilter = null;

    // entity filter (relation)
    #[ORM\ManyToOne(targetEntity: FilterRelatedEntity::class)]
    private ?FilterRelatedEntity $relatedEntity = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTextFilter(): ?string
    {
        return $this->textFilter;
    }

    public function setTextFilter(?string $textFilter): self
    {
        $this->textFilter = $textFilter;

        return $this;
    }

    public function getTextareaFilter(): ?string
    {
        return $this->textareaFilter;
    }

    public function setTextareaFilter(?string $textareaFilter): self
    {
        $this->textareaFilter = $textareaFilter;

        return $this;
    }

    public function getNumericFilter(): ?int
    {
        return $this->numericFilter;
    }

    public function setNumericFilter(?int $numericFilter): self
    {
        $this->numericFilter = $numericFilter;

        return $this;
    }

    public function getDecimalFilter(): ?float
    {
        return $this->decimalFilter;
    }

    public function setDecimalFilter(?float $decimalFilter): self
    {
        $this->decimalFilter = $decimalFilter;

        return $this;
    }

    public function getDateFilter(): ?\DateTimeInterface
    {
        return $this->dateFilter;
    }

    public function setDateFilter(?\DateTimeInterface $dateFilter): self
    {
        $this->dateFilter = $dateFilter;

        return $this;
    }

    public function getDateTimeFilter(): ?\DateTimeInterface
    {
        return $this->dateTimeFilter;
    }

    public function setDateTimeFilter(?\DateTimeInterface $dateTimeFilter): self
    {
        $this->dateTimeFilter = $dateTimeFilter;

        return $this;
    }

    public function getBooleanFilter(): ?bool
    {
        return $this->booleanFilter;
    }

    public function setBooleanFilter(?bool $booleanFilter): self
    {
        $this->booleanFilter = $booleanFilter;

        return $this;
    }

    public function getChoiceFilter(): ?string
    {
        return $this->choiceFilter;
    }

    public function setChoiceFilter(?string $choiceFilter): self
    {
        $this->choiceFilter = $choiceFilter;

        return $this;
    }

    public function getArrayFilter(): array
    {
        return $this->arrayFilter;
    }

    public function setArrayFilter(?array $arrayFilter): self
    {
        $this->arrayFilter = $arrayFilter ?? [];

        return $this;
    }

    public function getComparisonFilter(): ?int
    {
        return $this->comparisonFilter;
    }

    public function setComparisonFilter(?int $comparisonFilter): self
    {
        $this->comparisonFilter = $comparisonFilter;

        return $this;
    }

    public function getCountryFilter(): ?string
    {
        return $this->countryFilter;
    }

    public function setCountryFilter(?string $countryFilter): self
    {
        $this->countryFilter = $countryFilter;

        return $this;
    }

    public function getLanguageFilter(): ?string
    {
        return $this->languageFilter;
    }

    public function setLanguageFilter(?string $languageFilter): self
    {
        $this->languageFilter = $languageFilter;

        return $this;
    }

    public function getLocaleFilter(): ?string
    {
        return $this->localeFilter;
    }

    public function setLocaleFilter(?string $localeFilter): self
    {
        $this->localeFilter = $localeFilter;

        return $this;
    }

    public function getTimezoneFilter(): ?string
    {
        return $this->timezoneFilter;
    }

    public function setTimezoneFilter(?string $timezoneFilter): self
    {
        $this->timezoneFilter = $timezoneFilter;

        return $this;
    }

    public function getCurrencyFilter(): ?string
    {
        return $this->currencyFilter;
    }

    public function setCurrencyFilter(?string $currencyFilter): self
    {
        $this->currencyFilter = $currencyFilter;

        return $this;
    }

    public function getNullFilter(): ?string
    {
        return $this->nullFilter;
    }

    public function setNullFilter(?string $nullFilter): self
    {
        $this->nullFilter = $nullFilter;

        return $this;
    }

    public function getRelatedEntity(): ?FilterRelatedEntity
    {
        return $this->relatedEntity;
    }

    public function setRelatedEntity(?FilterRelatedEntity $relatedEntity): self
    {
        $this->relatedEntity = $relatedEntity;

        return $this;
    }
}
