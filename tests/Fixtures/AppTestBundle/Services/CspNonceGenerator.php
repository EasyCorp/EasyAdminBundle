<?php

namespace AppTestBundle\Services;

use EasyCorp\Bundle\EasyAdminBundle\Services\CspNonceGeneratorInterface;

class CspNonceGenerator implements CspNonceGeneratorInterface
{
    /**
     * Get a nonce to be used for inline script tags.
     */
    public function getScriptNonce(): string
    {
        return 'random-script-nonce';
    }

    /**
     * Get a nonce to be used for inline style tags.
     */
    public function getStyleNonce(): string
    {
        return 'random-style-nonce';
    }
}
