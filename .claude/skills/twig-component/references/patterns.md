# TwigComponent Patterns

Common patterns for building reusable components.

---

## Alert Component

```php
#[AsTwigComponent]
final class Alert
{
    public string $type = 'info';
    public ?string $title = null;
    public bool $dismissible = false;
    
    public function getIcon(): string
    {
        return match ($this->type) {
            'success' => 'check-circle',
            'danger', 'error' => 'x-circle',
            'warning' => 'alert-triangle',
            default => 'info',
        };
    }
}
```

```twig
{# templates/components/Alert.html.twig #}
<div 
    class="alert alert-{{ type }}"
    role="alert"
    {{ attributes }}
>
    <twig:Icon :name="this.icon" />
    
    {% if title %}
        <strong>{{ title }}</strong>
    {% endif %}
    
    <div class="alert-content">
        {% block content %}{% endblock %}
    </div>
    
    {% if dismissible %}
        <button type="button" class="close" data-action="click->alert#dismiss">
            &times;
        </button>
    {% endif %}
</div>
```

---

## Button Component

```twig
{# templates/components/Button.html.twig (anonymous) #}
{% props 
    variant = 'primary',
    size = 'md',
    type = 'button',
    href = null,
    disabled = false,
    loading = false,
    icon = null,
    iconPosition = 'left'
%}

{% set tag = href ? 'a' : 'button' %}
{% set classes = 'btn btn-' ~ variant ~ ' btn-' ~ size %}

<{{ tag }}
    {% if href %}href="{{ href }}"{% endif %}
    {% if not href %}type="{{ type }}"{% endif %}
    class="{{ classes }}"
    {{ disabled or loading ? 'disabled' }}
    {{ attributes }}
>
    {% if loading %}
        <span class="spinner"></span>
    {% elseif icon and iconPosition == 'left' %}
        <twig:Icon :name="icon" />
    {% endif %}
    
    {% block content %}{% endblock %}
    
    {% if icon and iconPosition == 'right' and not loading %}
        <twig:Icon :name="icon" />
    {% endif %}
</{{ tag }}>
```

---

## Card Component

```php
#[AsTwigComponent]
final class Card
{
    public ?string $title = null;
    public ?string $subtitle = null;
    public ?string $image = null;
    public bool $shadow = true;
    public bool $hover = false;
}
```

```twig
{# templates/components/Card.html.twig #}
<article 
    class="card {{ shadow ? 'shadow' }} {{ hover ? 'card-hover' }}"
    {{ attributes }}
>
    {% if image %}
        <img src="{{ image }}" class="card-image" alt="">
    {% endif %}
    
    {% block header %}
        {% if title or subtitle %}
            <header class="card-header">
                {% if title %}<h3 class="card-title">{{ title }}</h3>{% endif %}
                {% if subtitle %}<p class="card-subtitle">{{ subtitle }}</p>{% endif %}
            </header>
        {% endif %}
    {% endblock %}
    
    <div class="card-body">
        {% block content %}{% endblock %}
    </div>
    
    {% if block('footer') is not empty %}
        <footer class="card-footer">
            {% block footer %}{% endblock %}
        </footer>
    {% endif %}
</article>
```

---

## Modal Component

```php
#[AsTwigComponent]
final class Modal
{
    public string $id;
    public ?string $title = null;
    public string $size = 'md';
    public bool $closeButton = true;
    public bool $backdrop = true;
}
```

```twig
{# templates/components/Modal.html.twig #}
<dialog 
    id="{{ id }}"
    class="modal modal-{{ size }}"
    {{ not backdrop ? 'data-no-backdrop' }}
    {{ attributes }}
>
    <div class="modal-content">
        <header class="modal-header">
            {% block header %}
                {% if title %}
                    <h2 class="modal-title">{{ title }}</h2>
                {% endif %}
            {% endblock %}
            
            {% if closeButton %}
                <button type="button" class="modal-close" data-action="click->modal#close">
                    &times;
                </button>
            {% endif %}
        </header>
        
        <div class="modal-body">
            {% block content %}{% endblock %}
        </div>
        
        {% if block('footer') is not empty %}
            <footer class="modal-footer">
                {% block footer %}{% endblock %}
            </footer>
        {% endif %}
    </div>
</dialog>
```

