<?php

namespace AppTestBundle\Entity\FunctionalTests;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class EntityWithNonScalarPrimaryKey
{
    /**
     * @var PrimaryKey
     * @ORM\Column(type="string", name="id", nullable=false)
     * @ORM\Id
     */
    protected $id;

    public function __construct()
    {
        $this->id = new PrimaryKey(1);
    }

    public function getId()
    {
        return $this->id;
    }
}
