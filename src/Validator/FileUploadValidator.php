<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Validator;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\FileValidator;

/**
 * @Annotation
 */
class FileUploadValidator extends FileValidator
{
    public function validate($value, Constraint $constraint)
    {
        if(!$value || !\is_object($value) || !$value instanceof File) {
            return;
        }

        parent::validate($value, $constraint);
    }
}
