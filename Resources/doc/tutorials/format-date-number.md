How to Format Dates and Numbers
===============================

Customizing Date and Time Properties
------------------------------------

### Display-Based Views (`list` and `show`)

By default, these are the formats applied to date and time properties (read the
[date configuration options](http://php.net/manual/en/function.date.php) in the
PHP manual in case you don't understand the meaning of these formats):

  * `date`: `Y-m-d`
  * `time`:  `H:i:s`
  * `datetime`: `F j, Y H:i`

These default formats can be overridden in two ways: globally for all entities
and locally for each entity property. The global `formats` option sets the
formats for all entities and properties (define any or all the `date`, `time`
and `datetime` options):

```yaml
easy_admin:
    formats:
        date:     'd/m/Y'
        time:     'H:i'
        datetime: 'd/m/Y H:i:s'
    entities:
        # ...
```

The values of the `date`, `time` and `datetime` options are passed to the
`format()` method of the `DateTime` class, so you can use any of the
[date configuration options](http://php.net/manual/en/function.date.php) defined
by PHP.

Date/time based properties can also define their formatting using the `format`
option. This local option always overrides the global format:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:
                fields:
                    - { property: 'dateOfBirth', format: 'j/n/Y' }
                    # ...
    # ...
```

### Form-Based Views (`edit` and `new`)

// TODO ...

Customizing Numeric Properties
------------------------------

### Display-Based Views (`list` and `show`)

Numeric properties (`bigint`, `integer`, `smallint`, `decimal`, `float`) are
formatted by default according to the locale of your Symfony application. Use
the `format` option to explicitly set the format applied to numbers.

The global `formats` option applies the same formatting for all entities and
properties:

```yaml
easy_admin:
    formats:
        # ...
        number: '%.2f'
    entities:
        # ...
```

In this case, the value of the `number` option is passed to the `sprintf()`
function, so you can use any of the
[PHP format specifiers](http://php.net/manual/en/function.sprintf.php).

Numeric properties can also define their formatting using the `format`
option. This local option always overrides the global format:

```yaml
easy_admin:
    entities:
        Product:
            class: AppBundle\Entity\Product
            list:
                fields:
                    - { property: 'serialNumber', format: '%010s' }
                    - { property: 'margin', format: '%01.2f' }
                    # ...
    # ...
```

### Form-Based Views (`edit` and `new`)

// TODO ...
