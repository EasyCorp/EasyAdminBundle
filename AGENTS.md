# AI Contribution Guidelines

Welcome, AI assistant. Please follow these guidelines when contributing to this repository.

## Project Overview

EasyAdminBundle is a third-party Symfony bundle for creating admin backends. It provides CRUD controllers, dashboard management, and extensive field/filter configuration.

**Requirements:** PHP 8.2+, Symfony 6.4/7.x/8.x, Doctrine ORM 2.20+ or 3.6+

## General Rules

- Language: American English for code, comments, commits, branches
- Code quotes: wrap strings with single quotes in PHP, CSS, JavaScript
- Text quotes: straight quotes only (`'` and `"`, no typographic)
- Security: prevent XSS, CSRF, injections, auth bypass, open redirects

### Do Not Edit
- `vendor/` - managed by Composer
- `node_modules/` - managed by Yarn
- `var/` - Symfony cache/logs
- `public/bundles/` - generated assets
- `composer.lock`, `yarn.lock` - update only via package manager commands

## Architecture

### Key Patterns
- **Factory**: ActionFactory, EntityFactory, FieldFactory, FilterFactory, FormFactory
- **Configurator Chain**: FieldConfiguratorInterface, FilterConfiguratorInterface
- **Context Facade**: AdminContext wraps Request/Crud/Dashboard/I18n contexts
- **DTO Layer**: Type-safe data transfer (ActionDto, EntityDto, FieldDto, etc.)
- **Event Subscriber**: AdminRouterSubscriber, CrudAutocompleteSubscriber
- **Registry**: AdminControllerRegistry, TemplateRegistry
- **Provider**: AdminContextProvider, FieldProvider
- **Typed Collections**: FieldCollection, ActionCollection, FilterCollection
- **Argument Resolver**: AdminContextResolver, BatchActionDtoResolver

Don't modify the public API in `Contracts/` lightly — it's a stable, versioned interface that third-party code depends on.

### Design Principles
- Never surface internal complexity to end users. Keep the public API small, simple, and consistent; push complexity down into internal classes (factories, configurators, DTOs), not onto the people configuring a backend.
- Public APIs are stable contracts: prefer additive, backward-compatible changes and don't leak internal types or implementation details through them.

