<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Entity\Product;

class LoadProducts extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        foreach (range(1, 100) as $i) {
            $product = new Product();
            $product->setEnabled((rand(1, 1000) % 10) < 7);
            $product->setName($this->getRandomName());
            $product->setPrice($this->getRandomPrice());
            $product->setTags($this->getRandomTags());
            $product->setEan($this->getRandomEan());
            $product->setDescription($this->getRandomDescription());

            $this->addReference('product-'.$i, $product);
            $manager->persist($product);
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 100;
    }

    public function getRandomTags()
    {
        $tags = array(
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
        );

        $numTags = rand(2, 4);
        shuffle($tags);

        return array_slice($tags, 0, $numTags - 1);
    }

    public function getRandomEan()
    {
        $chars = str_split('0123456789');
        $count = count($chars) - 1;
        $ean13 = '';
        do {
            $ean13 .= $chars[rand(0, $count)];
        } while (strlen($ean13) < 13);

        $checksum = 0;
        foreach (str_split(strrev($ean13)) as $pos => $val) {
            $checksum += $val * (3 - 2 * ($pos % 2));
        }
        $checksum = ((10 - ($checksum % 10)) % 10);

        return $ean13.$checksum;
    }

    public function getRandomName()
    {
        $words = array(
            'Lorem', 'Ipsum', 'Sit', 'Amet', 'Adipiscing', 'Elit',
            'Vitae', 'Velit', 'Mauris', 'Dapibus', 'Suscipit', 'Vulputate',
            'Eros', 'Diam', 'Egestas', 'Libero', 'Platea', 'Dictumst',
            'Tempus', 'Commodo', 'Mattis', 'Donec', 'Posuere', 'Eleifend',
        );

        $numWords = 2;
        shuffle($words);

        return 'Product '.implode(' ', array_slice($words, 0, $numWords));
    }

    public function getRandomPrice()
    {
        $cents = array('00', '29', '39', '49', '99');

        return (float) rand(2, 79).'.'.$cents[array_rand($cents)];
    }

    public function getRandomDescription()
    {
        $phrases = array(
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
        );

        $numPhrases = rand(5, 10);
        shuffle($phrases);

        return implode(' ', array_slice($phrases, 0, $numPhrases - 1));
    }
}
