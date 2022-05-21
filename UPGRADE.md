Upgrade between EasyAdmin 4.x versions
======================================

EasyAdmin 4.2.0
---------------

### Signature changes

We've changed how translations are managed internally in EasyAdmin. Before we
passed translated contents to templates. Now we pass Symfony's "translatable"
objects to templates.

This means that many classes have been changed to allow using `TranslatableMessage` objects
in places where previously only `string`, `false` or `null` were allowed.
Return types were also loosened to allow returning `TranslatableMessage` where applicable.

In practice this should not affect to most applications because `TranslatableMessage`
objects gracefully transform to strings when needed. However, you might need to
update some checks where you only expected string scalar values and now you might
also get `TranslatableMessage` objects.

Full list of changes in final classes:

    Config\Action (new, setLabel); only docblocks and deprecation logic
    Config\Menu*MenuItem (constructors)
    Config\MenuItem (linkTo*, section, subMenu)
    Dto\ActionDto (getLabel, setLabel and private field)
    Dto\CrudDto (getEntityLabelInSingular, setEntityLabelInSingular,getEntityLabelInPlural, setEntityLabelInPlural, setCustomPageTitle, getHelpMessage, setHelpMessage)
    Dto\FieldDto (getLabel, setLabel, getHelp, setHelp)
    Dto\FilterDto (getLabel, setLabel); only docblocks
    Dto\MenuItemDto (getLabel, setLabel)
    Field*Field (new); only docblocks
    Field\FormField (addPanel, addTab)

List of signature changes in non-final classes and traits:

    Config\Crud (setHelp)
    Field\FieldTrait (setLabel, setHelp); setLabel only in docblock

### New setTranslatableChoices() method in `ChoiceField`

Use this method when defining choice labels with translatable objects. For example:

    yield ChoiceField::new('...')->setTranslatableChoices([
        'paid' => t('Paid Invoice'),
        'pending' => t('Invoice Sent but Unpaid'),
        'refunded' => 'Refunded Invoice', // You can mix strings with TranslatableMessage objects
    ]);

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