---

## Form Field Component

```twig
{# templates/components/FormField.html.twig (anonymous) #}
{% props 
    name,
    label = null,
    type = 'text',
    value = '',
    placeholder = '',
    required = false,
    error = null,
    help = null
%}

<div class="form-field {{ error ? 'has-error' }}" {{ attributes }}>
    {% if label %}
        <label for="{{ name }}" class="form-label">
            {{ label }}
            {% if required %}<span class="required">*</span>{% endif %}
        </label>
    {% endif %}
    
    {% block input %}
        <input 
            type="{{ type }}"
            id="{{ name }}"
            name="{{ name }}"
            value="{{ value }}"
            placeholder="{{ placeholder }}"
            {{ required ? 'required' }}
            class="form-input"
        >
    {% endblock %}
    
    {% if error %}
        <p class="form-error">{{ error }}</p>
    {% elseif help %}
        <p class="form-help">{{ help }}</p>
    {% endif %}
</div>
```

---

## Table Component

```php
#[AsTwigComponent]
final class DataTable
{
    public array $columns;
    public array $rows;
    public ?string $emptyMessage = 'No data available';
    public bool $striped = true;
    public bool $hoverable = true;
    
    public function isEmpty(): bool
    {
        return count($this->rows) === 0;
    }
}
```

```twig
{# templates/components/DataTable.html.twig #}
<div class="table-container" {{ attributes }}>
    <table class="table {{ striped ? 'table-striped' }} {{ hoverable ? 'table-hover' }}">
        <thead>
            <tr>
                {% for column in columns %}
                    <th>{{ column.label ?? column }}</th>
                {% endfor %}
                {% block extra_headers %}{% endblock %}
            </tr>
        </thead>
        <tbody>
            {% if this.empty %}
                <tr>
                    <td colspan="{{ columns|length }}">
                        {% block empty %}
                            {{ emptyMessage }}
                        {% endblock %}
                    </td>
                </tr>
            {% else %}
                {% for row in rows %}
                    <tr>
                        {% for column in columns %}
                            <td>{{ attribute(row, column.key ?? column) }}</td>
                        {% endfor %}
                        {% block row_actions %}{% endblock %}
                    </tr>
                {% endfor %}
            {% endif %}
        </tbody>
    </table>
</div>
```

---

## Dropdown Component

```twig
{# templates/components/Dropdown.html.twig (anonymous) #}
{% props 
    label,
    align = 'left',
    variant = 'default'
%}

<div 
    class="dropdown"
    data-controller="dropdown"
    {{ attributes }}
>
    <button 
        type="button"
        class="dropdown-toggle btn btn-{{ variant }}"
        data-action="click->dropdown#toggle"
    >
        {{ label }}
        <span class="caret"></span>
    </button>
    
    <div class="dropdown-menu dropdown-{{ align }}" data-dropdown-target="menu" hidden>
        {% block content %}{% endblock %}
    </div>
</div>
```

---

## Pagination Component

```php
#[AsTwigComponent]
final class Pagination
{
    public int $currentPage;
    public int $totalPages;
    public int $visiblePages = 5;
    public string $routeName;
    public array $routeParams = [];
    
    public function getPages(): array
    {
        $start = max(1, $this->currentPage - floor($this->visiblePages / 2));
        $end = min($this->totalPages, $start + $this->visiblePages - 1);
        $start = max(1, $end - $this->visiblePages + 1);
        
        return range($start, $end);
    }
    
    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }
    
    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }
}
```

