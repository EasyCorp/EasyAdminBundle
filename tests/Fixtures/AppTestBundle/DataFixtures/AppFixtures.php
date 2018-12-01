<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\DataFixtures;

use AppTestBundle\Entity\FunctionalTests\Category;
use AppTestBundle\Entity\FunctionalTests\Product;
use AppTestBundle\Entity\FunctionalTests\Purchase;
use AppTestBundle\Entity\FunctionalTests\PurchaseItem;
use AppTestBundle\Entity\FunctionalTests\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    private $phrases = [
        'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
        'Pellentesque vitae velit ex.',
        'Mauris dapibus, risus quis suscipit vulputate, eros diam egestas libero, eu vulputate eros eros eu risus.',
        'In hac habitasse platea dictumst.',
        'Morbi tempus commodo mattis.',
        'Donec vel elit dui.',
        'Ut suscipit posuere justo at vulputate.',
        'Phasellus id porta orci.',
        'Ut eleifend mauris et risus ultrices egestas.',
        'Aliquam sodales, odio id eleifend tristique, urna nisl sollicitudin urna, id varius orci quam id turpis.',
        'Nulla porta lobortis ligula vel egestas.',
        'Curabitur aliquam euismod dolor non ornare.',
        'Nunc et feugiat lectus.',
        'Nam porta porta augue.',
        'Sed varius a risus eget aliquam.',
        'Nunc viverra elit ac laoreet suscipit.',
        'Pellentesque et sapien pulvinar, consectetur eros ac, vehicula odio.',
    ];

    public function load(ObjectManager $manager)
    {
        $users = $this->createUsers();
        foreach ($users as $user) {
            $manager->persist($user);
        }

        $categories = $this->createCategories();
        foreach ($categories as $category) {
            $manager->persist($category);
        }

        $products = $this->createProducts($categories);
        foreach ($products as $product) {
            $manager->persist($product);
        }

        $purchases = $this->createPurchases($users);
        foreach ($purchases as $purchase) {
            $manager->persist($purchase);
        }

        $purchaseItems = $this->createPurchaseItems($products, $purchases);
        foreach ($purchaseItems as $purchaseItem) {
            $manager->persist($purchaseItem);
        }

        $manager->flush();
    }

    private function createUsers(): array
    {
        $users = [];

        foreach (\range(1, 20) as $i) {
            $user = new User();
            $user->setUsername('user'.$i);
            $user->setEmail('user'.$i.'@example.com');

            $user->setCreatedAtDateTimeImmutable(
                new \DateTimeImmutable('October 18th 2005 16:27:36')
            );
            $user->setCreatedAtDateImmutable(
                new \DateTimeImmutable('October 18th 2005')
            );
            $user->setCreatedAtTimeImmutable(
                new \DateTimeImmutable('16:27:36')
            );

            $users[] = $user;
        }

        return $users;
    }

    private function createCategories(): array
    {
        $parentCategories = [];
        $subCategories = [];

        foreach (\range(1, 100) as $i) {
            $category = new Category();
            $category->setName('Parent Category #'.$i);

            $parentCategories[] = $category;
        }

        foreach (\range(1, 100) as $i) {
            $category = new Category();
            $category->setName('Category #'.$i);
            $category->setParent($parentCategories[$i - 1]);

            $subCategories[] = $category;
        }

        return \array_merge($parentCategories, $subCategories);
    }

    private function createProducts(array $categories): array
    {
        $products = [];

        foreach (\range(1, 100) as $i) {
            $product = new Product();
            $product->setEnabled($i <= 90 ? true : false);
            $product->setName($this->getRandomName());
            $product->setPrice($this->getRandomPrice());
            $product->setTags($this->getRandomTags());
            $product->setEan($this->getRandomEan());
            $product->setCategories($this->getRandomCategories($categories));
            $product->setDescription($this->getRandomDescription());
            $product->setHtmlFeatures($this->getRandomHtmlFeatures());

            $products[] = $product;
        }

        return $products;
    }

    private function createPurchases(array $users): array
    {
        $purchases = [];

        foreach (\range(1, 30) as $i) {
            $purchase = new Purchase();
            $purchase->setGuid($this->generateGuid());
            $purchase->setDeliveryDate(new \DateTime("+$i days"));
            $purchase->setCreatedAt(new \DateTime("now +$i seconds"));
            $purchase->setShipping(new \StdClass());
            $purchase->setDeliveryHour($this->getHour($i));
            $purchase->setBillingAddress(
                \json_encode(
                    [
                        'line1' => '1234 Main Street',
                        'line2' => 'Big City, XX 23456',
                    ]
                )
            );
            $purchase->setBuyer($users[$i % \count($users)]);

            $purchases[] = $purchase;
        }

        return $purchases;
    }

    private function createPurchaseItems(array $products, array $purchases): array
    {
        $purchaseItems = [];

        foreach (\range(1, 30) as $i) {
            $numItemsPurchased = \rand(1, 5);
            foreach (\range(1, $numItemsPurchased) as $j) {
                $item = new PurchaseItem();
                $item->setQuantity(\rand(1, 3));
                $item->setProduct($products[\array_rand($products)]);
                $item->setTaxRate(0.21);
                $item->setPurchase($purchases[$i - 1]);

                $purchaseItems[] = $item;
            }
        }

        return $purchaseItems;
    }

    public function getRandomTags()
    {
        $tags = [
            'books',
            'electronics',
            'GPS',
            'hardware',
            'laptops',
            'monitors',
            'movies',
            'music',
            'printers',
            'smartphones',
            'software',
            'toys',
            'TV & video',
            'videogames',
            'wearables',
        ];

        $numTags = \mt_rand(2, 4);
        \shuffle($tags);

        return \array_slice($tags, 0, $numTags - 1);
    }

    public function getRandomEan()
    {
        $chars = \str_split('0123456789');
        $count = \count($chars) - 1;
        $ean13 = '';
        do {
            $ean13 .= $chars[\mt_rand(0, $count)];
        } while (\strlen($ean13) < 13);

        $checksum = 0;
        foreach (\str_split(\strrev($ean13)) as $pos => $val) {
            $checksum += $val * (3 - 2 * ($pos % 2));
        }
        $checksum = ((10 - ($checksum % 10)) % 10);

        return $ean13.$checksum;
    }

    public function getRandomName()
    {
        $words = [
            'Lorem', 'Ipsum', 'Sit', 'Amet', 'Adipiscing', 'Elit',
            'Vitae', 'Velit', 'Mauris', 'Dapibus', 'Suscipit', 'Vulputate',
            'Eros', 'Diam', 'Egestas', 'Libero', 'Platea', 'Dictumst',
            'Tempus', 'Commodo', 'Mattis', 'Donec', 'Posuere', 'Eleifend',
        ];

        $numWords = 2;
        \shuffle($words);

        return 'Product '.\implode(' ', \array_slice($words, 0, $numWords));
    }

    public function getRandomPrice()
    {
        $cents = ['00', '29', '39', '49', '99'];

        return (float) \mt_rand(2, 79).'.'.$cents[\array_rand($cents)];
    }

    private function getRandomCategories(array $allCategories)
    {
        $categories = [];
        $numCategories = \rand(1, 4);

        for ($i = 0; $i < $numCategories; ++$i) {
            $categories[] = $allCategories[\array_rand($allCategories)];
        }

        return $categories;
    }

    public function getRandomDescription()
    {
        $numPhrases = \mt_rand(5, 10);
        \shuffle($this->phrases);

        return \implode(' ', \array_slice($this->phrases, 0, $numPhrases - 1));
    }

    public function getRandomHtmlFeatures()
    {
        $numFeatures = 2;
        \shuffle($this->phrases);

        return '<ul><li>'.\implode('</li><li>', \array_slice($this->phrases, 0, $numFeatures)).'</li></ul>';
    }

    private function generateGuid()
    {
        return \sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            \mt_rand(0, 0xffff), \mt_rand(0, 0xffff),
            \mt_rand(0, 0xffff),
            \mt_rand(0, 0x0fff) | 0x4000,
            \mt_rand(0, 0x3fff) | 0x8000,
            \mt_rand(0, 0xffff), \mt_rand(0, 0xffff), \mt_rand(0, 0xffff)
        );
    }

    private function getHour($i)
    {
        $date = new \DateTime();

        return $date->setTime($i % 24, 0);
    }
}
