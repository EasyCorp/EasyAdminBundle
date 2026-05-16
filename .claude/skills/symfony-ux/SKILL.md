---
name: symfony-ux
description: Symfony UX frontend stack combining Stimulus, Turbo, TwigComponent and LiveComponent. Use when building modern Symfony frontends, choosing between UX tools, creating interactive components, handling real-time updates, or integrating multiple UX packages. Triggers - symfony ux, hotwire symfony, stimulus turbo, live component, twig component, frontend symfony, interactive ui, real-time symfony, which ux package, which tool should I use, how to make this interactive, SPA feel, reactive component, server-rendered component. Also trigger when the user asks a general question about frontend architecture in Symfony or wants to combine multiple UX packages together.
license: MIT
metadata:
  author: Simon Andre
  email: smn.andre@gmail.com
  url: https://smnandre.dev
  version: "1.0"
---

# Symfony UX

Modern frontend stack for Symfony. Build reactive UIs with minimal JavaScript using server-rendered HTML.

Symfony UX follows a progressive enhancement philosophy: start with plain HTML, add interactivity only where needed, and prefer server-side rendering over client-side JavaScript. Each tool solves a specific problem -- pick the simplest one that fits.

## Decision Tree: Which Tool?

```
Need frontend interactivity?
|
+-- Pure JavaScript behavior (no server)?
|   -> Stimulus
|      (DOM manipulation, event handling, third-party libs)
|
+-- Navigation / partial page updates?
|   -> Turbo
|      +-- Full page AJAX        -> Turbo Drive (automatic, zero config)
|      +-- Single section update  -> Turbo Frame
|      +-- Multiple sections      -> Turbo Stream
|
+-- Reusable UI component?
|   |
|   +-- Static (no live updates)?
|   |   -> TwigComponent
|   |      (props, blocks, computed properties)
|   |
|   +-- Dynamic (re-renders on interaction)?
|       -> LiveComponent
|          (data binding, actions, forms, real-time validation)
|
+-- Real-time (WebSocket/SSE)?
    -> Turbo Stream + Mercure
```

The tools compose naturally. A typical page uses Turbo Drive for navigation, Turbo Frames for partial sections, TwigComponents for reusable UI elements, LiveComponents for reactive forms/search, and Stimulus for client-side behavior that doesn't need a server round-trip.

## Quick Comparison

| Feature | Stimulus | Turbo | TwigComponent | LiveComponent |
|---------|----------|-------|---------------|---------------|
| JavaScript required | Yes (minimal) | No | No | No |
| Server re-render | No | Yes (page/frame) | No | Yes (AJAX) |
| State management | JS only | URL/Server | Props (immutable) | LiveProp (mutable) |
| Two-way binding | Manual | No | No | data-model |
| Real-time capable | Manual | Yes (Streams+Mercure) | No | Yes (polling/emit) |
| Lazy loading | Yes (stimulusFetch) | Yes (lazy frames) | No | Yes (defer/lazy) |

## Installation

```bash
# All core packages
composer require symfony/ux-turbo symfony/stimulus-bundle \
    symfony/ux-twig-component symfony/ux-live-component

# Individual
composer require symfony/stimulus-bundle      # Stimulus
composer require symfony/ux-turbo             # Turbo
composer require symfony/ux-twig-component    # TwigComponent
composer require symfony/ux-live-component    # LiveComponent (includes TwigComponent)
```

## Common Patterns

### Pattern 1: Static Component (TwigComponent)

Reusable UI with no interactivity. Use for buttons, cards, alerts, badges.

```php
#[AsTwigComponent]
final class Alert
{
    public string $type = 'info';
    public string $message;
}
```

```twig
{# templates/components/Alert.html.twig #}
<div class="alert alert-{{ type }}" {{ attributes }}>
    {{ message }}
</div>
```

```twig
<twig:Alert type="success" message="Saved!" />
```

### Pattern 2: Component + JS Behavior (TwigComponent + Stimulus)

Server-rendered component with client-side interactivity. Use when the interaction is purely cosmetic (toggling, animations, third-party JS libs) and doesn't need server data.

```php
#[AsTwigComponent]
final class Dropdown
{
    public string $label;
}
```

```twig
{# templates/components/Dropdown.html.twig #}
<div data-controller="dropdown" {{ attributes }}>
    <button data-action="click->dropdown#toggle">{{ label }}</button>
    <div data-dropdown-target="menu" hidden>
        {% block content %}{% endblock %}
    </div>
</div>
```

### Pattern 3: Server-Reactive Component (LiveComponent)

Component that re-renders via AJAX on user input. Use for search boxes, filters, forms with real-time validation, anything that needs server data on every interaction.

```php
#[AsLiveComponent]
final class SearchBox
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true)]
    public string $query = '';

    public function __construct(
        private readonly ProductRepository $products,
    ) {}

    public function getResults(): array
    {
        return $this->products->search($this->query);
    }
}
```

```twig
<div {{ attributes }}>
    <input data-model="debounce(300)|query" placeholder="Search...">
    <div data-loading="addClass(opacity-50)">
        {% for item in this.results %}
            <div>{{ item.name }}</div>
        {% endfor %}
    </div>
</div>
```

### Pattern 4: Frame-Based Navigation (Turbo Frame)

Partial page updates without full reload. Use for pagination, inline editing, tabbed content, modals loaded from server.

