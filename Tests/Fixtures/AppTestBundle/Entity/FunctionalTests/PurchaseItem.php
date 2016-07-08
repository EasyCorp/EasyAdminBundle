<?php

/*
 * This file is part of the Doctrine-TestSet project created by
 * https://github.com/MacFJA
 *
 * For the full copyright and license information, please view the LICENSE
 * at https://github.com/MacFJA/Doctrine-TestSet
 */

namespace AppTestBundle\Entity\FunctionalTests;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @author MacFJA
 */
class PurchaseItem
{
    /**
     * The identifier of the image.
     *
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * The ordered quantity.
     *
     * @var int
     * @ORM\Column(type="smallint")
     */
    protected $quantity = 1;

    /**
     * The tax rate to apply on the product.
     *
     * @var string
     * @ORM\Column(type="decimal", name="tax_rate")
     */
    protected $taxRate = 0.21;

    /**
     * The ordered product.
     *
     * @var Product
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     **/
    protected $product;

    /**
     * @param Product $product
     */
    public function setProduct($product)
    {
        $this->product = $product;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param string $taxRate
     */
    public function setTaxRate($taxRate)
    {
        $this->taxRate = $taxRate;
    }

    /**
     * @return string
     */
    public function getTaxRate()
    {
        return $this->taxRate;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /** {@inheritdoc} */
    public function __toString()
    {
        return $this->getProduct()->getName().' [x'.$this->getQuantity().']: '.$this->getTotalPrice();
    }

    /**
     * Return the total price (tax included).
     *
     * @return float
     */
    public function getTotalPrice()
    {
        return $this->product->getPrice() * $this->quantity * (1 + $this->taxRate);
    }
}
