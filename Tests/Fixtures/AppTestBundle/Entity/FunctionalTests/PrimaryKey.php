<?php

namespace AppTestBundle\Entity\FunctionalTests;

class PrimaryKey
{
    /**
     * @var string
     */
    private $id;

    public function __construct($id = 1)
    {
        $this->id = $id;
    }

    public function __toString()
    {
        return (string) $this->id;
    }
}
