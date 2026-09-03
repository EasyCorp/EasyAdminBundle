EasyAdmin File Field
====================

This field is used to manage the uploading of files (PDFs, documents, etc.) to the
backend. The entity property only stores the path to the file (relative to the
upload directory). The actual file contents are stored on the server filesystem
or on any remote system configured via the `league/flysystem-bundle`_.

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\FileField``
* **Doctrine DBAL Type** used to store this value: ``string``
* **Symfony Form Type** used to render the field: ``FileUploadType``, a custom
  form type created by EasyAdmin
* **Rendered as**:

  .. code-block:: html

    <!-- when loading the page this is transformed into a dynamic widget via JavaScript -->
    <input type="file">

Options
-------

``setBasePath``
~~~~~~~~~~~~~~~

By default, files are linked in read-only pages (``index`` and ``detail``) "as is",
without changing their path. If you serve your files under some path (e.g.
``uploads/files/``) use this option to configure that::

    yield FileField::new('...')->setBasePath('uploads/files/');

``setUploadDir``
~~~~~~~~~~~~~~~~

**This option is required.** Use it to set the directory where uploaded files are
stored. Relative paths are resolved from your project root directory and absolute
filesystem paths are used as-is::

    // relative to your project root directory
    yield FileField::new('...')->setUploadDir('public/uploads/files/');
    // absolute filesystem paths are also supported
    yield FileField::new('...')->setUploadDir('/mnt/data/uploads/');
    // the property will only store the file path relative to this dir
    // (e.g. 'catalog.pdf', 'venue/contract.docx')

``FileField`` does not define a default upload directory. If you don't call this
method, an exception will be thrown.

.. note::

    Files stored outside your project's ``public/`` directory are not accessible
    by web browsers, so the upload form doesn't display the view/download links
    for them. If you serve those files by other means (e.g. a custom controller),
    define the public path of those links with the
    :ref:`setFormTypeOption() method <field-configuration>`:
    ``->setFormTypeOption('download_path', '...')``.

``setFileConstraints``
~~~~~~~~~~~~~~~~~~~~~~

By default, no validation constraints are applied to the uploaded file. Use this
option to define the constraints applied to the uploaded file::

    use Symfony\Component\Validator\Constraints\File;

    yield FileField::new('...')->setFileConstraints(new File(filenameCharset: 'ASCII'));

``setUploadedFileNamePattern``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, uploaded files are stored with the same file name and extension as
the original files. Use this option to rename the files after uploading.
The string pattern passed as argument can include the following special values:

* ``[DD]``, the day part of the current date (with leading zeros, obtained as ``date('d')``)
* ``[MM]``, the month part of the current date (with leading zeros, obtained as ``date('m')``)
* ``[YYYY]``, the full year of the current date (obtained as ``date('Y')``)
* ``[YY]``, the two-digit year of the current date (obtained as ``date('y')``)
* ``[hh]``, the hour of the current time in 24h format (with leading zeros, obtained as ``date('H')``)
* ``[mm]``, the minutes of the current time (with leading zeros, obtained as ``date('i')``)
* ``[ss]``, the seconds of the current time (with leading zeros, obtained as ``date('s')``)
* ``[timestamp]``, the current timestamp (obtained as ``time()``; e.g. ``1773256492``)
* ``[name]``, the original name of the uploaded file
* ``[slug]``, the slug of the original name of the uploaded file generated with Symfony's
  String component (all lowercase and using ``-`` as the separator)
* ``[extension]``, the original extension of the uploaded file (without the leading dot, e.g. ``png``)
  (if the file has multiple extensions, only the last one is returned)
* ``[contenthash]``, a SHA1 hash of the original file contents (40-char hexadecimal
  string, e.g. ``3dfd6a9fbb83413b7f47c913ce2a95416dc6da88``)
* ``[randomhash]``, a random hash not related in any way to the original file contents
  (40-char hexadecimal string, e.g. ``8ff61576fb5f07f82dd9dbb7874cef74e24fcb26``)
