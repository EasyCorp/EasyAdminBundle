<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Reflection\fixtures;

class FooBarClass
{
    // CamelCase - No getters and no setters
    public $publicPropertyWithoutGetterWithoutSetter;
    protected $protectedPropertyWithoutGetterWithoutSetter;
    private $privatePropertyWithoutGetterWithoutSetter;

    // CamelCase - Methods with the same name as properties
    public $publicPropertyWithSameNameMethod;
    public function publicPropertyWithSameNameMethod() {
        return $publicPropertyWithSameNameMethod;
    }

    protected $protectedPropertyWithSameNameMethod;
    public function protectedPropertyWithSameNameMethod() {
        return $protectedPropertyWithSameNameMethod;
    }

    private $privatePropertyWithSameNameMethod;
    public function privatePropertyWithSameNameMethod() {
        return $privatePropertyWithSameNameMethod;
    }

    // CamelCase - Getters but no setters
    public $publicPropertyWithGetterWithoutSetter;
    public function getPublicPropertyWithGetterWithoutSetter()
    {
        return $this->publicPropertyWithGetterWithoutSetter;
    }

    protected $protectedPropertyWithGetterWithoutSetter;
    public function getProtectedPropertyWithGetterWithoutSetter()
    {
        return $this->protectedPropertyWithGetterWithoutSetter;
    }

    private $privatePropertyWithGetterWithoutSetter;
    public function getPrivatePropertyWithGetterWithoutSetter()
    {
        return $this->privatePropertyWithGetterWithoutSetter;
    }

    // CamelCase - No getters but setters
    public $publicPropertyWithoutGetterWithSetter;
    public function setPublicPropertyWithoutGetterWithSetter($value)
    {
        $this->publicPropertyWithoutGetterWithSetter = $value;
    }

    protected $protectedPropertyWithoutGetterWithSetter;
    public function setProtectedPropertyWithoutGetterWithSetter($value)
    {
        $this->protectedPropertyWithoutGetterWithSetter = $value;
    }

    private $privatePropertyWithoutGetterWithSetter;
    public function setPrivatePropertyWithoutGetterWithSetter($value)
    {
        $this->privatePropertyWithoutGetterWithSetter = $value;
    }

    // CamelCase - Getters and setters
    public $publicPropertyWithGetterWithSetter;
    public function getPublicPropertyWithGetterWithSetter()
    {
        return $this->publicPropertyWithGetterWithSetter;
    }
    public function setPublicPropertyWithGetterWithSetter($value)
    {
        $this->publicPropertyWithGetterWithSetter = $value;
    }

    protected $protectedPropertyWithGetterWithSetter;
    public function getProtectedPropertyWithGetterWithSetter()
    {
        return $this->protectedPropertyWithGetterWithSetter;
    }
    public function setProtectedPropertyWithGetterWithSetter($value)
    {
        $this->protectedPropertyWithGetterWithSetter = $value;
    }

    private $privatePropertyWithGetterWithSetter;
    public function getPrivatePropertyWithGetterWithSetter()
    {
        return $this->privatePropertyWithGetterWithSetter;
    }
    public function setPrivatePropertyWithGetterWithSetter($value)
    {
        $this->privatePropertyWithGetterWithSetter = $value;
    }

    // CamelCase - Hassers but not getters or setters
    public $publicPropertyWithHasserWithoutGetterWithoutSetter;
    public function hasPublicPropertyWithHasserWithoutGetterWithoutSetter()
    {
        return $this->publicPropertyWithHasserWithoutGetterWithSetter;
    }

    protected $protectedPropertyWithHasserWithoutGetterWithoutSetter;
    public function hasProtectedPropertyWithHasserWithoutGetterWithoutSetter()
    {
        return $this->protectedPropertyWithHasserWithoutGetterWithSetter;
    }

    private $privatePropertyWithHasserWithoutGetterWithoutSetter;
    public function hasPrivatePropertyWithHasserWithoutGetterWithoutSetter()
    {
        return $this->privatePropertyWithHasserWithoutGetterWithSetter;
    }

    // CamelCase - Issers but not getters or setters or hassers
    public $publicPropertyWithIsserWithoutHasserWithoutGetterWithSetter;
    public function isPublicPropertyWithIsserWithoutHasserWithoutGetterWithSetter()
    {
        return $this->publicPropertyWithIsserWithoutHasserWithoutGetterWithSetter;
    }

    protected $protectedPropertyWithIsserWithoutHasserWithoutGetterWithSetter;
    public function isProtectedPropertyWithIsserWithoutHasserWithoutGetterWithSetter()
    {
        return $this->protectedPropertyWithIsserWithoutHasserWithoutGetterWithSetter;
    }

    private $privatePropertyWithIsserWithoutHasserWithoutGetterWithSetter;
    public function isPrivatePropertyWithIsserWithoutHasserWithoutGetterWithSetter()
    {
        return $this->privatePropertyWithIsserWithoutHasserWithoutGetterWithSetter;
    }

