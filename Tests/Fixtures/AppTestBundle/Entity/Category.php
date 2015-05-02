<?php

/*
 * This file is part of the Doctrine-TestSet project created by
 * https://github.com/MacFJA
 *
 * For the full copyright and license information, please view the LICENSE
 * at https://github.com/MacFJA/Doctrine-TestSet
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Category
 *
 * @author MacFJA
 *
 * @ORM\Table(name="category")
 * @ORM\Entity
 */
class Category
{
    /**
     * The identifier of the category
     * @var integer
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id = null;

    /**
     * The category name
     * @var string
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Product in the category
     * @var Product[]
     * @ORM\ManyToMany(targetEntity="Product", mappedBy="categories")
     **/
    protected $products;

    /**
     * The category parent
     * @var Category
     * @ORM\OneToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     **/
    protected $parent;

    /**
     * Constructor of the Category class.
     * (Initialize array field)
     */
    function __construct()
    {
        //Initialize product as a Doctrine Collection
        $this->products = new ArrayCollection();
    }

    /** {@inheritdoc} */
    function __toString()
    {
        return $this->getName();
    }

    /**
     * Get the id of the category.
     * Return null if the category is new and not saved
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the name of the category
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of the category
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the parent category
     * @param Category $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get the parent category
     * @return Category
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Return all product associated to the category
     * @return Product[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Set all products in the category
     * @param Product[] $products
     */
    public function setProducts($products) {
        $this->products->clear();
        $this->products = new ArrayCollection($products);
    }

    /**
     * Add a product in the category
     * @param $product Product The product to associate
     */
    public function addProduct($product) {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
        }
    }

    /**
     * @param Product $product
     */
    public function removeProduct($product)
    {
        $this->products->removeElement($product);
    }
}
