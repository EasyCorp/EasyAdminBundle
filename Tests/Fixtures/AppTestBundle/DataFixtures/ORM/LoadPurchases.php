<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\DataFixtures\ORM;

use AppTestBundle\Entity\FunctionalTests\Purchase;
use AppTestBundle\Entity\FunctionalTests\PurchaseItem;
use AppTestBundle\Entity\FunctionalTests\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadPurchases extends AbstractFixture implements OrderedFixtureInterface
{
    public function getOrder()
    {
        return 200;
    }

    public function load(ObjectManager $manager)
    {
        foreach (range(1, 30) as $i) {
            $purchase = new Purchase();
            $purchase->setGuid($this->generateGuid());
            $purchase->setDeliveryDate(new \DateTime("+$i days"));
            $purchase->setCreatedAt(new \DateTime("now +$i seconds"));
            $purchase->setShipping(new \StdClass());
            $purchase->setDeliveryHour($this->getHour($i));
            $purchase->setBillingAddress(json_encode(array(
                'line1' => '1234 Main Street',
                'line2' => 'Big City, XX 23456',
            )));
            $purchase->setBuyer($this->getReference('user-'.($i % 20 + 1)));

            $this->addReference('purchase-'.$i, $purchase);
            $manager->persist($purchase);
            $manager->flush();

            $numItemsPurchased = rand(1, 5);
            foreach (range(1, $numItemsPurchased) as $j) {
                $item = new PurchaseItem();
                $item->setQuantity(rand(1, 3));
                $item->setProduct($this->getRandomProduct());
                $item->setTaxRate(0.21);
                $item->setPurchase($this->getReference('purchase-'.$i));

                $manager->persist($item);
            }
        }

        $manager->flush();
    }

    private function generateGuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
          mt_rand(0, 0xffff), mt_rand(0, 0xffff),
          mt_rand(0, 0xffff),
          mt_rand(0, 0x0fff) | 0x4000,
          mt_rand(0, 0x3fff) | 0x8000,
          mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function getHour($i)
    {
        $date = new \DateTime();

        return $date->setTime($i % 24, 0);
    }

    private function getRandomProduct()
    {
        $productId = rand(1, 100);

        return $this->getReference('product-'.$productId);
    }
}