    // snake_case - No getters and no setters
    public $snake_case_public_property_without_getter_without_setter;
    protected $snake_case_protected_property_without_getter_without_setter;
    private $snake_case_private_property_without_getter_without_setter;

    // snake_case - Methods with the same name as properties
    public $snake_case_public_property_with_same_name_method;
    public function snake_case_public_property_with_same_name_method() {
        return $snake_case_public_property_with_same_name_method;
    }

    protected $snake_case_protected_property_with_same_name_method;
    public function snake_case_protected_property_with_same_name_method() {
        return $snake_case_protected_property_with_same_name_method;
    }

    private $snake_case_private_property_with_same_name_method;
    public function snake_case_private_property_with_same_name_method() {
        return $snake_case_private_property_with_same_name_method;
    }

    // snake_case - Getters but no setters
    public $snake_case_public_property_with_getter_without_setter;
    public function getSnakeCasePublicPropertyWithGetterWithoutSetter()
    {
        return $this->snake_case_public_property_with_getter_without_setter;
    }

    protected $snake_case_protected_property_with_getter_without_setter;
    public function getSnakeCaseProtectedPropertyWithGetterWithoutSetter()
    {
        return $this->snake_case_protected_property_with_getter_without_setter;
    }

    private $snake_case_private_property_with_getter_without_setter;
    public function getSnakeCasePrivatePropertyWithGetterWithoutSetter()
    {
        return $this->snake_case_private_property_with_getter_without_setter;
    }

    // snake_case - No getters but setters
    public $snake_case_public_property_without_getter_with_setter;
    public function setSnakeCasePublicPropertyWithoutGetterWithSetter($value)
    {
        $this->snake_case_public_property_without_getter_with_setter = $value;
    }

    protected $snake_case_protected_property_without_getter_with_setter;
    public function setSnakeCaseProtectedPropertyWithoutGetterWithSetter($value)
    {
        $this->snake_case_protected_property_without_getter_with_setter = $value;
    }

    private $snake_case_private_property_without_getter_with_setter;
    public function setSnakeCasePrivatePropertyWithoutGetterWithSetter($value)
    {
        $this->snake_case_private_property_without_getter_with_setter = $value;
    }

    // snake_case - Getters and setters
    public $snake_case_public_property_with_getter_with_setter;
    public function getSnakeCasePublicPropertyWithGetterWithSetter()
    {
        return $this->snake_case_public_property_with_getter_with_setter;
    }
    public function setSnakeCasePublicPropertyWithGetterWithSetter($value)
    {
        $this->snake_case_public_property_with_getter_with_setter = $value;
    }

    protected $snake_case_protected_property_with_getter_with_setter;
    public function getSnakeCaseProtectedPropertyWithGetterWithSetter()
    {
        return $this->snake_case_protected_property_with_getter_with_setter;
    }
    public function setSnakeCaseProtectedPropertyWithGetterWithSetter($value)
    {
        $this->snake_case_protected_property_with_getter_with_setter = $value;
    }

    private $snake_case_private_property_with_getter_with_setter;
    public function getSnakeCasePrivatePropertyWithGetterWithSetter()
    {
        return $this->snake_case_private_property_with_getter_with_setter;
    }
    public function setSnakeCasePrivatePropertyWithGetterWithSetter($value)
    {
        $this->snake_case_private_property_with_getter_with_setter = $value;
    }

    // snake_case - Hassers but not getters or setters
    public $snake_case_public_property_with_hasser_without_getter_with_setter;
    public function hasSnakeCasePublicPropertyWithHasserWithoutGetterWithSetter()
    {
        return $this->snake_case_public_property_with_hasser_without_getter_with_setter;
    }

    protected $snake_case_protected_property_with_hasser_without_getter_with_setter;
    public function hasSnakeCaseProtectedPropertyWithHasserWithoutGetterWithSetter()
    {
        return $this->snake_case_protected_property_with_hasser_without_getter_with_setter;
    }

    private $snake_case_private_property_with_hasser_without_getter_with_setter;
    public function hasSnakeCasePrivatePropertyWithHasserWithoutGetterWithSetter()
    {
        return $this->snake_case_private_property_with_hasser_without_getter_with_setter;
    }

    // snake_case - Issers but not getters or setters or hassers
    public $snake_case_public_property_with_isser_without_hasser_without_getter_with_setter;
    public function isSnakeCasePublicPropertyWithIsserWithoutHasserWithoutGetterWithSetter()
    {
        return $this->snake_case_public_property_with_isser_without_hasser_without_getter_with_setter;
    }

    protected $snake_case_protected_property_with_isser_without_hasser_without_getter_with_setter;
    public function isSnakeCaseProtectedPropertyWithIsserWithoutHasserWithoutGetterWithSetter()
    {
        return $this->snake_case_protected_property_with_isser_without_hasser_without_getter_with_setter;
    }

    private $snake_case_private_property_with_isser_without_hasser_without_getter_with_setter;
    public function isSnakeCasePrivatePropertyWithIsserWithoutHasserWithoutGetterWithSetter()
    {
        return $this->snake_case_private_property_with_isser_without_hasser_without_getter_with_setter;
    }
}
