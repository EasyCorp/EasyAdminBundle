Integrating VichUploaderBundle to Upload Files and Images
=========================================================

In this article you'll learn how to allow uploading files in your backends, both
images and regular files, such as PDF files.

Although EasyAdmin doesn't provide any built-in feature to upload files, it
integrates seamlessly with `VichUploaderBundle`_, the most popular file uploader
Symfony bundle.

Installing the File Uploader Bundle
-----------------------------------

1) Install the bundle:

.. code-block:: terminal

    $ composer require vich/uploader-bundle

2) Enable the bundle:

.. code-block:: php

    // app/AppKernel.php
    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            return array(
                // ...
                new Vich\UploaderBundle\VichUploaderBundle(),
            );
        }
    }

3) Add the minimal configuration that makes the bundle work:

.. code-block:: yaml

    vich_uploader:
        db_driver: orm

Uploading Image Files
---------------------

First you'll learn how to upload and preview images in the backend. Then, in the
next section, you'll see how to upload other types of files (such as PDFs).

Configuring the Uploading of Image Files
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Before uploading files, you must configure the "mappings" for the
VichUploaderBundle. These "mappings" tell the bundle where should the files be
uploaded and which paths should be used to display them in the application.

This is the configuration needed for this example:

.. code-block:: yaml

    # app/config/config.yml
    parameters:
        app.path.product_images: /uploads/images/products

    # ...

    vich_uploader:
        # ...
        mappings:
            product_images:
                uri_prefix:         '%app.path.product_images%'
                upload_destination: '%kernel.root_dir%/../web/uploads/images/products'

The ``product_images`` value is a freely chosen name which holds the configuration
for a specific mapping. This value will be used later in the entity configuration.

The uploaded images are stored in the directory defined in ``upload_destination``.
The ``uri_prefix`` option defines the base path prepended to file paths so they
can be displayed in the application. In this example, the ``uri_prefix`` value is
defined as a container parameter, because we'll reuse this value in the EasyAdmin
configuration later.

Preparing your Entities to Persist Images
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Considering that the ``Product`` entity is already created, the first change you
need to make is adding the ``Uploadable`` annotation to the entity class:

.. code-block:: php

    use Symfony\Component\HttpFoundation\File\File;
    use Vich\UploaderBundle\Mapping\Annotation as Vich;

    /**
     * @ORM\Entity
     * @Vich\Uploadable
     */
    class Product
    {
        // ...
    }

Then, you need to add two new properties (``image`` and ``imageFile``):

.. code-block:: php

    use Symfony\Component\HttpFoundation\File\File;
    use Vich\UploaderBundle\Mapping\Annotation as Vich;

    /**
     * @ORM\Entity
     * @Vich\Uploadable
     */
    class Product
    {
        /**
         * @ORM\Column(type="string", length=255)
         * @var string
         */
        private $image;

        /**
         * @Vich\UploadableField(mapping="product_images", fileNameProperty="image")
         * @var File
         */
        private $imageFile;

        /**
         * @ORM\Column(type="datetime")
         * @var \DateTime
         */
        private $updatedAt;

        // ...

        public function setImageFile(File $image = null)
        {
            $this->imageFile = $image;

            // VERY IMPORTANT:
            // It is required that at least one field changes if you are using Doctrine,
            // otherwise the event listeners won't be called and the file is lost
            if ($image) {
                // if 'updatedAt' is not defined in your entity, use another property
                $this->updatedAt = new \DateTime('now');
            }
        }

        public function getImageFile()
        {
            return $this->imageFile;
        }

        public function setImage($image)
        {
            $this->image = $image;
        }

        public function getImage()
        {
            return $this->image;
        }
    }

The ``image`` property stores just the name of the uploaded image and it's
persisted in the database. The ``imageFile`` property stores the binary contents
of the image file and it's not persisted in the database (that's why it doesn't
define a ``@ORM`` annotation).

The ``imageFile`` property must define a ``@Vich\UploadableField`` annotation that
configures both the "mapping" to use (``product_images`` in this case) and the
entity property that stores the image name (``image`` in this case).

Displaying the Images in the ``list`` and ``show`` Views
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Use the special ``image`` type in the ``list`` and ``show`` views to display the
contents of a property as an image:

.. code-block:: yaml

    easy_admin:
        entities:
            Product:
                # ...
                list:
                    fields:
                        - { property: 'image', type: 'image', base_path: '%app.path.product_images%' }
                # ...
                show:
                    fields:
                        - { property: 'image', type: 'image', base_path: '%app.path.product_images%' }

The property used to display the image must be the one that stores the image path
(``image`` in this case) and not the one that stores the binary contents of the
image (``imageFile``). Since this property only stores the image name, you must also
define the ``base_path`` option to prepend the path to make the image accessible.

Instead of hardcoding the ``base_path`` value, this example uses the
``app.path.product_images`` container parameter which also was used in the
VichUploaderBundle configuration.

