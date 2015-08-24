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


# Extraction with JMSTranslationBundle

An extractor for the JMSTranslationBundle allows to get automatically these translations.

Run the extract andenable the `easyadmin_translation_entity_extractor` extractor.

Example:

        php app/console translation:extract --config=app --enable-extractor=easyadmin_translation_entity_extractor

