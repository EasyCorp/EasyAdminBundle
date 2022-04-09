Upgrade between EasyAdmin 4.x versions
======================================

EasyAdmin 4.1.0
---------------

### Updated Country Field Flags

Flags that are optionally displayed in `CountryField` have been redesigned and
updated their format from `.png` to `.svg`. This doesn't require any change in
your application, but if you are using the flag images in your own custom designs,
update the path of the images:

```
# Before
<img alt="Flag of Panama" src="/bundles/easyadmin/images/flags/PA.png">

# After
<img alt="Flag of Panama" src="/bundles/easyadmin/images/flags/PA.svg">
```

### Removed URL signatures

Backend URLs no longer include signatures, because they don't provide any
additional security. The following classes and methods are deprecated:

  * `AdminUrlGenerator::addSignature()` method
  * `AdminUrlGenerator::getSignature()` method
  * `UrlSigner` class and service
  * `Dashboard::disableUrlSignatures()` method

The validity of URL signatures is no longer checked either. If you add signatures
manually, you'll need to check them too.
