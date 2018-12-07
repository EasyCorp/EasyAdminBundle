UPGRADE FROM 1.x to 2.0
=======================

Although EasyAdmin 2.0 is a new major version, it doesn't contain many
backward compatibility breaks when you upgrade from EasyAdmin 1.x versions.
Also, the breaking changes are related to mostly unimportant features.

Upgraded Requirements
---------------------

The most important change is that EasyAdmin now requires at least PHP 7.1.3 and
Symfony ^4.1 components. If you can't upgrade these requirements, you can't
upgrade to EasyAdmin 2.0 and you must keep using 1.x versions.

Deprecated Features
-------------------

Upgrade to the latest EasyAdmin 1.x version and you'll see in the application
logs all the deprecated features that you are using. You must remove all of them
before upgrading to EasyAdmin 2.x.

Most deprecations are related to design config options that have been removed in
EasyAdmin 2.x. Remove (or update appropriately) those deprecated options in
your configuration file and you'll be ready to upgrade. The docs have also been
updated to warn about any deprecated feature.

New Base Controller
-------------------

Symfony 4.2 has deprecated the base Controller class in favor of AbstractController
class. They are similar, but AbstractController only allows you to access to
some services using `$this->get('service_id')` instead of allowing you to access
to all available services.

EasyAdmin 1.x provided one base controller extending from Symfony's `Controller`.
EasyAdmin 2.0 provides two base controllers:

* The first one is the same as in EasyAdmin 1.x: `EasyCorp\Bundle\EasyAdminBundle\AdminController`
  It extends from the deprecated `Controller` class, so you'll see deprecation
  messages in your logs. Using it will ensure that your app keeps working because
  you can still use `$this->get('service_id')` in the controller.
* The second one is a new controller called `EasyCorp\Bundle\EasyAdminBundle\EasyAdminController`
  It extends from `AbstractController` so you won't get any deprecation message.
  However, your apps may break if they use `$this->get('service_id')` in the
  controller.

It's recommended to use the new `EasyAdminController` base controller to get
rid of legacy deprecations. If you need to get services, don't use `$this->get('service_id')`
and instead, inject the services in your controller's constructor or actions as
recommended in Symfony 4.x apps.

### Before

Extend from `EasyCorp\Bundle\EasyAdminBundle\AdminController` class and:

```php
// in some place from extended child controller:
$this->get('custom_service')->doSomething();
```

### After

Extend from `EasyCorp\Bundle\EasyAdminBundle\EasyAdminController` class and:

**Option 1**: Inject your service as argument of the constructor:

```php
private $customService;

public function __constructor(CustomService $customService)
{
    $this->customService = $customService;
}

// then use $this->customService instead of $this->get('custom_service')
```

**Option 2**: Override the `getSubscribedServices()` method and add your services
to the list:

```php
public static function getSubscribedServices()
{
    return parent::getSubscribedServices() + [
        'custom_service' => CustomService::class,
    ];
}

// then use $this->get('custom_service') as before
```

Redesigned Interface
--------------------

The interface of the backend has been redesigned entirely. We kept all the
original Twig blocks and their names, so your templates shouldn't break when
upgrading.

We also kept most of CSS classes and IDs, so your design customizations should
keep working. However, we changed some CSS classes/IDs and we removed some
HTML attributes related to the responsive design. You may need to tweak a bit
your CSS customizations to fix those edge cases.

Finally, the design customization is now based on CSS variables, so it's easier
to fully customize the entire backend interface. Read the updated chapter about
design to learn all the details.
