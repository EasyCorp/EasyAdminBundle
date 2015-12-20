How to Use a WYSIWYG Editor
===========================

EasyAdmin uses a `<textarea>` form field to render long text properties:

![Default textarea for text elements](../images/wysiwyg/default-textarea.png)

However, sometimes you need to provide to your users a rich editor, commonly
named *WYSIWYG editor*. Although EasyAdmin doesn't provide any built-in rich text
editor, you can integrate one very easily.

Installing the Rich Text Editor
-------------------------------

The recommended WYSIWYG editor is called [CKEditor](http://ckeditor.com/) and
you can integrate it thanks to the [IvoryCKEditorBundle](https://github.com/egeloen/IvoryCKEditorBundle):

1) Install the bundle:

```bash
$ composer require egeloen/ckeditor-bundle
```

2) Enable the bundle:

```php
// app/AppKernel.php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return array(
            // ...
            new Ivory\CKEditorBundle\IvoryCKEditorBundle(),
        );
    }
}
```

3) Install the JavaScript/CSS files used by the bundle:

```bash
$ php app/console assets:install --symlink
```

Using the Rich Text Editor
--------------------------

IvoryCKEditorBundle provides a new form type called `ckeditor`. Just set the
`type` option of any property to this value to display its contents using a
rich text editor:

```yaml
easy_admin:
    entities:
        Product:
            # ...
            form:
                fields:
                    # ...
                    - { property: 'description', type: 'Ivory\CKEditorBundle\Form\Type\CKEditorType' }
                    # In Symfony 2 you can use the form type alias instead
                    # - { property: 'description', type: 'ckeditor' }
```

Now, the `description` property will be rendered as a rich text editor and not as
a simple `<textarea>`:

![Default WYSIWYG editor](../images/wysiwyg/default-wysiwyg.png)

Customizing the Rich Text Editor
--------------------------------

EasyAdmin tweaks some CKEditor settings to improve the user experience. In case
you need further customization, you have two options:

1) Define the editor options in the `type_options` setting of the property:

```yaml
easy_admin:
    entities:
        Product:
            # ...
            form:
                fields:
                    # ...
                    - { property: 'description', type: 'ckeditor', type_options: { 'config': { 'toolbar': [ { name: 'styles', items: ['Bold', 'Italic', 'BulletedList', 'Link'] } ] } } }
```

In this example, the toolbar is simplified to display just a few common options:

![Simple WYSIWYG editor](../images/wysiwyg/simple-wysiwyg.png)

2) Configure the editor globally in your Symfony application under the
`ivory_ck_editor` option. This is the preferred option because it provides a
consistent look and feel to your application and because it makes the backend
configuration more maintainable. For example:

```yaml
# app/config/config.yml
ivory_ck_editor:
    input_sync: true
    default_config: base_config
    configs:
        base_config:
            toolbar:
                - { name: "basicstyles", items: [ "Bold", "Italic", "Underline", "Strike", "Subscript", "Superscript", "-", "RemoveFormat" ] }
                - { name: "links", items: [ "Link", "Unlink", "Anchor" ] }
                - { name: "insert", items: [ "Image", "Table", "HorizontalRule", "SpecialChar" ] }
                - { name: "tools", items: [ "Maximize" ] }
                - "/"
                - { name: "paragraph", items: [ "NumberedList", "BulletedList", "-", "Outdent", "Indent", "-", "Blockquote" ] }
                - { name: "styles", items: [ "Styles", "Format" ] }
                - { name: "document", items: [ "Source", "-" ] }
                - { name: "about", items: [ "About" ] }

easy_admin:
    entities:
        Product:
            # ...
            form:
                fields:
                    # ...
                    - { property: 'description', type: 'Ivory\CKEditorBundle\Form\Type\CKEditorType' }
```

Check out the original CKEditor documentation to get
[its full list of configuration options] (http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html).
