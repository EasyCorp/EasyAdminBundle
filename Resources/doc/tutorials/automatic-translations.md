Automatic translations
===========================

In this article you'll learn how to generate translations for the entities.

# Configuration

```yaml
easy_admin:
    automatic_translation: true
    # ...
```

This options allow the generation of labels for all fields of all entities.

It does not overwrite the label describes in the configuration if there is one.

Example:
An entity `Object` has a `name` attribute.

The views will try to translate `Object.field.name` text. (in list/new/...)


# Extraction

An extractor allows to get automatically these translations.

Run the ` translation:update` command of symfony, it will dump the translations in app/Resources/translations/EasyAdminBundle.en.yml

Example:

        php app/console translation:update --dump-messages --force en

