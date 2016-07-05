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
class Image
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
     * The raw data of the full size image.
     *
     * @var resource
     * @ORM\Column(type="blob", name="image_data")
     */
    protected $data;

    /**
     * The raw data of the thumbnail of the image.
     *
     * @var resource
     * @ORM\Column(type="blob")
     */
    protected $thumbnail;

    /**
     * Set the content of the full size image.
     *
     * @param resource $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get the (raw) content of the image.
     *
     * @return resource
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the thumbnail of the image.
     *
     * @param resource $thumbnail
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * Get the (raw) content of the thumbnail.
     *
     * @return resource
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * Get the id of the image.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
