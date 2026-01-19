# AI Contribution Guidelines

Welcome, AI assistant. Please follow these guidelines when contributing to this repository.

## Project Overview

EasyAdminBundle is a third-party Symfony bundle for creating admin backends. It provides CRUD controllers, dashboard management, and extensive field/filter configuration.

**Requirements:** PHP 8.1+, Symfony 5.4/6.x/7.x/8.x, Doctrine ORM 2.12+

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
- **Registry**: DashboardControllerRegistry, CrudControllerRegistry, TemplateRegistry
- **Provider**: AdminContextProvider, FieldProvider
- **Typed Collections**: FieldCollection, ActionCollection, FilterCollection
- **Argument Resolver**: AdminContextResolver, BatchActionDtoResolver

### Main Namespace
`EasyCorp\Bundle\EasyAdminBundle\`

## Directory Structure

```
src/
├── ArgumentResolver/   # Controller argument resolvers
├── Config/             # Configuration classes (Crud, Dashboard, Action, etc.)
├── Context/            # AdminContext and related context classes
├── Contracts/          # Public interfaces (stable API)
├── Controller/         # AbstractCrudController, AbstractDashboardController
├── Dto/                # Data transfer objects
├── Event/              # Event classes
├── EventListener/      # Event subscribers and listeners
├── Factory/            # ActionFactory, EntityFactory, FieldFactory, etc.
├── Field/              # Field type classes (TextField, AssociationField, etc.)
├── Filter/             # Filter type classes
├── Form/               # Form types and extensions
├── Provider/           # AdminContextProvider, FieldProvider
├── Registry/           # DashboardControllerRegistry, CrudControllerRegistry
├── Router/             # Admin URL generation
├── Security/           # Permission system
├── Twig/               # Twig extensions and components
templates/              # Twig templates
tests/
├── TestApplication/    # Main test app with fixtures
├── PrettyUrlsTestApplication/  # Pretty URLs feature tests
├── AdminRouteTestApplication/  # Admin route tests
├── Controller/         # Controller tests
├── Field/              # Field tests
├── Unit/               # Unit tests
```

## Commands

### Development
```bash
composer install              # Install PHP dependencies
yarn install                  # Install JS dependencies
```

### Pre-Commit Checklist

Before submitting changes, run these commands to verify them:

If PHP code changed:
- [ ] `./vendor/bin/phpstan analyse` passes with no errors
- [ ] `php-cs-fixer fix --dry-run` shows no issues
- [ ] Run tests with:
  ```bash
  ./vendor/bin/simple-phpunit                    # All tests
  ./vendor/bin/simple-phpunit tests/Field/       # Specific directory
  ./vendor/bin/simple-phpunit --filter=testName  # Specific test
  USE_PRETTY_URLS=1 ./vendor/bin/simple-phpunit tests/Controller/PrettyUrls/ # only when working on pretty URLs feature
  ```

If JS/CSS changed:
- [ ] `yarn ci` passes with no errors
- [ ] `yarn biome check --write` shows no issues
- [ ] `make build-assets` completed successfully

If Twig templates changed:
- [ ] `./vendor/bin/twig-cs-fixer lint templates/` passes

If documentation changed:
- [ ] `make linter-docs` to validate RST syntax

If translations changed:
- [ ] all locales updated consistently; use English as placeholder if unsure

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

### Before Creating a PR
Run the full check suite:
```bash
make checks-before-pr
```

## PHP Code Standards

### Syntax and Style
- PHP 8.1+ syntax with constructor property promotion
- PSR-1, PSR-2, PSR-4, PSR-12 standards
- Yoda conditions: `if (null === $value)` (project convention)
- Strict comparisons only (`===`, `!==`)
- Braces required for all control structures
- Trailing commas in multi-line arrays
- Blank line before `return` (unless only statement in block)

### Naming
- Variables/methods: `camelCase`
- Config/routes/Twig: `snake_case`
- Constants: `SCREAMING_SNAKE_CASE`
- Classes: `UpperCamelCase`
- Abstract classes: `Abstract*` (except test cases)
- Interfaces: `*Interface`, Traits: `*Trait`, Exceptions: `*Exception`
- Most classes add a suffix showing its type:
  `*Controller`, `*Configurator`, `*Context`, `*Dto`, `*Event`,
  `*Field`, `*Filter`, `*Subscriber`, `*Type`, `*Test`
- Templates/assets: `snake_case` (e.g., `detail_page.html.twig`)

### Class Organization
1. Properties before methods
2. Constructor first, then `setUp()`/`tearDown()` in tests
3. Method order: public, protected, private

### Code Practices
- Don't add `declare(strict_types=1);` to PHP files
- Use enums (use `UpperCamelCase` for case names) instead of constants for fixed sets of values
- Avoid `else`/`elseif` after return/throw
- Use `sprintf()` for exception messages with `get_debug_type()` for class names
- Exception messages: capital letter start, period end, no backticks
- `return null;` for nullable, `return;` for void
- Always use parentheses when instantiating: `new Foo()`
- Add `void` return types on test methods
- No service autowiring - configure explicitly in `config/services.php`
- Comments: only for complex/unintuitive code, lowercase start, no period end
- Error messages: concise but precise and actionable (e.g. include class names, file paths)
- Handle exceptions explicitly (no silent catches)
- Config files in PHP format (`config/services.php`, `translations/*.php`)

### PHPDoc
- No `@return` for void methods
- No single-line docblocks
- Group annotations by type
- `null` last in union types

## Templates (Twig)

- Modern HTML5 and Twig syntax
- Icons: FontAwesome 6.x names
- All user-facing text via `|trans` filter (no hardcoded strings)
- Translation logic in templates, not PHP (use `TranslatableInterface`)
- Use components from `templates/components/` when available
- Accessibility: `aria-*` attributes, semantic tags, labels

## JavaScript

- ES6+ syntax
- 4-space indentation
- `camelCase` for variables and functions

## CSS

- Standard CSS only (no SCSS/LESS)
- 4-space indentation
- Bootstrap 5.3 classes and utilities
- Don't use nested rules
- Logical properties: `margin-block-end` instead of `margin-bottom`
- `kebab-case` for class names
- Responsive design required; use only these Bootstrap breakpoints:
  - Medium (md): ≥768px
  - Large (lg): ≥992px
  - Extra large (xl): ≥1200px

## Testing

### Test Structure
- **Unit tests**: `tests/Unit/` - isolated component tests
- **Functional tests**: `tests/Controller/`, `tests/Field/` - integration tests
- **Test applications**: Real Symfony apps in `tests/TestApplication/`

### Writing Tests
- Extend `WebTestCase` for functional tests
- Use simple names: 'Action 1', 'Field 1', not realistic data
- Add `void` return type to all test methods
- Name tests descriptively without `test` prefix duplication

### Test Fixtures
- Entity fixtures in `tests/TestApplication/src/Entity/`
- CRUD controllers in `tests/TestApplication/src/Controller/`
- Doctrine fixtures loaded via `DoctrineFixturesBundle`
- Deprecation baseline: `tests/baseline-ignore.txt`

## Anti-Patterns

Avoid these common mistakes:

- **Don't use service autowiring** - Configure explicitly in `config/services.php`
- **Don't add typographic quotes** - Use straight quotes only (`'` and `"`)
- **Don't hardcode user-facing text** - Always use translations with `|trans`
- **Don't modify public interfaces lightly** - `Contracts/` contains stable API
- **Don't use `else` after `return`/`throw`** - Return/throw early instead
- **Don't use inline hyperlinks in docs** - Separate link text from URLs
- **Don't use SCSS/LESS** - Standard CSS only
- **Don't use nested CSS rules** - Keep selectors flat
- **Don't skip pre-PR checks** - Run `make checks-before-pr` before every PR

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
