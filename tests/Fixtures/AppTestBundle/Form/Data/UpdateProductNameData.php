<?php

namespace AppTestBundle\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * One case where it is useful to use something like a DTO
 * is when you have a significant mismatch between the model
 * in your presentation layer and the underlying domain model.
 *
 * In this case it makes sense to make presentation specific
 * that maps from the domain model and presents an interface
 * that's convenient for the presentation.
 */
class UpdateProductNameData
{
    /**
     * @Assert\NotBlank()
     * @Assert\Length(min="3")
     */
    public $name;
}
