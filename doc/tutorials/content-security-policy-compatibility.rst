How to Make Admin Pages CSP Compatible
================================================

EasyAdminBundle uses several inline ``<script>`` and ``<style>`` blocks to enable
dynamic functionality. If you have implemented a Content-Security-Policy then
these will likely violate your policy, in which case we need to specify a ``nonce``
for each inline block. Luckily, it's a simple process to enable CSP compatibility.

Create a CspNonceGenerator
--------------------------

The first thing you need to do is create a class that implements the
``CspNonceGeneratorInterface``:

.. code-block:: php

    use EasyCorp\Bundle\EasyAdminBundle\CspNonceGeneratorInterface;

    class CspNonceGenerator implements CspNonceGeneratorInterface
    {
      /**
       * Get a nonce to be used for inline script tags.
       */
      public function getScriptNonce(): string
      {
          // return a nonce that's added to the `script-src` section
          // of your `content-security-policy` header
      }

      /**
       * Get a nonce to be used for inline style tags.
       */
      public function getStyleNonce(): string
      {
          // return a nonce that's added to the `style-src` section
          // of your `content-security-policy` header
      }
    }

You can use dependency injection to inject whatever service handles your
``Content-Security-Policy`` header. For example, if you are using the
`NelmioSecurityBundle`_ then you would inject ``ContentSecurityPolicyListener``
and call the ``getNonce()`` method.

Alias your service to the CspNonceGeneratorInterface
----------------------------------------------------

Now you need to create a `service alias`_ in the Symfony container to tell the
EasyAdminBundle to use your CspNonceGenerator:

.. code-block:: yaml

    # config/services.yaml
    EasyCorp\Bundle\EasyAdminBundle\Services\CspNonceGeneratorInterface: '@App\Services\CspNonceGenerator'

.. _`NelmioSecurityBundle`: https://github.com/nelmio/NelmioSecurityBundle#nonce-for-inline-script-handling
.. _`service alias`: https://symfony.com/doc/current/service_container/autowiring.html#using-aliases-to-enable-autowiring

