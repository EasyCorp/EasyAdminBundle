<?php

namespace AppTestBundle\Entity\FunctionalTests;

use Doctrine\ORM\Mapping as ORM;

if (class_exists('Doctrine\DBAL\Types\DateTimeImmutableType') && class_exists('\DateTimeImmutable')) {
    /**
     * @ORM\Entity
     */
    class User extends BaseUserDbal26
    {
    }
} else {
    /**
     * @ORM\Entity
     */
    class User extends BaseUserDbal25
    {
    }
}
