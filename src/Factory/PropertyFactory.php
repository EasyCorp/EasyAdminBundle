<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\DBAL\Types\Type;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\PropertyDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\IdProperty;
use EasyCorp\Bundle\EasyAdminBundle\Property\Property;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class PropertyFactory
{
    private const DOCTRINE_TYPE_TO_PROPERTY_TYPE_MAP = [
        Type::TARRAY => 'array',
        Type::BIGINT => 'bigint',
        Type::BINARY => 'text',
        Type::BLOB => 'text',
        Type::BOOLEAN => 'boolean',
        Type::DATE => 'date',
        Type::DATE_IMMUTABLE => 'date',
        Type::DATEINTERVAL => 'text',
        Type::DATETIME => 'datetime',
        Type::DATETIME_IMMUTABLE => 'datetime',
        Type::DATETIMETZ => 'datetimetz',
        Type::DATETIMETZ_IMMUTABLE => 'datetimetz',
        Type::DECIMAL => 'decimal',
        Type::FLOAT => 'float',
        Type::GUID => 'string',
        Type::INTEGER => 'integer',
        Type::JSON => 'text',
        Type::OBJECT => 'text',
        Type::SIMPLE_ARRAY => 'array',
        Type::SMALLINT => 'integer',
        Type::STRING => 'string',
        Type::TEXT => 'text',
        Type::TIME => 'time',
        Type::TIME_IMMUTABLE => 'time',
    ];

    private $applicationContextProvider;
    private $authorizationChecker;
    private $propertyConfigurators;

    public function __construct(ApplicationContextProvider $applicationContextProvider, AuthorizationCheckerInterface $authorizationChecker, iterable $propertyConfigurators)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->propertyConfigurators = $propertyConfigurators;
    }

    /**
     * @param PropertyConfigInterface[] $propertiesConfig
     */
    public function create(EntityDto $entityDto, iterable $propertiesConfig): EntityDto
    {
        $action = $this->applicationContextProvider->getContext()->getCrud()->getAction();
        $configuredProperties = \is_array($propertiesConfig) ? $propertiesConfig : iterator_to_array($propertiesConfig);
        $configuredProperties = $this->preProcessPropertiesConfig($entityDto, $configuredProperties);

        $builtProperties = [];
        foreach ($configuredProperties as $propertyConfig) {
            if (false === $this->authorizationChecker->isGranted(Permission::EA_VIEW_PROPERTY, $propertyConfig)) {
                continue;
            }

            foreach ($this->propertyConfigurators as $configurator) {
                if (!$configurator->supports($propertyConfig, $entityDto)) {
                    continue;
                }

                $configurator->configure($action, $propertyConfig, $entityDto);
            }

            $builtProperties[] = $propertyConfig->getAsDto();
        }

        return $entityDto->updateProperties(PropertyDtoCollection::new($builtProperties));
    }

    private function preProcessPropertiesConfig(EntityDto $entityDto, array $propertiesConfig): array
    {
        // fox DX reasons, property config can be just a string with the property name
        foreach ($propertiesConfig as $i => $propertyConfig) {
            if (\is_string($propertyConfig)) {
                $propertiesConfig[$i] = Property::new($propertyConfig);
            }
        }

        /*
         * @var PropertyConfigInterface $propertyConfig
         */
        foreach ($propertiesConfig as $i => $propertyConfig) {
            // if it's not a generic Property, don't autoconfigure it
            if (!$propertyConfig instanceof Property) {
                continue;
            }

            // this is a virtual property, so we can't autoconfigure it
            if (!$entityDto->hasProperty($propertyConfig->getName())) {
                continue;
            }

            $doctrineMetadata = $entityDto->getPropertyMetadata($propertyConfig->getName());
            if (isset($doctrineMetadata['id']) && true === $doctrineMetadata['id']) {
                $propertiesConfig[$i] = $propertyConfig->transformInto(IdProperty::class);

                continue;
            }

            $guessedType = self::DOCTRINE_TYPE_TO_PROPERTY_TYPE_MAP[$doctrineMetadata['type']] ?? null;
            if (null !== $guessedType) {
                $guessedPropertyClass = 'EasyCorp\\Bundle\\EasyAdminBundle\\Property\\'.ucfirst($guessedType).'Property';
                $propertiesConfig[$i] = $propertyConfig->transformInto($guessedPropertyClass);
            }
        }

        return $propertiesConfig;
    }
}
