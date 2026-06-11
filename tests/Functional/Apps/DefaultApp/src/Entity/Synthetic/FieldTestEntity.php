<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class FieldTestEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // text fields
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $textField = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $textareaField = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $textEditorField = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $codeEditorField = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailField = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $telephoneField = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $urlField = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slugField = null;

    // numeric fields
    #[ORM\Column(nullable: true)]
    private ?int $integerField = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $numberField = null;

    #[ORM\Column(nullable: true)]
    private ?int $moneyField = null; // stored as cents

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $percentField = null;

    // dateTime fields
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateField = null;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $timeField = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateTimeField = null;

    // choice fields
    #[ORM\Column(nullable: true)]
    private ?bool $booleanField = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $choiceField = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $multipleChoiceField = [];

    // collection fields
    #[ORM\Column(type: 'json', nullable: true)]
    private array $arrayField = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private array $collectionField = [];

    // intl fields
    #[ORM\Column(length: 2, nullable: true)]
    private ?string $countryField = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $languageField = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $localeField = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $timezoneField = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $currencyField = null;

    // media fields
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageField = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarField = null;

    // special fields
    #[ORM\Column(length: 7, nullable: true)]
    private ?string $colorField = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hiddenField = null;

    // association fields
    #[ORM\ManyToOne(targetEntity: FieldRelatedEntity::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?FieldRelatedEntity $manyToOneAssociation = null;

    /**
     * @var Collection<int, FieldRelatedEntity>
     */
    #[ORM\ManyToMany(targetEntity: FieldRelatedEntity::class)]
    #[ORM\JoinTable(name: 'field_test_entity_many_to_many')]
    private Collection $manyToManyAssociation;

    public function __construct()
    {
        $this->manyToManyAssociation = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTextField(): ?string
    {
        return $this->textField;
    }

    public function setTextField(?string $textField): self
    {
        $this->textField = $textField;

        return $this;
    }

    public function getTextareaField(): ?string
    {
        return $this->textareaField;
    }

    public function setTextareaField(?string $textareaField): self
    {
        $this->textareaField = $textareaField;

        return $this;
    }

    public function getTextEditorField(): ?string
    {
        return $this->textEditorField;
    }

    public function setTextEditorField(?string $textEditorField): self
    {
        $this->textEditorField = $textEditorField;

        return $this;
    }

    public function getCodeEditorField(): ?string
    {
        return $this->codeEditorField;
    }

    public function setCodeEditorField(?string $codeEditorField): self
    {
        $this->codeEditorField = $codeEditorField;

        return $this;
    }

    public function getEmailField(): ?string
    {
        return $this->emailField;
    }

    public function setEmailField(?string $emailField): self
    {
        $this->emailField = $emailField;

        return $this;
    }

    public function getTelephoneField(): ?string
    {
        return $this->telephoneField;
    }

    public function setTelephoneField(?string $telephoneField): self
    {
        $this->telephoneField = $telephoneField;

        return $this;
    }

    public function getUrlField(): ?string
    {
        return $this->urlField;
    }

    public function setUrlField(?string $urlField): self
    {
        $this->urlField = $urlField;

        return $this;
    }

    public function getSlugField(): ?string
    {
        return $this->slugField;
    }

    public function setSlugField(?string $slugField): self
    {
        $this->slugField = $slugField;

        return $this;
    }

    public function getIntegerField(): ?int
    {
        return $this->integerField;
    }

    public function setIntegerField(?int $integerField): self
    {
        $this->integerField = $integerField;

        return $this;
    }

    public function getNumberField(): ?float
    {
        return $this->numberField;
    }

    public function setNumberField(?float $numberField): self
    {
        $this->numberField = $numberField;

        return $this;
    }

    public function getMoneyField(): ?int
    {
        return $this->moneyField;
    }

    public function setMoneyField(?int $moneyField): self
    {
        $this->moneyField = $moneyField;

        return $this;
    }

    public function getPercentField(): ?float
    {
        return $this->percentField;
    }

    public function setPercentField(?float $percentField): self
    {
        $this->percentField = $percentField;

        return $this;
    }

    public function getDateField(): ?\DateTimeInterface
    {
        return $this->dateField;
    }

    public function setDateField(?\DateTimeInterface $dateField): self
    {
        $this->dateField = $dateField;

        return $this;
    }

    public function getTimeField(): ?\DateTimeInterface
    {
        return $this->timeField;
    }

    public function setTimeField(?\DateTimeInterface $timeField): self
    {
        $this->timeField = $timeField;

        return $this;
    }

    public function getDateTimeField(): ?\DateTimeInterface
    {
        return $this->dateTimeField;
    }

    public function setDateTimeField(?\DateTimeInterface $dateTimeField): self
    {
        $this->dateTimeField = $dateTimeField;

        return $this;
    }

    public function getBooleanField(): ?bool
    {
        return $this->booleanField;
    }

    public function setBooleanField(?bool $booleanField): self
    {
        $this->booleanField = $booleanField;

        return $this;
    }

    public function getChoiceField(): ?string
    {
        return $this->choiceField;
    }

    public function setChoiceField(?string $choiceField): self
    {
        $this->choiceField = $choiceField;

        return $this;
    }

    public function getMultipleChoiceField(): array
    {
        return $this->multipleChoiceField;
    }

    public function setMultipleChoiceField(?array $multipleChoiceField): self
    {
        $this->multipleChoiceField = $multipleChoiceField ?? [];

        return $this;
    }

    public function getArrayField(): array
    {
        return $this->arrayField;
    }

    public function setArrayField(?array $arrayField): self
    {
        $this->arrayField = $arrayField ?? [];

        return $this;
    }

    public function getCollectionField(): array
    {
        return $this->collectionField;
    }

    public function setCollectionField(?array $collectionField): self
    {
        $this->collectionField = $collectionField ?? [];

        return $this;
    }

    public function getCountryField(): ?string
    {
        return $this->countryField;
    }

    public function setCountryField(?string $countryField): self
    {
        $this->countryField = $countryField;

        return $this;
    }

    public function getLanguageField(): ?string
    {
        return $this->languageField;
    }

    public function setLanguageField(?string $languageField): self
    {
        $this->languageField = $languageField;

        return $this;
    }

    public function getLocaleField(): ?string
    {
        return $this->localeField;
    }

    public function setLocaleField(?string $localeField): self
    {
        $this->localeField = $localeField;

        return $this;
    }

    public function getTimezoneField(): ?string
    {
        return $this->timezoneField;
    }

    public function setTimezoneField(?string $timezoneField): self
    {
        $this->timezoneField = $timezoneField;

        return $this;
    }

    public function getCurrencyField(): ?string
    {
        return $this->currencyField;
    }

    public function setCurrencyField(?string $currencyField): self
    {
        $this->currencyField = $currencyField;

        return $this;
    }

    public function getImageField(): ?string
    {
        return $this->imageField;
    }

    public function setImageField(?string $imageField): self
    {
        $this->imageField = $imageField;

        return $this;
    }

    public function getAvatarField(): ?string
    {
        return $this->avatarField;
    }

    public function setAvatarField(?string $avatarField): self
    {
        $this->avatarField = $avatarField;

        return $this;
    }

    public function getColorField(): ?string
    {
        return $this->colorField;
    }

    public function setColorField(?string $colorField): self
    {
        $this->colorField = $colorField;

        return $this;
    }

    public function getHiddenField(): ?string
    {
        return $this->hiddenField;
    }

    public function setHiddenField(?string $hiddenField): self
    {
        $this->hiddenField = $hiddenField;

        return $this;
    }

    public function getManyToOneAssociation(): ?FieldRelatedEntity
    {
        return $this->manyToOneAssociation;
    }

    public function setManyToOneAssociation(?FieldRelatedEntity $manyToOneAssociation): self
    {
        $this->manyToOneAssociation = $manyToOneAssociation;

        return $this;
    }

    /**
     * @return Collection<int, FieldRelatedEntity>
     */
    public function getManyToManyAssociation(): Collection
    {
        return $this->manyToManyAssociation;
    }

    public function addManyToManyAssociation(FieldRelatedEntity $entity): self
    {
        if (!$this->manyToManyAssociation->contains($entity)) {
            $this->manyToManyAssociation->add($entity);
        }

        return $this;
    }

    public function removeManyToManyAssociation(FieldRelatedEntity $entity): self
    {
        $this->manyToManyAssociation->removeElement($entity);

        return $this;
    }
}
