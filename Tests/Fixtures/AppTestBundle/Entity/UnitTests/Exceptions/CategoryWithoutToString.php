<?php

namespace AppTestBundle\Entity\UnitTests\Exceptions;

use Doctrine\ORM\Mapping as ORM;

/**
 * This entity doesn't contain a __toString() method to force an exception
 * in some tests.
 *
 * @ORM\Entity
 */
class CategoryWithoutToString
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="CategoryWithoutToString")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    protected $parent;

    public function getId()
    {
        return $this->id;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }
}
