<?php

/*
 * This file is part of the Doctrine-TestSet project created by
 * https://github.com/MacFJA
 *
 * For the full copyright and license information, please view the LICENSE
 * at https://github.com/MacFJA/Doctrine-TestSet
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Model;

/**
 * Class Shipment.
 * Define a simple PHP class.
 *
 * @author MacFJA
 */
class Shipment
{
    protected $address;
    protected $cost;
    protected $transporter;

    /**
     * Set the shipping address.
     *
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Get the shipping address.
     *
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set the delivery cost of the order.
     *
     * @param mixed $cost
     */
    public function setCost($cost)
    {
        $this->cost = $cost;
    }

    /**
     * Get the delivery cost.
     *
     * @return mixed
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Set the transporter that handle the delivery.
     *
     * @param mixed $transporter
     */
    public function setTransporter($transporter)
    {
        $this->transporter = $transporter;
    }

    /**
     * Get the order's transporter.
     *
     * @return mixed
     */
    public function getTransporter()
    {
        return $this->transporter;
    }

    /** {@inheritdoc} */
    public function __toString()
    {
        return $this->getTransporter().': '.$this->getCost();
    }
}