```twig
<turbo-frame id="product-list">
    {% for product in products %}
        <a href="{{ path('product_show', {id: product.id}) }}">
            {{ product.name }}
        </a>
    {% endfor %}
</turbo-frame>
```

### Pattern 5: Multi-Section Update (Turbo Stream)

Update multiple page areas from a single server response. Use after form submissions that affect several parts of the page.

```php
#[Route('/comments', methods: ['POST'])]
public function create(Request $request): Response
{
    // ... save comment

    $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
    return $this->render('comment/create.stream.html.twig', [
        'comment' => $comment,
        'count' => $count,
    ]);
}
```

```twig
{# create.stream.html.twig #}
<turbo-stream action="append" target="comments">
    <template>{{ include('comment/_comment.html.twig') }}</template>
</turbo-stream>
<turbo-stream action="update" target="comment-count">
    <template>{{ count }}</template>
</turbo-stream>
```

You can also use the Twig component syntax:

```twig
<twig:Turbo:Stream:Append target="comments">
    {{ include('comment/_comment.html.twig') }}
</twig:Turbo:Stream:Append>
```

### Pattern 6: LiveComponent Inside Turbo Frame

Combine for complex UIs -- the frame scopes navigation, the LiveComponent handles reactivity within that scope.

```twig
<turbo-frame id="search-section">
    <twig:ProductSearch />
</turbo-frame>
```

### Pattern 7: Real-Time Updates (Mercure + Turbo Stream)

Broadcast server-side events to all connected browsers via SSE.

```php
use Symfony\UX\Turbo\Attribute\Broadcast;

#[Broadcast]
class Message
{
    // Entity changes broadcast automatically
}
```

```twig
<turbo-stream-source src="{{ mercure('chat')|escape('html_attr') }}">
</turbo-stream-source>
<div id="messages">...</div>
```

## When to Use What

**Stimulus** -- Adding JS behavior to existing HTML, integrating third-party libraries (charts, datepickers, maps), client-only interactions (toggles, tabs, clipboard), anything where you need full control over JavaScript execution.

**Turbo Drive** -- SPA-like navigation. Automatic, zero config. Just install and all links/forms become AJAX. Opt out selectively with `data-turbo="false"`.

**Turbo Frames** -- Loading or updating a single page section: inline editing, pagination within a section, modal content loading, lazy-loaded sidebar.

**Turbo Streams** -- Updating multiple page sections at once, real-time broadcasts (with Mercure), flash messages after form submit, delete confirmations that update a list and a counter.

**TwigComponent** -- Reusable UI elements (buttons, cards, alerts, form widgets), consistent styling and markup, no server interaction needed after initial render, component composition and nesting.

**LiveComponent** -- Forms with real-time validation, search with live results, data binding (like Vue/React but server-rendered), any component whose state changes based on user interaction, when you want to avoid writing JavaScript entirely.

## Combining Tools

```
+-----------------------------------------------------+
|                     Page                             |
|  +------------------------------------------------+  |
|  | Turbo Drive (automatic full-page AJAX)          |  |
|  |  +------------------------------------------+  |  |
|  |  | Turbo Frame (partial section)             |  |  |
|  |  |  +------------------------------------+  |  |  |
|  |  |  | LiveComponent (reactive)            |  |  |  |
|  |  |  |  +------------------------------+  |  |  |  |
|  |  |  |  | TwigComponent (static)        |  |  |  |  |
|  |  |  |  |  + Stimulus (JS behavior)     |  |  |  |  |
|  |  |  |  +------------------------------+  |  |  |  |
|  |  |  +------------------------------------+  |  |  |
|  |  +------------------------------------------+  |  |
|  +------------------------------------------------+  |
+-----------------------------------------------------+
```

## File Structure

```
src/
  Twig/
    Components/
      Alert.php              # TwigComponent
      Button.php             # TwigComponent
      SearchBox.php          # LiveComponent
      ProductForm.php        # LiveComponent

templates/
  components/
    Alert.html.twig
    Button.html.twig
    SearchBox.html.twig
    ProductForm.html.twig

assets/
  controllers/
    dropdown_controller.js   # Stimulus
    modal_controller.js      # Stimulus
    chart_controller.js      # Stimulus
```

## Anti-Patterns to Avoid

**Don't use LiveComponent for static content.** If a component never re-renders after initial load, use TwigComponent instead -- LiveComponent adds unnecessary overhead (AJAX requests, state serialization).

**Don't use Turbo Streams when a Frame is enough.** If you're only updating one section of the page, a Turbo Frame is simpler and requires no special response format.

**Don't reach for Stimulus when Turbo handles it.** Before writing a Stimulus controller for a link or form interaction, check if Turbo Drive/Frames already handle it.

**Don't fight Turbo Drive.** If a link or form behaves oddly with Turbo, the fix is usually to ensure the server returns a proper full HTML page, not to disable Turbo.

## Related Skills

For detailed documentation on each tool, read the dedicated skill:
- **Stimulus**: Controllers, targets, values, actions, outlets, lazy loading
- **Turbo**: Drive, Frames, Streams, Mercure integration, `<twig:Turbo:Stream:*>` components
- **TwigComponent**: Props, blocks, computed properties, anonymous components, attributes
- **LiveComponent**: LiveProp, LiveAction, data-model, forms, emit/listen, polling, defer/lazy
