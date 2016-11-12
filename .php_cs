<?php

$finder = Symfony\CS\Finder\Finder::create()
    ->in(__DIR__)
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->exclude(array('build', 'vendor'))
;

return Symfony\CS\Config::create()
    ->setUsingCache(true)
    ->fixers(array('-unalign_double_arrow', '-phpdoc_short_description'))
    ->finder($finder)
;
