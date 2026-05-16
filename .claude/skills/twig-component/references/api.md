# TwigComponent API Reference

## AsTwigComponent Attribute

```php
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
    name: 'Alert',                              // Optional, defaults to class name
    template: 'components/Alert.html.twig',    // Optional, defaults to components/{Name}.html.twig
    exposePublicProps: true,                   // Expose public props directly in template
)]
final class Alert
{
    // ...
}
```

---

## Props

### Public Properties

```php
#[AsTwigComponent]
final class Button
{
    public string $label;                    // Required (no default)
    public string $variant = 'primary';      // Optional with default
    public ?string $icon = null;             // Nullable
    public bool $disabled = false;           // Boolean
    public array $options = [];              // Array
}
```

### Anonymous Props

```twig
{# templates/components/Button.html.twig #}
{% props 
    label,
    variant = 'primary',
    icon = null,
    disabled = false
%}
```

### Accessing Props in Template

```twig
{# Direct access (exposePublicProps: true, default) #}
{{ label }}
{{ variant }}

{# Via this #}
{{ this.label }}
{{ this.variant }}
```

---

## mount() Method

Called when component is created. Receives all passed props.

```php
#[AsTwigComponent]
final class UserAvatar
{
    public User $user;
    public int $size;
    public string $initials;
    
    public function mount(User $user, int $size = 40): void
    {
        $this->user = $user;
        $this->size = $size;
        $this->initials = $this->computeInitials($user);
    }
    
    private function computeInitials(User $user): string
    {
        // ...
    }
}
```

---

## Lifecycle Hooks

### PreMount

Modify data before mounting:

```php
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent]
final class Table
{
    #[PreMount]
    public function preMount(array $data): array
    {
        // Normalize incoming data
        $data['page'] = max(1, $data['page'] ?? 1);
        return $data;
    }
}
```

With priority (higher = earlier):

```php
#[PreMount(priority: 10)]
public function first(array $data): array { }

#[PreMount(priority: 0)]
public function second(array $data): array { }
```

### PostMount

Run code after all props are set:

```php
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent]
final class Chart
{
    public array $data;
    private array $processedData;
    
    #[PostMount]
    public function postMount(): void
    {
        $this->processedData = $this->processData($this->data);
    }
}
```

---

## Computed Properties

Methods starting with `get` or `is`/`has` become template properties:

```php
#[AsTwigComponent]
final class Invoice
{
    public array $items;
    
    // Access via: this.total
    public function getTotal(): float
    {
        return array_sum(array_column($this->items, 'price'));
    }
    
    // Access via: this.empty
    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }
    
    // Access via: this.items (boolean check)
    public function hasItems(): bool
    {
        return !$this->isEmpty();
    }
}
```

```twig
{% if this.hasItems %}
    Total: {{ this.total }}
{% else %}
    No items
{% endif %}
```

---

## ExposeInTemplate

Expose private properties or rename public ones:

```php
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
final class Stats
{
    #[ExposeInTemplate]
    private int $count;
    
    #[ExposeInTemplate(name: 'total')]
    public int $totalCount;
    
    #[ExposeInTemplate(getter: 'getFormattedCount')]
    private int $rawCount;
    
    public function getFormattedCount(): string
    {
        return number_format($this->rawCount);
    }
}
```

---

## Attributes Object

### In Template

```twig
{# Render all attributes #}
<div {{ attributes }}>

{# With defaults (merged) #}
<div {{ attributes.defaults({class: 'card', role: 'article'}) }}>

{# Only render if class attribute present #}
<div class="{{ attributes.render('class')|default('default-class') }}">

{# Exclude specific attributes #}
<div {{ attributes.without('class', 'id') }}>

{# Only specific attributes #}
<div {{ attributes.only('id', 'data-*') }}>

{# Check attribute existence #}
{% if attributes.has('disabled') %}
    <span>Disabled</span>
{% endif %}
```

### Stimulus Integration

```twig
<div {{ attributes.defaults({
    'data-controller': 'my-controller',
    'data-my-controller-url-value': url
}) }}>
```

---

## Blocks

### Definition

```twig
{# templates/components/Card.html.twig #}
<div class="card">
    <header class="card-header">
        {% block header %}{% endblock %}
    </header>
    <div class="card-body">
        {% block content %}Default content{% endblock %}
    </div>
    <footer class="card-footer">
        {% block footer %}{% endblock %}
    </footer>
</div>
```

### Usage with twig:block

```twig
<twig:Card>
    <twig:block name="header">
        <h3>Title</h3>
    </twig:block>
    
    <twig:block name="content">
        <p>Body text</p>
    </twig:block>
    
    <twig:block name="footer">
        <button>Save</button>
    </twig:block>
</twig:Card>
```

### Default Block (content)

Content not in a named block goes to `content`:

```twig
<twig:Card>
    <twig:block name="header">Title</twig:block>
    
    {# This goes to content block #}
    <p>Card body content here</p>
</twig:Card>
```

### Outer Blocks

Access blocks from parent context:

```twig
{# Component template #}
{% block sidebar %}
    {% if outerBlocks.sidebar is defined %}
        {{ outerBlocks.sidebar }}
    {% else %}
        Default sidebar
    {% endif %}
{% endblock %}
```

---

## Configuration

```yaml
# config/packages/twig_component.yaml
twig_component:
    # Directory for anonymous components
    anonymous_template_directory: 'components/'
    
    # Map namespaces to template directories
    defaults:
        App\Twig\Components\: 'components/'
        App\Twig\Components\Form\: 'components/form/'
        
    # For components outside src/
    # App\Component\: '../component/'
```

### Custom Template Location

```php
#[AsTwigComponent(template: 'my/custom/path/Alert.html.twig')]
final class Alert { }
```

### Custom Component Name

```php
#[AsTwigComponent('UI:Alert')]
final class Alert { }
```

```twig
<twig:UI:Alert />
```

---

## Events

### PreRenderEvent

```php
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

class ComponentSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [PreRenderEvent::class => 'onPreRender'];
    }
    
    public function onPreRender(PreRenderEvent $event): void
    {
        $component = $event->getComponent();
        $template = $event->getTemplate();
        $variables = $event->getVariables();
        
        // Modify before render
        $event->setVariables([...$variables, 'extra' => 'data']);
    }
}
```

### PostRenderEvent

```php
use Symfony\UX\TwigComponent\Event\PostRenderEvent;

public function onPostRender(PostRenderEvent $event): void
{
    $html = $event->getRenderedString();
    $mountedComponent = $event->getMountedComponent();
}
```

---

## Debugging

```bash
# List all components
php bin/console debug:twig-component

# Show specific component
php bin/console debug:twig-component Alert
```

---

## Rendering Programmatically

```php
use Symfony\UX\TwigComponent\ComponentRendererInterface;

class SomeService
{
    public function __construct(
        private readonly ComponentRendererInterface $renderer,
    ) {}
    
    public function renderAlert(): string
    {
        return $this->renderer->createAndRender('Alert', [
            'type' => 'success',
            'message' => 'Done!',
        ]);
    }
}
```
