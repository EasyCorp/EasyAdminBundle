<?php

$symfonyVersion = $argv[1];

$content = str_replace('%SYMFONY_VERSION%', $symfonyVersion, file_get_contents(__DIR__ . '/composer.json'));

if ("2.3.*" === $symfonyVersion) {
    $data = json_decode($content, true);
    $data['conflict'] = array(
        'symfony/security-acl' => '*',
        'symfony/security-core' => '*',
    );
    $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

file_put_contents(__DIR__ . '/../composer.json', $content);
