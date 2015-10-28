<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__)
    ->exclude(array('vendor', 'build'))
;

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->fixers(array('-unalign_double_arrow', '-phpdoc_short_description'))
    ->finder($finder)
;
