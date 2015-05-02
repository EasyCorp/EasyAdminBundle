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
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Model\Shipment;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Purchase
 *
 * @author MacFJA
 *
 * @ORM\Table(name="purchase")
 * @ORM\Entity
 */
class Purchase
{
    /**
     * The purchase increment id. This identifier will be use in all communication between the customer and the store.
     * @var integer
     * @ORM\Column(type="integer", name="id", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $id = null;

    /**
     * The Unique id of the purchase
     * @var string
     * @ORM\Column(type="guid")
     */
    public $guid = null;

    /**
     * The day of the delivery
     * @var \DateTime
     * @ORM\Column(type="date")
     */
    public $deliverySelected = null;

    /**
     * The purchase date in the customer timezone
     * @var \DateTime
     * @ORM\Column(type="datetimetz")
     */
    public $purchaseAt = null;

    /**
     * The shipping information
     * @var Shipment
     * @ORM\Column(type="object")
     */
    public $shipping = null;

    /**
     * The customer preferred time of the day for the delivery
     * @var \DateTime
     * @ORM\Column(type="time")
     */
    public $preferredDeliveryHour = null;

    /**
     * The customer billing address.
     * @var array
     * @ORM\Column(type="json_array")
     */
    public $billingAddress = array();

    /**
     * Items that have been purchased
     * @var OrderItem[]
     * @ORM\ManyToMany(targetEntity="PurchaseItem")
     * @ORM\JoinTable(name="purchase_purchase_item",
     *      joinColumns={@ORM\JoinColumn(name="purchase_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="item_id", referencedColumnName="id", unique=true)}
     *      )
     */
    public $purchasedItems;

    /**
     * Constructor of the Purchase class.
     * (Initialize some fields)
     */
    function __construct()
    {
        //Initialize purchasedItems as a Doctrine Collection
        $this->purchasedItems = new ArrayCollection();
        //Initialize purchaseAt to now (useful for new order, override by existing one)
        $this->purchaseAt = new \DateTime();
        $this->deliverySelected = new \DateTime('+2 days');
        $this->preferredDeliveryHour = new \DateTime('14:00');
        $this->incrementId = $this->generateIncrementId();
    }

    /**
     * Set the address where the customer want its billing
     * @param array $billingAddress
     */
    public function setBillingAddress($billingAddress)
    {
        $this->billingAddress = $billingAddress;
    }

    /**
     * Get the customer billing address
     * @return array
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * Set the day of delivery
     * @param \DateTime $deliverySelected
     */
    public function setDeliverySelected($deliverySelected)
    {
        $this->deliverySelected = $deliverySelected;
    }

    /**
     * Get the day when the customer want to be deliver
     * @return \DateTime
     */
    public function getDeliverySelected()
    {
        return $this->deliverySelected;
    }

    /**
     * Get the purchase id
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set all items ordered
     * @param OrderItem[] $purchasedItems
     */
    public function setPurchasedItems($purchasedItems)
    {
        $this->purchasedItems = $purchasedItems;
    }

    /**
     * Get all ordered items
     * @return OrderItem[]
     */
    public function getPurchasedItems()
    {
        return $this->purchasedItems;
    }

    /**
     * Set the delivery hour
     * @param \DateTime $preferredDeliveryHour
     */
    public function setPreferredDeliveryHour($preferredDeliveryHour)
    {
        $this->preferredDeliveryHour = $preferredDeliveryHour;
    }

    /**
     * Get the delivery hour
     * @return \DateTime
     */
    public function getPreferredDeliveryHour()
    {
        return $this->preferredDeliveryHour;
    }

    /**
     * Set the date when the order have been created
     * @param \DateTime $purchaseAt
     */
    public function setPurchaseAt($purchaseAt)
    {
        $this->purchaseAt = $purchaseAt;
    }

    /**
     * Get the date of the order
     * @return \DateTime
     */
    public function getPurchaseAt()
    {
        return $this->purchaseAt;
    }

    /**
     * Set the shipping information
     * @param Shipment $shipping
     */
    public function setShipping($shipping)
    {
        $this->shipping = $shipping;
    }

    /**
     * Get the shipping information
     * @return Shipment
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * Generate an increment id base on the store id and teh current date
     * @param int $storeId
     * @return string
     */
    public function generateIncrementId($storeId=1) {
        $uid = date('YmdHi');
        return sprintf('%d%O13d', $storeId, $uid);
    }

    /** {@inheritdoc} */
    function __toString()
    {
        return 'Purchase #'.$this->getIncrementId();
    }
}