.. tip::

    If you define `custom namers`_ in VichUploaderBundle, images won't be
    displayed correctly because their paths will be wrong. The simplest solution
    is to define a custom template fragment to display the image and use in it
    the ``vich_uploader_asset()`` Twig function:

    .. code-block:: yaml

        easy_admin:
            entities:
                Product:
                    # ...
                    list:
                        fields:
                            - { property: 'image', template: 'vich_uploader_image.html.twig' }

    .. code-block:: twig

        {# app/Resources/views/easy_admin/vich_uploader_image.html.twig #}
        <img src="{{ vich_uploader_asset(value, 'image') }}" />

Uploading the Images in the ``edit`` and ``new`` Views
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The easiest way to enable uploading images in the forms of the ``edit`` and
``new`` views is to define the type of the property as ``file``:

.. code-block:: yaml

    easy_admin:
        entities:
            Product:
                # ...
                form:
                    fields:
                        - { property: 'imageFile', type: 'file' }
                # ...

In this case, the property must be the one which stores the binary contents of
the image (``imagefile`` in this case) and not the one that stores just the name
(``image`` in this case).

Although this works as expected, its behavior is too basic (for example you can't
see a preview of the existing image before uploading a new file). In order to get
the best experience, use ``VichImageType`` as the type of the property:

.. code-block:: yaml

    easy_admin:
        entities:
            Product:
                # ...
                form:
                    fields:
                        - { property: 'imageFile', type: 'vich_image' }
                # ...

.. tip::

    Even if your application uses Symfony 3 there is no need to use the FQCN of
    the VichImageType (``type: 'Vich\UploaderBundle\Form\Type\VichImageType'``)
    because EasyAdmin supports the short types for some popular third-party bundles.

Uploading Other Types of Files
------------------------------

Adding support for uploading other types of files (such as PDF files) is similar
to uploading images. That's why in the next sections we'll show the required
steps, but we won't repeat the same explanations. In this example, we'll add
support for uploading PDFs that represent the contracts subscribed by the users.

Configuring the Uploading of Files
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Define the "mapping" for the new user contracts:

.. code-block:: yaml

    parameters:
        # ...
        app.path.user_contracts: /uploads/files/user/contracts

    vich_uploader:
        # ...
        mappings:
            user_contracts:
                uri_prefix: '%app.path.user_contracts%'
                upload_destination: '%kernel.root_dir%/../web/uploads/files/user/contracts'

Preparing your Entities to Persist Files
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Considering that the ``User`` entity already exists, add two new properties to
store the name of the contract (``contract`` property) and the binary contents of
the PDF file (``contractFile`` property). Only the first property is persisted in
the database:

.. code-block:: php

    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\HttpFoundation\File\File;
    use Vich\UploaderBundle\Mapping\Annotation as Vich;

    /**
     * @ORM\Entity
     * @Vich\Uploadable
     */
    class User
    {
        /**
         * @ORM\Column(type="string", length=255)
         * @var string
         */
        private $contract;

        /**
         * @Vich\UploadableField(mapping="user_contracts", fileNameProperty="contract")
         * @var File
         */
        private $contractFile;

        // ... getters and setters for these properties
    }

Displaying the Files in the ``list`` and ``show`` Views
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This is the most tricky part of adding support for file uploads. Contrary to
images, it's not easy to provide a preview of the contents for any kind of file.
Instead of trying to do that, we'll display a *View contract (PDF)* link in the
``list`` and ``show`` views.

First, add the ``contract`` property to the list of properties to display:

.. code-block:: yaml

    easy_admin:
        entities:
            User:
                # ...
                list:
                    fields:
                        # ...
                        - contract

Then, add the ``template`` option to define the custom template to use to render
the contents of this property:

.. code-block:: yaml

    easy_admin:
        entities:
            User:
                # ...
                list:
                    fields:
                        # ...
                        - { property: 'contract', template: 'contract.html.twig' }

Now you must create the ``app/Resources/views/easy_admin/contract.html.twig``
template with this content:

.. code-block:: twig

    <a href="{{ value }}">View contract (PDF)</a>

Reload your backend and you'll see the link to the contract PDF. However, if you
click on the link, you won't see the file. The reason is that the ``contract``
property only stores the name of the file, but you also need the public path to
that file (which in this case is stored in the ``app.path.user_contracts`` parameter).

The solution is simple: you can define any number of arbitrary options for a
property and they will be available in your custom template via the ``field_options``
option. So you just need to add a new option in the property definition:

.. code-block:: yaml

    # Before
    - { property: 'contract', template: 'contract.html.twig' }

    # After
    - { property: 'contract', template: 'contract.html.twig', base_path: '%app.path.user_contracts%' }

Then, update the custom template to use this new option:

.. code-block:: twig

    <a href="{{ field_options.base_path ~ '/' ~ value }}">View contract (PDF)</a>

.. tip::

    You can also solve this issue using Symfony asset packages instead of relying
    on EasyAdmin options. The custom template would look like this:

    .. code-block:: twig

        <a href="{{ asset(value, 'user_contracts') }}">View contract (PDF)</a>

Uploading the Files in the ``edit`` and ``new`` Views
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Thanks to the custom ``VichFileType`` provided by the bundle, this is pretty
straightforward:

.. code-block:: yaml

    easy_admin:
        entities:
            Product:
                # ...
                form:
                    fields:
                        - { property: 'contractFile', type: 'vich_file' }
                # ...

.. tip::

    Even if your application uses Symfony 3 there is no need to use the FQCN of
    the VichFileType (``type: 'Vich\UploaderBundle\Form\Type\VichFileType'``)
    because EasyAdmin supports the short types for some popular third-party bundles.

Customizing Form Fields for Image and File Uploading
----------------------------------------------------

EasyAdmin renders the VichUploaderBundle form fields using a custom form theme
that improves their default styling. For example, image fields are rendered as
thumbnails which display the original images when clicking on them.

If you prefer to use the original VichUploaderBundle styles, add its form theme
in the ``form_theme`` configuration option (put it at the end to override
EasyAdmin's default form theme):

.. code-block:: yaml

    easy_admin:
        # ...
        design:
            form_theme: ['horizontal', 'VichUploaderBundle:Form:fields.html.twig']

Apply the same technique in case you want to use your own form theme instead
of the one provided by VichUploaderBundle.

.. _`VichUploaderBundle`: https://github.com/dustin10/VichUploaderBundle
.. _`custom namers`: https://github.com/dustin10/VichUploaderBundle/blob/master/Resources/doc/namers.md
