Chapter 2. Your First Backend
=============================

Creating your first backend will take you around 30 seconds, because you just
have to create a simple configuration file.

Let's suppose that you already have defined in your Symfony application three
Doctrine ORM entities called `Customer`, `Order` and `Product`. Open your main
application configuration file (usually `app/config/config.yml`) and add the
following configuration:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        - AppBundle\Entity\Customer
        - AppBundle\Entity\Order
        - AppBundle\Entity\Product
```

**Congratulations! You've just created your first fully-featured backend!**
Browse the `/admin` URL in your Symfony application and you'll get access to
the admin backend:

![Default listing interface](images/easyadmin-customer-listing.png)

Creating a backend is that simple because EasyAdmin doesn't generate any code,
not even for the templates. All resources are served on-the-fly to ensure an
exceptional developer experience.

Without any further configuration, EasyAdmin guesses the best settings to make
your admin backend look "good enough". This may be acceptable for simple
backends and rapid prototypes, but most of the times, you need to customize
some parts of the backend. Keep reading to learn how to do it.
