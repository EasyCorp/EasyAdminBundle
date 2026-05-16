---
name: twig-component
description: Symfony UX TwigComponent for reusable UI elements. Use when creating reusable Twig templates with PHP backing classes, component composition, props, slots/blocks, computed properties, or anonymous components. Triggers - twig component, AsTwigComponent, reusable template, component props, twig blocks, component slots, anonymous component, Symfony UX component, HTML component, component library, design system component, UI kit, reusable button, reusable card, PreMount, PostMount, mount method. Also trigger for any question about building a reusable piece of UI in Symfony, even if the user doesn't mention TwigComponent by name.
license: MIT
metadata:
  author: Simon Andre
  email: smn.andre@gmail.com
  url: https://smnandre.dev
  version: "1.0"
---

# TwigComponent

Reusable UI components with PHP classes + Twig templates. Think React/Vue components, but server-rendered with zero JavaScript.

Two flavors exist: **class components** (PHP class + Twig template) for components that need logic, services, or computed properties, and **anonymous components** (Twig-only, no PHP class) for simple presentational elements.

## When to Use TwigComponent

Use TwigComponent when you need reusable markup with props but no server re-rendering after the initial render. If the component needs to react to user input (re-render via AJAX, data binding, actions), use LiveComponent instead.

Good candidates: buttons, alerts, cards, badges, icons, form widgets, layout sections, navigation items, table rows, modals (structure only).

## Installation

```bash
composer require symfony/ux-twig-component
```

## Class Component

A PHP class annotated with `#[AsTwigComponent]` paired with a Twig template.

```php
// src/Twig/Components/Alert.php
namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Alert
{
    public string $type = 'info';
    public string $message;
    public bool $dismissible = false;
}
```

```twig
{# templates/components/Alert.html.twig #}
<div class="alert alert-{{ type }}" {{ attributes }}>
    {{ message }}
    {% if dismissible %}
        <button type="button" class="close">&times;</button>
    {% endif %}
</div>
```

```twig
{# Usage #}
<twig:Alert type="success" message="Saved!" />
<twig:Alert type="danger" message="Error occurred" dismissible />

{# With block content instead of message prop #}
<twig:Alert type="warning">
    <strong>Warning:</strong> Check your input
</twig:Alert>
```

## Anonymous Component (Twig Only)

No PHP class needed. Props are declared with `{% props %}` directly in the template. Use for simple presentational components with no logic.

```twig
{# templates/components/Button.html.twig #}
{% props variant = 'primary', size = 'md', disabled = false %}

<button
    class="btn btn-{{ variant }} btn-{{ size }}"
    {{ disabled ? 'disabled' }}
    {{ attributes }}
>
    {% block content %}{% endblock %}
</button>
```

```twig
<twig:Button variant="danger" size="lg">Delete</twig:Button>
```

## Props

### Public Properties (Class Components)

Public properties become props. Required props have no default value.

```php
#[AsTwigComponent]
final class Card
{
    public string $title;           // Required
    public ?string $subtitle = null; // Optional
    public bool $shadow = true;      // Optional with default
}
```

### mount() for Derived State

Use `mount()` to compute values from incoming props. The method runs once during component initialization.

```php
#[AsTwigComponent]
final class UserCard
{
    public User $user;
    public string $displayName;

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->displayName = $user->getFullName();
    }
}
```

```twig
<twig:UserCard :user="currentUser" />
```

### Dynamic Props (Colon Prefix)

Prefix a prop with `:` to pass a Twig expression instead of a string literal.

```twig
{# Pass a variable #}
<twig:Alert :type="alertType" :message="flashMessage" />

{# Pass an expression #}
<twig:UserList :users="users|filter(u => u.active)" />
```

## Blocks (Slots)

Blocks let parent templates inject content into specific areas of a component.

### Default Block

Content between component tags goes to `{% block content %}`:

```twig
{# Component template #}
<div class="card">{% block content %}{% endblock %}</div>

{# Usage #}
<twig:Card><p>This is the card content</p></twig:Card>
```

### Named Blocks

```twig
{# templates/components/Modal.html.twig #}
<dialog class="modal" {{ attributes }}>
    <header>{% block header %}Default Header{% endblock %}</header>
    <main>{% block content %}{% endblock %}</main>
    <footer>{% block footer %}{% endblock %}</footer>
</dialog>
```