* ``[uuid]``, a random UUID v4 value formatted as RFC 4122 (36-char hexadecimal string,
  e.g. ``d9e7a184-5d5b-11ea-a62a-3499710062d0``) (generated with Symfony's Uid component)
* ``[uuid32]``, a random UUID v4 value formatted as Base 32 (26-char string,
  e.g. ``6SWYGR8QAV27NACAHMK5RG0RPG``) (generated with Symfony's Uid component)
* ``[uuid58]``, a random ULID value formatted as Base 58 (22-char string,
  e.g. ``TuetYWNHhmuSQ3xPoVLv9M``) (generated with Symfony's Uid component)
* ``[ulid]``, a random ULID value (26-char string, e.g. ``01AN4Z07BY79KA1307SR9X4MV3``)
  (generated with Symfony's Uid component)

.. deprecated:: 5.1

    The ``[day]``, ``[month]`` and ``[year]`` placeholders are deprecated. Use
    ``[DD]``, ``[MM]`` and ``[YYYY]`` instead. The old placeholders will be
    removed in EasyAdmin 6.0.

You can combine them in any way::

    yield FileField::new('...')
        ->setUploadedFileNamePattern('[YYYY]/[MM]/[DD]/[slug]-[contenthash].[extension]');

The argument of this method also accepts a closure that receives Symfony's
``UploadedFile`` instance as its first argument::

    yield FileField::new('...')->setUploadedFileNamePattern(
        fn (UploadedFile $file): string => sprintf('upload_%d_%s.%s', random_int(1, 999), $file->getFilename(), $file->guessExtension())
    );

Unlike the :doc:`ImageField </fields/ImageField>` closure, the ``FileField``
closure also receives the **current entity instance** as a second argument. This
allows naming files based on entity data. On the ``new`` page, the entity is a
fresh instance (possibly without an ID); on the ``edit`` page, it has its
current database values::

    yield FileField::new('...')->setUploadedFileNamePattern(
        static fn (UploadedFile $file, MyEntity $entity): string => sprintf('%s/[name].[extension]', $entity->getSlug())
    );

The string returned by the closure still goes through the placeholder
substitution described above, so you can combine both mechanisms (as in the
``'[name].[extension]'`` part of the previous example).

``isDeletable``
~~~~~~~~~~~~~~~

By default, the file upload widget shows a "delete" checkbox that allows users
to remove the uploaded file. Use this option to hide that checkbox::

    yield FileField::new('...')->isDeletable(false);

``isDownloadable``
~~~~~~~~~~~~~~~~~~

By default, a link to download the uploaded file is displayed next to the form
field. Use this option to hide that link::

    yield FileField::new('...')->isDownloadable(false);

``isViewable``
~~~~~~~~~~~~~~

By default, a link to view the uploaded file is displayed next to the form field.
Use this option to hide that link::

    yield FileField::new('...')->isViewable(false);

``maxSize``
~~~~~~~~~~~

Use this option to set the maximum allowed file size. The value can be an integer
(number of bytes) or a suffixed string (e.g. ``'200k'``, ``'2M'``, ``'1G'`` for
SI units or ``'1Ki'``, ``'1Mi'`` for binary units)::

    yield FileField::new('...')->maxSize('10M');
    yield FileField::new('...')->maxSize(1048576); // 1 MB in bytes

You can customize the error message by passing a second argument::

    yield FileField::new('...')->maxSize('5M', 'The file "{{ name }}" is too large ({{ size }} {{ suffix }}). Maximum allowed: {{ limit }} {{ suffix }}.');

The available placeholders for the error message are: ``{{ file }}`` (the absolute
file path), ``{{ name }}`` (the base file name), ``{{ size }}`` (the file size),
``{{ limit }}`` (the maximum allowed size) and ``{{ suffix }}`` (the size unit,
e.g. ``kB``, ``MB``).

``mimeTypes``
~~~~~~~~~~~~~

By default, all file types are accepted. Use this option to restrict the allowed
MIME types. The value is a string with a comma-separated list of file extensions
or MIME types. You can use any value valid in the `HTML "accept" attribute`_::

    yield FileField::new('...')->mimeTypes('.pdf,.doc,.docx');
    yield FileField::new('...')->mimeTypes('video/*');
    yield FileField::new('...')->mimeTypes('image/*');
    yield FileField::new('...')->mimeTypes('.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document');

When this option is set, the corresponding MIME types are also added
automatically as validation constraints. You can customize the error message
shown when the validation fails by passing a second argument::

    yield FileField::new('...')->mimeTypes('.pdf', 'The file "{{ name }}" has MIME type "{{ type }}" but only "{{ types }}" are allowed.');

The available placeholders for the error message are: ``{{ file }}`` (the absolute
file path), ``{{ name }}`` (the base file name), ``{{ type }}`` (the MIME type of
the uploaded file) and ``{{ types }}`` (the list of allowed MIME types).

Replaced File Behavior
~~~~~~~~~~~~~~~~~~~~~~

When a user uploads a new file to replace an existing one, ``FileField``
controls what happens to the old file on disk. There are three behaviors:

``deleteReplacedFile``
......................

This is the **default** behavior. The old file is deleted from disk. If the new
file has the same name as an existing file, a numeric suffix (``_1``, ``_2``,
etc.) is appended to avoid conflicts::

    yield FileField::new('...')->deleteReplacedFile();

``keepReplacedFile``
....................

The old file is kept on disk. If you upload a new file with the same name, the
contents are silently overwritten::

    yield FileField::new('...')->keepReplacedFile();

``keepReplacedFileOrFail``
..........................

The old file is kept on disk. If the new file's name conflicts with an existing
file, an error is thrown::

    yield FileField::new('...')->keepReplacedFileOrFail();

Serving Uploaded Files Safely
-----------------------------

Uploaded files are typically stored under the public web root, so they are
served by the web server with the ``Content-Type`` that matches their
extension. Some file types (e.g. ``.html`` and ``.svg``) are rendered *inline*
by browsers and can run embedded JavaScript. If such a file is opened from the
same origin as the backend, that script executes in the session of the user who
opens it (a stored `XSS`_ attack).

To prevent this, EasyAdmin **forces a download** (instead of opening the file
inline) for uploaded files whose type the browser would render and could
execute scripts from, such as ``.html``, ``.svg``, ``.xhtml`` and ``.xml``.
Safe types (images, PDFs, office documents, etc.) keep opening inline as usual.
You can change this default behavior with the following option.

``allowRiskyInlineRender``
~~~~~~~~~~~~~~~~~~~~~~~~~~

If you fully trust the uploaded contents, use this option to disable the
protection above and render those files inline again::

    yield FileField::new('...')->allowRiskyInlineRender();

.. caution::

    Only enable this when uploads are restricted to trusted users, sanitized, or
    served from a separate domain. Otherwise a malicious ``.html`` or ``.svg``
    upload can run JavaScript in the backend user's session.

.. note::

    Forcing a download in the backend only protects EasyAdmin's own links. The
    files are still served as static assets, so a user who opens the file URL
    directly is not protected by this setting. For full protection, serve the
    upload directory with the ``Content-Disposition: attachment`` header (via your
    web server configuration) or store uploads outside the public web root.

Flysystem Integration (Remote Storage)
--------------------------------------

By default, ``FileField`` stores uploaded files on the local filesystem. If you
need to store files in a remote storage service (Amazon S3, Google Cloud Storage,
Azure Blob Storage, etc.) you can integrate with `Flysystem`_ via the
`league/flysystem-bundle`_.

Installation
~~~~~~~~~~~~

Install the Flysystem bundle and the adapter for your storage service:

.. code-block:: terminal

    $ composer require league/flysystem-bundle

Then install the adapter you need (e.g. for Amazon S3):

.. code-block:: terminal

    $ composer require league/flysystem-aws-s3-v3

Configure Flysystem in your application:

.. code-block:: yaml

    # config/packages/flysystem.yaml
    flysystem:
        storages:
            default.storage:
                aws:
                    client: 'Aws\S3\S3Client'
                    bucket: 'my-bucket'

Usage
~~~~~

Point the field at the storage you configured::

    yield FileField::new('attachment')
        ->setFlysystemStorage('default.storage')
        ->setUploadDir('files/')
        ->setUploadedFileNamePattern('[uuid].[extension]');

``setFlysystemStorage``
~~~~~~~~~~~~~~~~~~~~~~~

Sets the Flysystem storage service ID used to upload and delete files. This is
the key you defined under ``flysystem.storages`` in your Flysystem
configuration::

    yield FileField::new('...')->setFlysystemStorage('default.storage');

When this option is set, EasyAdmin automatically replaces the local upload,
delete, and validation callables with Flysystem equivalents. The upload directory
configured with ``setUploadDir()`` is used as a path prefix inside the Flysystem
storage (not as a local directory).

``setFlysystemUrlPrefix``
~~~~~~~~~~~~~~~~~~~~~~~~~

**This method is optional.** By default, EasyAdmin generates the public URL of
each file from the Flysystem storage itself (via the ``public_url`` or
``public_url_generator`` option configured for that storage). Use this method
only to override that default; for example when the admin UI needs to serve
files from a different host than the one configured in Flysystem, or when your
Flysystem storage has no public URL generator configured::

    yield FileField::new('...')->setFlysystemUrlPrefix('https://cdn.example.com/uploads');

When set, this prefix is combined with the file path to generate the full URL
shown in the ``index`` and ``detail`` pages, and takes precedence over the
Flysystem ``public_url`` configuration.

.. note::

    When using Flysystem, the ``setBasePath()`` option is ignored. Configure
    ``public_url`` in your Flysystem storage, or call ``setFlysystemUrlPrefix()``
    to override it.

How It Works
~~~~~~~~~~~~

When Flysystem is configured for a field:

* **Upload**: new files are written to the Flysystem storage using
  ``writeStream()`` instead of being moved to a local directory.
* **Delete**: files are removed from the Flysystem storage using ``delete()``
  instead of ``unlink()``.
* **Validation**: file existence is checked using ``fileExists()`` instead of
  the local filesystem.
* **Display**: file URLs are built from the configured URL prefix instead of
  using the Symfony ``asset()`` function.

All existing options (``setUploadedFileNamePattern()``, ``setFileConstraints()``,
``mimeTypes()``, ``maxSize()``, replaced file behaviors, ``isDeletable()``) continue
to work exactly the same way with Flysystem.

.. _`HTML "accept" attribute`: https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Attributes/accept
.. _`Flysystem`: https://flysystem.thephpleague.com
.. _`league/flysystem-bundle`: https://github.com/thephpleague/flysystem-bundle
.. _`XSS`: https://owasp.org/www-community/attacks/xss/
