<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Admin;

use AppTestBundle\Entity\FunctionalTests\Product;
use AppTestBundle\Form\Data\AddProductData;
use AppTestBundle\Form\Data\UpdateProductNameData;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use Symfony\Component\Form\FormInterface;

class ProductController extends EasyAdminController
{
    protected function createNewEntity(): AddProductData
    {
        return new AddProductData();
    }

    /**
     * @param AddProductData $object
     */
    protected function persistEntity($object): void
    {
        $product = new Product();
        $product->setName($object->name);
        $product->setDescription($object->description);
        $product->setPrice($object->price);
        $product->setEan($object->ean);
        $product->setTags(['x-product']);

        parent::persistEntity($product);
    }

    /**
     * @param Product $product
     */
    protected function createEditForm($product, array $entityProperties)
    {
        $object = new UpdateProductNameData();
        $object->name = $product->getName();

        return $this->createEntityForm($object, $entityProperties, 'edit');
    }

    /**
     * @param Product $product
     */
    protected function updateEntity($product, FormInterface $editForm = null): void
    {
        /** @var UpdateProductNameData $object */
        $object = $editForm->getData();

        $product->setName($object->name);

        parent::updateEntity($product);
    }
}
