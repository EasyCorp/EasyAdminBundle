Chapter 2. Your First Backend
=============================

Creating your first backend will take you less than 30 seconds. Let's suppose
that your Symfony application defines three Doctrine ORM entities called
`Product`, `Category` and `User`.

Creating the backend for those entities just require you to add the following
configuration in the `app/config/config.yml` file:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        - AppBundle\Entity\Product
        - AppBundle\Entity\Category
        - AppBundle\Entity\User
```

**Congratulations! You've just created your first fully-featured backend!**
Browse the `/admin` URL in your Symfony application and you'll get access to
the admin backend:

![Default listing interface](../images/easyadmin-list-view.png)

Creating a backend is that simple because EasyAdmin doesn't generate any code,
not even for the templates. All resources are served on-the-fly to ensure an
exceptional developer experience.

Without any further configuration, EasyAdmin guesses the best settings to make
your admin backend look "good enough". This may be acceptable for simple
backends and rapid prototypes, but most of the times, you need to customize
some parts of the backend. Keep reading to learn how to do it.

-------------------------------------------------------------------------------

&larr; [Chapter 1. Installation](1-installation.md)  |  [Chapter 3. Backend Configuration](3-backend-configuration.md) &rarr;
