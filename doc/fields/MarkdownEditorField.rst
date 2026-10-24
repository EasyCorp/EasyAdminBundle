EasyAdmin Markdown Editor Field
===============================

``MarkdownEditorField`` lets you write Markdown with a formatting toolbar
and live preview. It stores the Markdown text in your entity and displays
formatted content on read-only pages.

.. note::

    This field requires :doc:`EasyAdmin Pro </pro>`, a paid extension
    to EasyAdmin. Follow its installation guide to install the bundle
    and assets. No additional bundle configuration is needed.

Basic Usage
-----------

Use the field in the ``configureFields()`` method of any CRUD
controller, exactly as you would use any built-in EasyAdmin field::

    // src/Controller/Admin/ProductCrudController.php
    namespace App\Controller\Admin;

    use App\Entity\Product;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
    use EasyCorp\Bundle\EasyAdminProBundle\Field\MarkdownEditorField;

    class ProductCrudController extends AbstractCrudController
    {
        public static function getEntityFqcn(): string
        {
            return Product::class;
        }

        public function configureFields(string $pageName): iterable
        {
            yield MarkdownEditorField::new('description');
        }
    }

The field loads its own editor and rendering assets. You can use the usual
EasyAdmin methods such as ``setLabel()``, ``setHelp()``, ``setColumns()``
and ``hideOnIndex()``. See the :doc:`fields documentation </fields>`.

Options
-------

Use ``setNumOfRows()`` to set the editor's minimum height. The default is
5 rows, and the value must be at least 1. The editor grows as you type::

    yield MarkdownEditorField::new('description')
        ->setNumOfRows(10)
        ->setColumns(12)
        ->setHelp('Use Markdown for headings, lists and links.');

.. tip::

    For longer content, use the side-by-side preview or fullscreen mode
    from the toolbar. You can keep writing while checking the result.

How Values Are Displayed
------------------------

* On ``new`` and ``edit`` pages, you get the editor, toolbar and preview.
* On ``detail`` pages, the formatted content appears directly in the page.
* On ``index`` pages, a "View content" button opens it in a modal.

Security
--------

The field renders Markdown in the browser with `marked`_ and sanitizes
the resulting HTML with `DOMPurify`_ to remove unsafe markup.

Your database keeps the original Markdown. If you display it elsewhere,
such as on your public website, sanitize the HTML there too. Sanitization
inside EasyAdmin doesn't protect other places where you render the value.

Images and Other Markdown Features
----------------------------------

The toolbar covers common formatting: headings, bold, italic, lists,
quotes, links and code. For syntax without a toolbar button, such as
tables, type the Markdown directly.

There isn't an image uploader. To include an image, use the URL of an
image you've already uploaded:

.. code-block:: markdown

    ![Front view of the product](https://example.com/images/product.jpg)

If your public website uses a different Markdown renderer, check its
output too: it may differ from the editor's preview.

.. _`marked`: https://marked.js.org/
.. _`DOMPurify`: https://github.com/cure53/DOMPurify
