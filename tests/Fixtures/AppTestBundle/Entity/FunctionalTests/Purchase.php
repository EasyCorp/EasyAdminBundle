<?php

/*
 * This file is part of the Doctrine-TestSet project created by
 * https://github.com/MacFJA
 *
 * For the full copyright and license information, please view the LICENSE
 * at https://github.com/MacFJA/Doctrine-TestSet
 */

namespace AppTestBundle\Entity\FunctionalTests;

use AppTestBundle\Model\Shipment;
use Doctrine\Common\Collections\ArrayCollection;
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
     * @var string
     * @ORM\Column(type="string", name="id", nullable=false)
     * @ORM\Id
     */
    protected $id = null;

    /**
     * The Unique id of the purchase.
     *
     * @var string
     * @ORM\Column(type="guid")
     */
    protected $guid = null;

    /**
     * The date of the delivery (it doesn't include the time).
     *
     * @var \DateTime
     * @ORM\Column(type="date")
     */
    protected $deliveryDate = null;

    /**
     * The purchase datetime in the customer timezone.
     *
     * @var \DateTime
     * @ORM\Column(type="datetimetz")
     */
    protected $createdAt = null;

    /**
     * The shipping information.
     *
     * @var Shipment
     * @ORM\Column(type="object")
     */
    protected $shipping = null;

    /**
     * The customer preferred time of the day for the delivery.
     *
     * @var \DateTime|null
     * @ORM\Column(type="time", nullable=true)
     */
    protected $deliveryHour = null;

    /**
     * The customer billing address.
     *
     * @var array
     * @ORM\Column(type="json_array")
     */
    protected $billingAddress = [];

    /**
     * The user who made the purchase.
     *
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="purchases")
     */
    protected $buyer;

    /**
     * Items that have been purchased.
     *
     * @var PurchaseItem[]
     * @ORM\OneToMany(targetEntity="PurchaseItem", mappedBy="purchase", cascade={"remove"})
     */
    protected $purchasedItems;

    /**
     * Constructor of the Purchase class.
     * (Initialize some fields).
     */
    public function __construct()
    {
        $this->id = $this->generateId();
        $this->purchasedItems = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->deliveryDate = new \DateTime('+2 days');
        $this->deliveryHour = new \DateTime('14:00');
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
     * @return array
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @param User $buyer
     */
    public function setBuyer($buyer)
    {
        $this->buyer = $buyer;
    }

    /**
     * @return User
     */
    public function getBuyer()
    {
        return $this->buyer;
    }

    /**
     * @param \DateTime $deliveryDate
     */
    public function setDeliveryDate($deliveryDate)
    {
        $this->deliveryDate = $deliveryDate;
    }

    /**
     * @return \DateTime
     */
    public function getDeliveryDate()
    {
        return $this->deliveryDate;
    }

    /**
     * @param PurchaseItem[] $purchasedItems
     */
    public function setPurchasedItems($purchasedItems)
    {
        $this->purchasedItems = $purchasedItems;
    }

    /**
     * @return PurchaseItem[]
     */
    public function getPurchasedItems()
    {
        return $this->purchasedItems;
    }

    /**
     * @param \DateTime $deliveryHour
     */
    public function setDeliveryHour($deliveryHour)
    {
        $this->deliveryHour = $deliveryHour;
    }

    /**
     * @return \DateTime|null
     */
    public function getDeliveryHour()
    {
        return $this->deliveryHour;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param Shipment $shipping
     */
    public function setShipping($shipping)
    {
        $this->shipping = $shipping;
    }

    /**
     * @return Shipment
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * @param int $storeId
     *
     * @return string
     */
    public function generateId($storeId = 1)
    {
        return \preg_replace('/[^0-9]/i', '', \sprintf('%d%d%03d%s', $storeId, \date('Y'), \date('z'), \microtime()));
    }

    public function __toString()
    {
        return 'Purchase #'.$this->getId();
    }

    /**
     * @return string
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * @param string $guid
     *
     * @return Purchase
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return Purchase
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getTotal()
    {
        $total = 0.0;

        foreach ($this->getPurchasedItems() as $item) {
            $total += $item->getTotalPrice();
        }

        return $total;
    }
}
