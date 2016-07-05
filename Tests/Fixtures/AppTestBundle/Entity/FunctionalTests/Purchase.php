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
use AppTestBundle\Model\Shipment;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @author MacFJA
 */
class Purchase
{
    /**
     * The purchase increment id. This identifier will be use in all communication between the customer and the store.
     *
     * @var int
     * @ORM\Column(type="integer", name="id", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $id;

    /**
     * The Unique id of the purchase.
     *
     * @var string
     * @ORM\Column(type="guid")
     */
    public $guid;

    /**
     * The day of the delivery.
     *
     * @var \DateTime
     * @ORM\Column(type="date")
     */
    public $deliverySelected;

    /**
     * The purchase date in the customer timezone.
     *
     * @var \DateTime
     * @ORM\Column(type="datetimetz")
     */
    public $purchaseAt;

    /**
     * The shipping information.
     *
     * @var Shipment
     * @ORM\Column(type="object")
     */
    public $shipping;

    /**
     * The customer preferred time of the day for the delivery.
     *
     * @var \DateTime
     * @ORM\Column(type="time")
     */
    public $preferredDeliveryHour;

    /**
     * The customer billing address.
     *
     * @var array
     * @ORM\Column(type="json_array")
     */
    public $billingAddress = array();

    /**
     * Items that have been purchased.
     *
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="PurchaseItem")
     * @ORM\JoinTable(name="purchase_purchase_item",
     *                  joinColumns={@ORM\JoinColumn(name="purchase_id", referencedColumnName="id")},
     *                  inverseJoinColumns={@ORM\JoinColumn(name="item_id", referencedColumnName="id", unique=true)}
     *                  )
     */
    public $purchasedItems;

    /**
     * Constructor of the Purchase class.
     * (Initialize some fields).
     */
    public function __construct()
    {
        $this->purchasedItems = new ArrayCollection();
        $this->purchaseAt = new \DateTime();
        $this->deliverySelected = new \DateTime('+2 days');
        $this->preferredDeliveryHour = new \DateTime('14:00');
        $this->id = $this->generateIncrementId();
    }

    /**
     * Set the address where the customer want its billing.
     *
     * @param array $billingAddress
     */
    public function setBillingAddress($billingAddress)
    {
        $this->billingAddress = $billingAddress;
    }

    /**
     * Get the customer billing address.
     *
     * @return array
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * Set the day of delivery.
     *
     * @param \DateTime $deliverySelected
     */
    public function setDeliverySelected($deliverySelected)
    {
        $this->deliverySelected = $deliverySelected;
    }

    /**
     * Get the day when the customer want to be deliver.
     *
     * @return \DateTime
     */
    public function getDeliverySelected()
    {
        return $this->deliverySelected;
    }

    /**
     * Get the purchase id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set all items ordered.
     *
     * @param ArrayCollection $purchasedItems
     */
    public function setPurchasedItems($purchasedItems)
    {
        $this->purchasedItems = $purchasedItems;
    }

    /**
     * Get all ordered items.
     *
     * @return ArrayCollection
     */
    public function getPurchasedItems()
    {
        return $this->purchasedItems;
    }

    /**
     * Set the delivery hour.
     *
     * @param \DateTime $preferredDeliveryHour
     */
    public function setPreferredDeliveryHour($preferredDeliveryHour)
    {
        $this->preferredDeliveryHour = $preferredDeliveryHour;
    }

    /**
     * Get the delivery hour.
     *
     * @return \DateTime
     */
    public function getPreferredDeliveryHour()
    {
        return $this->preferredDeliveryHour;
    }

    /**
     * Set the date when the order have been created.
     *
     * @param \DateTime $purchaseAt
     */
    public function setPurchaseAt($purchaseAt)
    {
        $this->purchaseAt = $purchaseAt;
    }

    /**
     * Get the date of the order.
     *
     * @return \DateTime
     */
    public function getPurchaseAt()
    {
        return $this->purchaseAt;
    }

    /**
     * Set the shipping information.
     *
     * @param Shipment $shipping
     */
    public function setShipping($shipping)
    {
        $this->shipping = $shipping;
    }

    /**
     * Get the shipping information.
     *
     * @return Shipment
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * Generate an increment id base on the store id and teh current date.
     *
     * @param int $storeId
     *
     * @return int
     */
    public function generateIncrementId($storeId = 1)
    {
        $uid = date('YmdHi');

        return (int) sprintf('%d%013d', $storeId, $uid);
    }

    /** {@inheritdoc} */
    public function __toString()
    {
        return 'Purchase #'.$this->getId();
    }
}
