# AI Contribution Guidelines

Welcome, 🤖 AI assistant! Please follow these guidelines when contributing to this repository:

## General Guidelines

- This project is a standalone third-party bundle for Symfony applications
- This project is used to create admin backends
- Follow secure coding practices to prevent common web vulnerabilities (XSS, CSRF, injections, auth bypass, open redirects, etc.)
- Code must be compatible with Symfony 5.4, 6.x and 7.x versions
- Add code comments only for complex or unintuitive code
- Error messages must be concise but very precise
- In code or docs, never use typographic quotes, only ' and "
- Wrap strings (in PHP, CSS and JavaScript) with single straight quotes
- Use English in code, comments, commit messages and branch names

## Documentation

- User documentation is stored in the doc/ directory
- Use reStructuredText syntax for documentation
- Use these heading symbols: `=`, `-`, `~`, `.`, `"` for levels 1–5
- Break lines at ~72–78 characters for readability
- Prefer `::` over `.. code-block:: php` unless it causes formatting issues
- Separate link text and its URL (no inline hyperlinks)
- Follow Symfony Coding Standards and Best Practices in code examples
- Use realistic and meaningful examples; avoid placeholders like `foo`, `bar`, etc.
- Use `Acme` for vendor names and `example.com` / `example.org` / `example.net` for URLs
- Break code lines >85 characters; use `...` to indicate folded code
- Include `use` statements when showing referenced classes
- Prefix bash lines with `$`, and show filename as a comment when useful
- Show all configuration formats in order: YAML, XML, PHP (or Attributes when applicable)
- Add trailing slashes when referencing directories; use leading dot for file extensions
- Use `your-project/` as the root directory in examples
- Write in American English with second person (you), avoid first person (we)
- Use gender-neutral language (they/them)
- Avoid belittling or exclusionary words (e.g. "just", "obviously", "easy")
- Contractions are allowed (e.g. "it's", "you're")

## Commands

- Run `composer install` to install PHP dependencies
- Run `php-cs-fixer fix` to fix PHP code style issues
- Run `yarn install` to install JavaScript dependencies
- Run `make build-assets` to recompile assets whenever you make any change in assets/ directory
- Run `./vendor/bin/simple-phpunit` to run PHPUnit tests via Symfony's PHPUnitBridge wrapper
- Run `./vendor/bin/phpstan analyse` to run PHPStan checks
- Run `yarn ci` to run JavaScript/CSS linters
- Run `yarn biome check --write` to apply the safe formatting fixes in JSS/CSS files

## PHP Code

- Use modern PHP 8.1 syntax and features
- Avoid using deprecated Symfony or PHP features
- Apply these Symfony coding standards and best practices:
  - Follow PSR-1, PSR-2, PSR-4, and PSR-12 coding standards.
  - Use Yoda conditions for comparisons (e.g. `if (null === $value)`).
  - Always use strict (`===`) and not loose (`==`) comparisons.
  - Use one class per file (unless private/internal).
  - Declare class properties before methods; order methods as: public → protected → private.
  - Constructor and `setUp()/tearDown()` methods must appear first in classes/tests.
  - Use braces `{}` in all control structures, even for one-liners.
  - Add a blank line before `return`, unless it's the only line in the block.
  - In multi-line arrays, use trailing commas.
  - Avoid `else`, `elseif`, or `break` after a block that returns or throws.
  - Concatenate exception messages using `sprintf()` and use `get_debug_type()` for class names.
  - Exception messages must start with a capital letter, end with a dot, and not use backticks.
  - Use `return null;` for nullable returns and `return;` for void functions.
  - Use parentheses when instantiating classes, even without arguments.
  - Do not add `void` return types to test methods.
  - Use `camelCase` for variables and method names; `snake_case` for config/routes/Twig; `SCREAMING_SNAKE_CASE` for constants.
  - Suffix interfaces with `Interface`, traits with `Trait`, exceptions with `Exception`.
  - Prefix abstract classes with `Abstract`, except for test cases.
  - Use UpperCamelCase for PHP class, interface, trait, and enum names.
  - Use snake_case for Twig templates and assets (e.g. `.html.twig`, `.scss`).
  - Add `use` statements for all non-global classes.
  - In PHPDoc:
    - Avoid `@return` if method returns void.
    - Don't use one-line docblocks.
    - Group annotations by type.
    - Put `null` last in union types.
- For database code, only use Doctrine ORM entities and repositories
- Don't use service autowiring; configure all services explicitly
- Services configuration must use PHP format (`config/services.php`)
- Translations must be in PHP format (`translations/*.php`)
- Handle exceptions explicitly and avoid silent catch blocks

## Twig Templates

- Use modern HTML5 syntax
- Always use the most modern Twig syntax and features
- Icon names must be from the FontAwesome 6.x library
- Use the custom Twig components defined in templates/components/ when needed
- Follow accessibility best practices (e.g. `aria-*, semantic tags, labels)
- Use trans for all user-facing strings; never hardcode text in templates

## JavaScript

- Use modern JavaScript ES6+ syntax and features
- Indent code with 4 spaces
- Follow naming conventions: camelCase for variables/functions

## CSS

- Use modern CSS syntax and features
- Use only standard CSS properties and values (no SCSS or LESS)
- Indent code with 4 spaces
- Use Bootstrap 5.3 classes and utilities
- Use logical CSS properties (e.g. `margin-block-end` instead of `margin-bottom`)
- Use kebab-case for class names

---

Thanks for contributing! 🙇‍♂️