```twig
{# templates/components/Pagination.html.twig #}
{% if totalPages > 1 %}
<nav class="pagination" {{ attributes }}>
    <a 
        href="{{ this.hasPrevious ? path(routeName, routeParams|merge({page: currentPage - 1})) : '#' }}"
        class="pagination-prev {{ not this.hasPrevious ? 'disabled' }}"
    >
        Previous
    </a>
    
    {% for page in this.pages %}
        <a 
            href="{{ path(routeName, routeParams|merge({page: page})) }}"
            class="pagination-page {{ page == currentPage ? 'active' }}"
        >
            {{ page }}
        </a>
    {% endfor %}
    
    <a 
        href="{{ this.hasNext ? path(routeName, routeParams|merge({page: currentPage + 1})) : '#' }}"
        class="pagination-next {{ not this.hasNext ? 'disabled' }}"
    >
        Next
    </a>
</nav>
{% endif %}
```

---

## Tabs Component

```twig
{# templates/components/Tabs.html.twig (anonymous) #}
{% props tabs, activeTab = null %}

{% set activeTab = activeTab ?? tabs|keys|first %}

<div class="tabs" data-controller="tabs" {{ attributes }}>
    <div class="tabs-nav" role="tablist">
        {% for key, tab in tabs %}
            <button 
                type="button"
                role="tab"
                class="tab-button {{ key == activeTab ? 'active' }}"
                data-tabs-target="tab"
                data-action="click->tabs#select"
                data-key="{{ key }}"
            >
                {{ tab.label ?? key }}
            </button>
        {% endfor %}
    </div>
    
    <div class="tabs-content">
        {% for key, tab in tabs %}
            <div 
                role="tabpanel"
                class="tab-panel"
                data-tabs-target="panel"
                {{ key != activeTab ? 'hidden' }}
            >
                {% block tab_content %}
                    {{ tab.content|raw }}
                {% endblock %}
            </div>
        {% endfor %}
    </div>
</div>
```

---

## Icon Component

```twig
{# templates/components/Icon.html.twig (anonymous) #}
{% props name, size = 'md', class = '' %}

<svg 
    class="icon icon-{{ size }} {{ class }}"
    {{ attributes.without('class') }}
>
    <use href="#icon-{{ name }}"></use>
</svg>
```

---

## Avatar Component

```twig
{# templates/components/Avatar.html.twig (anonymous) #}
{% props 
    src = null,
    alt = '',
    initials = null,
    size = 'md'
%}

<div class="avatar avatar-{{ size }}" {{ attributes }}>
    {% if src %}
        <img src="{{ src }}" alt="{{ alt }}" class="avatar-image">
    {% elseif initials %}
        <span class="avatar-initials">{{ initials }}</span>
    {% else %}
        <span class="avatar-placeholder">
            <twig:Icon name="user" />
        </span>
    {% endif %}
</div>
```

---

## Composition Example

Combining multiple components:

```twig
<twig:Card title="Team Members" shadow>
    <twig:block name="header">
        <div class="flex justify-between">
            <h3>Team Members</h3>
            <twig:Button variant="primary" size="sm" icon="plus">
                Add Member
            </twig:Button>
        </div>
    </twig:block>
    
    <twig:DataTable 
        :columns="['Name', 'Role', 'Status']"
        :rows="members"
    >
        <twig:block name="empty">
            <twig:Alert type="info">
                No team members yet. Add your first member!
            </twig:Alert>
        </twig:block>
        
        <twig:block name="row_actions">
            <td>
                <twig:Dropdown label="Actions" variant="ghost">
                    <a href="#">Edit</a>
                    <a href="#" class="text-danger">Remove</a>
                </twig:Dropdown>
            </td>
        </twig:block>
    </twig:DataTable>
    
    <twig:block name="footer">
        <twig:Pagination 
            :currentPage="page"
            :totalPages="totalPages"
            routeName="team_members"
        />
    </twig:block>
</twig:Card>
```
