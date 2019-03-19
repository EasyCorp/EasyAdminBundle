<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Services;

/**
 * Classes implementing this interface are used to generate CSP nonces for inline script and style tags.
 */
interface CspNonceGeneratorInterface
{
    /**
     * Get a nonce to be used for inline script tags.
     */
    public function getScriptNonce(): string;

    /**
     * Get a nonce to be used for inline style tags.
     */
    public function getStyleNonce(): string;
}
