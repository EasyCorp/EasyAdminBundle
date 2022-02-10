Extracting translation strings from dashboard
=============================================

Since version 4.X EasyAdmin allows using `Translatable` objects
as labels, titles, help messages etc. If you want to automatically
extract translation from dashboard it is recommended to pass
`Translatable` objects everywhere and let Symfony default extractor
find them.

For example:::

    TextField::new('firstName', t('Name'))

    $crud
        ->setEntityLabelInSingular(t('Product'))
        ->setEntityLabelInPlural(t('Products'))
    ;

.. note::

    If you need to use EasyAdmin placeholders (`%entity_name%` etc.) in title or actions with translatable objects you should either use default Symfony `TranslatableMessage` class or provide them by yourself as parameters. Due to the nature of `TranslatableInterface` it is not possible to append additional parameters to other classes.