### Backward Compatibility
Follow [Symfony's BC policy](https://symfony.com/bc): never break public API — deprecate first, keep the old path working, remove only in the next major. Emit deprecations with `@trigger_deprecation('easycorp/easyadmin-bundle', '<version>', '<message>')`, naming the replacement and removal version.

### Configuration Model
User-facing config uses fluent **builders** (`Config/`, `Field/`): static `new()` + chainable setters returning `self`. Each builder converts via `getAsDto()` into an immutable **DTO** (`Dto/`) for internal use. New configurable concepts follow this split — fluent builder for users, DTO internally; never expose a DTO as the config surface.

### Main Namespace
`EasyCorp\Bundle\EasyAdminBundle\`

## Commands

The `Makefile` is the source of truth for build/lint/test commands; its targets match what CI runs. Prefer them over hand-typed invocations. Run `make help` to list every target.

### Setup
```bash
make build          # composer update + yarn install (first-time setup)
composer install    # PHP dependencies only
yarn install        # JS dependencies only
```

### Before Creating a PR
Run the full check suite (linters + tests, identical to CI):
```bash
make checks-before-pr
```

### Pre-Commit Checklist
Run the relevant target(s) for what you changed:

| Changed          | Run                                                               |
|------------------|-------------------------------------------------------------------|
| PHP code         | `make linter-phpstan`, `make linter-cs-fixer`, `make tests`       |
| JS / CSS         | `yarn ci`, then `make build-assets`                               |
| Twig templates   | `make linter-twig`                                                |
| Documentation    | `make linter-docs`                                                |
| Translations     | keep all locales consistent; use English as placeholder if unsure |

Run targeted tests with `make tests ARGS=...` (keeps the deprecation baseline and cache reset; full guide in `tests/AGENTS.md`):
```bash
make tests                            # whole suite
make tests ARGS="tests/Unit/Field/"   # one directory
make tests ARGS="--filter=testFoo"    # one test
```
A bare `./vendor/bin/simple-phpunit` run skips the deprecation baseline (`tests/baseline-ignore.txt`) and the cache reset.

## Git and Pull Requests

### Commit Messages
- Use imperative mood: "Add feature" not "Added feature"
- First line: concise summary (50 chars max)
- Reference issues when applicable: "Fix #123"
- No period at end of subject line

### Branch Naming
- Feature: `<short description>` (e.g., `add_new_field_type`)
- Bug fix: `fix_<issue number>` (e.g., `fix_123`)
- Use lowercase with underscores

## PHP Code Standards

php-cs-fixer (`@Symfony` + `@Symfony:risky`) auto-formats most style — trailing commas, braces, strict comparisons, Yoda conditions, blank lines, quotes, etc. Run `make linter-cs-fixer` and let it handle formatting. The rules below are the conventions it does **not** enforce.

### Conventions
- PHP 8.2+ syntax with constructor property promotion
- Don't add `declare(strict_types=1);` to PHP files
- No service autowiring — configure explicitly in `config/services.php`
- Use enums (`UpperCamelCase` case names) instead of constants for fixed sets of values
- Prefer project constants (`Action::EDIT`, `EA::QUERY`) over hardcoded strings
- Avoid `else`/`elseif` after `return`/`throw` — return/throw early instead
- `return null;` for nullable, `return;` for void
- Use `sprintf()` for exception messages with `get_debug_type()` for class names
- Exception/error messages: start capital, end with a period, no backticks; concise but actionable (include class names, file paths)
- Handle exceptions explicitly (no silent catches)
- Comments: only for complex/unintuitive code, lowercase start, no period end; never as section separators (e.g. `// === Methods for dashboards ===`)
- Config files in PHP format (`config/services.php`, `translations/*.php`)

### Naming
- Variables/methods: `camelCase`
- Config/routes/Twig: `snake_case`
- Constants: `SCREAMING_SNAKE_CASE`
- Classes: `UpperCamelCase`
- Abstract classes: `Abstract*` (except test cases)
- Interfaces: `*Interface`, Traits: `*Trait`, Exceptions: `*Exception`
- Most classes add a suffix showing their type:
  `*Controller`, `*Configurator`, `*Context`, `*Dto`, `*Event`,
  `*Field`, `*Filter`, `*Subscriber`, `*Type`, `*Test`
- Templates/assets: `snake_case` (e.g., `detail_page.html.twig`)

### Class Organization
1. Properties before methods
2. Constructor first, then public, protected, private methods in that order

### PHPDoc
- No single-line docblocks
- No `@return` for void methods
- Group annotations by type

## Templates (Twig)

twig-cs-fixer handles formatting (`make linter-twig`). Conventions it doesn't enforce:

- Modern HTML5 and Twig syntax
- Icons: FontAwesome 6.x names
- All user-facing text via `|trans` filter (no hardcoded strings)
- Keep translation logic in templates, not PHP (use `TranslatableInterface`)
- Reuse components from `templates/components/` when available — see `src/Twig/Component/AGENTS.md` before adding or changing a component
- Accessibility: `aria-*` attributes, semantic tags, labels

## JavaScript

biome handles formatting (`yarn ci`). Conventions it doesn't enforce:

- ES6+ syntax
- `camelCase` for variables and functions

## CSS

- Standard CSS only (no SCSS/LESS)
- Don't use nested rules — keep selectors flat
- `kebab-case` for class names
- Bootstrap 5.3 classes and utilities
- Logical properties: `margin-block-end` instead of `margin-bottom`
- Responsive design required; use only these Bootstrap breakpoints:
  - Medium (md): ≥768px
  - Large (lg): ≥992px
  - Extra large (xl): ≥1200px

## Documentation (doc/)

- Format: reStructuredText (.rst)
- Heading symbols: `=`, `-`, `~`, `.`, `"` for levels 1-5
- Line length: 72-78 characters
- Code blocks: prefer `::` over `.. code-block:: php`
- Separate link text from URLs (no inline hyperlinks)
- Show config in order: YAML, XML, PHP (or Attributes)
- Code line limit: 85 chars (use `...` for folded code)
- Include `use` statements for referenced classes
- Bash lines prefixed with `$`
- Root directory: `your-project/`
- Vendor name: `Acme`
- URLs: `example.com`, `example.org`, `example.net`
- Trailing slashes for directories, leading dots for extensions

### Writing Style
- American English, second person (you)
- Gender-neutral (they/them)
- Use contractions (it's, don't, you're)
- Avoid: "just", "obviously", "easy", "simply"
- Realistic examples (no foo/bar placeholders)
- Write for non-native English speakers: use simple vocabulary, avoid idioms, and complex sentence structures

## Agent Skill (skills/easyadmin/)

The `skills/easyadmin/` directory ships an agent skill for AI coding assistants
inside the Composer package. It describes the public API and the rules that keep
generated code working on the current version, so it changes together with the code:

- After changing a public method, constant or attribute in `src/Config/`,
  `src/Field/`, `src/Filter/`, `src/Attribute/`, `src/Test/`, `src/Controller/Abstract*`
  or `src/Router/AdminUrlGenerator.php`, run `make skill-api-reference` and commit
  the regenerated `skills/easyadmin/references/api.md` (a unit test fails otherwise)
- After adding an entry to `UPGRADE.md`, review the "Legacy" table and the rules of
  `skills/easyadmin/SKILL.md` that the change contradicts
- Every bullet inside a `<!-- rules:start -->` / `<!-- rules:end -->` block ends
  with a provenance comment (`<!-- path::symbol; path:line -->`) that a unit test
  resolves against the source; a rule without provenance is not merged
- Rules that describe behavior (not only the existence of a method) need an anchor:
  a `source_contains` line in the `<!-- skill-check -->` trailer or a functional
  test that names the rule in its docblock
- The skill documents only what this package provides
- `make tests ARGS="tests/Unit/Skill/"` runs every skill check; the manual
  evaluation in `tests/Unit/Skill/eval/README.md` runs before each release
