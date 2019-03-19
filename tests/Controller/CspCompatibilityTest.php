<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use AppTestBundle\Services\CspNonceGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Services\CspNonceGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Services\NonceHandler;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CspCompatibilityTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(['environment' => 'csp_compatibility']);
    }

    public function testListViewActionIncludesNonce()
    {
        $nonceGenerator = $this->getNonceGenerator();

        $crawler = $this->requestListView();

        $this->assertCount(2, $crawler->filter(\sprintf('script[nonce=%s]', $nonceGenerator->getScriptNonce())));
        $this->assertCount(1, $crawler->filter(\sprintf('style[nonce=%s]', $nonceGenerator->getStyleNonce())));
    }

    public function testListViewActionsExcludesNonce()
    {
        $crawler = $this->requestListView();

        $this->assertCount(0, $crawler->filter('script[nonce]'));
        $this->assertCount(0, $crawler->filter('style[nonce]'));
    }

    public function testEditViewActionIncludesNonce()
    {
        $nonceGenerator = $this->getNonceGenerator();

        $crawler = $this->requestEditView();

        $this->assertCount(3, $crawler->filter(\sprintf('script[nonce=%s]', $nonceGenerator->getScriptNonce())));
        $this->assertCount(1, $crawler->filter(\sprintf('style[nonce=%s]', $nonceGenerator->getStyleNonce())));
    }

    public function testEditViewActionExcludesNonce()
    {
        $crawler = $this->requestEditView();

        $this->assertCount(0, $crawler->filter('script[nonce]'));
        $this->assertCount(0, $crawler->filter('style[nonce]'));
    }

    public function testShowViewActionIncludesNonce()
    {
        $nonceGenerator = $this->getNonceGenerator();

        $crawler = $this->requestShowView();

        $this->assertCount(2, $crawler->filter(\sprintf('script[nonce=%s]', $nonceGenerator->getScriptNonce())));
        $this->assertCount(1, $crawler->filter(\sprintf('style[nonce=%s]', $nonceGenerator->getStyleNonce())));
    }

    public function testShowViewActionExcludesNonce()
    {
        $crawler = $this->requestShowView();

        $this->assertCount(0, $crawler->filter('script[nonce]'));
        $this->assertCount(0, $crawler->filter('style[nonce]'));
    }

    public function testNewViewActionIncludesNonce()
    {
        $nonceGenerator = $this->getNonceGenerator();

        $crawler = $this->requestNewView();

        $this->assertCount(3, $crawler->filter(\sprintf('script[nonce=%s]', $nonceGenerator->getScriptNonce())));
        $this->assertCount(1, $crawler->filter(\sprintf('style[nonce=%s]', $nonceGenerator->getStyleNonce())));
    }

    public function testNewViewActionExcludesNonce()
    {
        $crawler = $this->requestNewView();

        $this->assertCount(0, $crawler->filter('script[nonce]'));
        $this->assertCount(0, $crawler->filter('style[nonce]'));
    }

    private function getNonceGenerator(): CspNonceGeneratorInterface
    {
        // Give the NonceHandler a NonceGenerator
        $nonceHandler = new NonceHandler(new CspNonceGenerator());
        static::$container->set(NonceHandler::class, $nonceHandler);
        $nonceGenerator = $nonceHandler->getGenerator();

        return $nonceGenerator;
    }
}
