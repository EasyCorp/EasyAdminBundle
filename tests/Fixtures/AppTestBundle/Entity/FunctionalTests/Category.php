<?php

/*
 * This file is part of the Doctrine-TestSet project created by
 * https://github.com/MacFJA
 *
 * For the full copyright and license information, please view the LICENSE
 * at https://github.com/MacFJA/Doctrine-TestSet
 */

namespace AppTestBundle\Entity\FunctionalTests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 *
 * @author MacFJA
 */
class Category
{
    /**
     * The identifier of the category.
     *
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * The category name.
     *
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotNull(groups={"Validate"})
     */
    protected $name;

    /**
     * Product in the category.
     *
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="Product", mappedBy="categories")
     **/
    protected $products;

    /**
     * The category parent.
     *
     * @var Category
     * @ORM\OneToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     **/
    protected $parent;

    /**
     * Constructor of the Category class.
     * (Initialize array field).
     */
    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Get the id of the category.
     * Return null if the category is new and not saved.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the name of the category.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of the category.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the parent category.
     *
     * @param Category $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get the parent category.
     *
     * @return Category
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Return all product associated to the category.
     *
     * @return ArrayCollection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Add a product in the category.
     *
     * @param $product Product The product to associate
     */
    public function addProduct(Product $product)
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
        }
    }

    /**
     * @param Product $product
     */
    public function removeProduct(Product $product)
    {
        $this->products->removeElement($product);
    }
}