```twig
<twig:Modal>
    <twig:block name="header"><h2>Confirm Action</h2></twig:block>
    <twig:block name="content"><p>Are you sure?</p></twig:block>
    <twig:block name="footer">
        <button>Cancel</button>
        <button>Confirm</button>
    </twig:block>
</twig:Modal>
```

## Computed Properties

Methods prefixed with `get` become accessible as `this.xxx` in templates. They are computed on each access (not cached across re-renders -- for caching, see LiveComponent's `computed`).

```php
#[AsTwigComponent]
final class ProductCard
{
    public Product $product;

    public function getFormattedPrice(): string
    {
        return number_format($this->product->getPrice(), 2) . ' EUR';
    }

    public function isOnSale(): bool
    {
        return $this->product->getDiscount() > 0;
    }
}
```

```twig
<div class="product">
    <span class="price">{{ this.formattedPrice }}</span>
    {% if this.onSale %}
        <span class="badge">Sale!</span>
    {% endif %}
</div>
```

## Attributes

Extra HTML attributes passed to the component are available via `{{ attributes }}`. This is how you let consumers add custom classes, ids, data attributes, etc.

```twig
{# Usage #}
<twig:Alert type="info" message="Hello" class="my-class" id="main-alert" data-controller="alert" />

{# In component template -- renders class, id, data-controller #}
<div {{ attributes }}>...</div>
```

### Attributes Methods

```twig
{# Merge with defaults #}
<div {{ attributes.defaults({class: 'alert'}) }}>

{# Exclude specific #}
<div {{ attributes.without('id', 'class') }}>

{# Only render specific #}
<div id="{{ attributes.render('id') }}">

{# Check existence #}
{% if attributes.has('disabled') %}
```

## Components as Services

Components are Symfony services -- autowiring works naturally. Use the constructor for dependencies, public properties for props.

```php
#[AsTwigComponent]
final class FeaturedProducts
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {}

    public function getProducts(): array
    {
        return $this->products->findFeatured(limit: 6);
    }
}
```

```twig
{# templates/components/FeaturedProducts.html.twig #}
<div class="featured-products">
    {% for product in this.products %}
        <twig:ProductCard :product="product" />
    {% endfor %}
</div>
```

```twig
{# Usage -- no props needed, data comes from service #}
<twig:FeaturedProducts />
```

## Lifecycle Hooks

```php
use Symfony\UX\TwigComponent\Attribute\PreMount;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent]
final class DataTable
{
    public array $data;
    public string $sortBy = 'id';

    #[PreMount]
    public function preMount(array $data): array
    {
        // Modify/validate incoming data before property assignment
        $data['sortBy'] ??= 'id';
        return $data;
    }

    #[PostMount]
    public function postMount(): void
    {
        // Runs after all props are set
        $this->data = $this->sortData($this->data);
    }
}
```

## Nested Components

Components compose naturally -- nest them like HTML elements:

```twig
<twig:Card>
    <twig:block name="header">
        <twig:Icon name="star" /> Featured
    </twig:block>
    <twig:block name="content">
        <twig:ProductList :products="featuredProducts">
            <twig:block name="empty">
                <twig:Alert type="info" message="No products found" />
            </twig:block>
        </twig:ProductList>
    </twig:block>
</twig:Card>
```

## Configuration

```yaml
# config/packages/twig_component.yaml
twig_component:
    anonymous_template_directory: 'components/'
    defaults:
        App\Twig\Components\: 'components/'
```

## HTML vs Twig Syntax

```twig
{# HTML syntax (recommended -- better IDE support, more readable) #}
<twig:Alert type="success" message="Done!" />

{# Twig syntax (alternative -- useful in edge cases) #}
{% component 'Alert' with {type: 'success', message: 'Done!'} %}
{% endcomponent %}
```

Prefer HTML syntax (`<twig:...>`) in all cases. The Twig syntax (`{% component %}`) is legacy and less readable.

## CVE-2025-47946 -- Attribute Injection

TwigComponent had a security vulnerability (CVE-2025-47946) related to unsanitized HTML attribute injection via `ComponentAttributes`. Make sure you are on a patched version (check the Symfony security advisories). The `{{ attributes }}` helper now properly escapes values.

## References

- **Full API** (attribute options, hooks, configuration, all methods): [references/api.md](references/api.md)
- **Patterns** (forms, tables, layouts, composition, real-world examples): [references/patterns.md](references/patterns.md)
