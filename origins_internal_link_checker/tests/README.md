# Origins internal link checker tests

This guide explains what the test suite checks, why the tests are useful, and
how to run them. It is intended for developers who are new to PHPUnit, Drupal
testing, or this module.

## The behaviour being protected

The module changes absolute links to the current site into relative links when
a content entity is saved. For example:

```html
<a href="https://www.example.gov.uk/news">News</a>
```

becomes:

```html
<a href="/news">News</a>
```

This sounds like a small change, but it runs during entity saves and can alter
editorial content. A mistake could remove field values, erase summaries, or
change a genuinely external URL. The tests make those safety rules explicit.

## What a unit test proves

A unit test checks a small class without installing Drupal or using its
database. These tests create the real class under test and replace its Drupal
dependencies with controlled stand-in objects called **mocks**.

Unit tests are a good fit here because most decisions can be checked using
plain strings and small sets of field data. They run quickly and identify the
specific rule which failed.

The tests do not prove that Drupal discovers the service, invokes the legacy
presave hook, or displays the configuration form correctly. Those integration
points require a kernel or browser test. During development, they should also
be checked in an installed Drupal site by rebuilding the service container.

## Test files

### `InternalLinkProcessorTest.php`

[`InternalLinkProcessorTest.php`](src/Unit/InternalLinkProcessorTest.php) tests
the main content-processing service.

It checks that:

- every value in a multi-value formatted-text field is processed;
- the field itself is not rebuilt, preserving formats, summaries, and other
  values;
- the current hostname and configured aliases are converted;
- `http`, `https`, and a leading `www` are handled consistently;
- non-default ports must be configured explicitly;
- paths, query strings, and fragments are preserved;
- exact exclusions remain absolute;
- external hosts, subdomains, and URLs containing user information are left
  unchanged;
- only real `href` attributes are changed, not attributes such as `data-href`;
- a migration import suppresses processing while an ordinary front-page
  request does not.

Many URL examples are supplied by `conversionProvider()`. This is a PHPUnit
**data provider**: PHPUnit runs the same test method once for each named row.
When a row fails, its descriptive name helps explain the broken rule.

### `MigrationExecutionContextTest.php`

[`MigrationExecutionContextTest.php`](src/Unit/MigrationExecutionContextTest.php)
tests the small event subscriber which tracks active Drupal migrations.

The link processor should not rewrite source content while a migration is
importing it. Older code guessed that a request to `/` or `/batch` was a
migration, which also skipped unrelated saves. The new context listens for
Drupal Migrate's pre-import and post-import events instead.

The test checks the event names, nested import scopes, and an unmatched
post-import event. The last scenario is defensive: the counter must never
become negative.

### `UrlListTest.php`

[`UrlListTest.php`](src/Unit/UrlListTest.php) tests configuration input before
the processor relies on it.

It checks that:

- surrounding whitespace and empty lines are removed;
- aliases use complete HTTP or HTTPS URLs;
- aliases may contain a port but not a content path, query, or fragment;
- exclusion entries may identify a complete URL;
- URLs containing user information are rejected.

Keeping this logic in a small class lets the form and processor share the same
normalisation rules.

## How mocks work in these tests

The real processor receives services and Drupal field objects. A unit test
does not need Drupal configuration storage or a real content entity, so it
creates mocks for interfaces such as `ConfigFactoryInterface` and
`FieldItemListInterface`.

For example, this expectation says that a field item must receive one updated
property:

```php
$first_item->expects($this->once())
  ->method('set')
  ->with('value', '<a href="/one">One</a>');
```

The test deliberately expects the entity's `set()` method never to be called.
That is important: replacing the whole field was the behaviour which could
discard additional values and summaries.

Most tests follow the same three stages:

1. **Arrange:** create the processor, input, and mocks.
2. **Act:** call the method being tested.
3. **Assert:** compare the result or verify an expected interaction.

## Running the tests with DEPT

The Origins modules repository does not contain a complete Drupal installation
or its own `vendor` directory. Run the suite through a consuming project such
as DEPT after the current module code has been installed there.

From the DEPT project root, run the whole module suite:

```shell
ddev exec vendor/bin/phpunit -c phpunit.xml web/modules/origins/origins_internal_link_checker/tests/src/Unit
```

Run one test class while working on a specific area:

```shell
ddev exec vendor/bin/phpunit -c phpunit.xml web/modules/origins/origins_internal_link_checker/tests/src/Unit/UrlListTest.php
```

A successful run ends with `OK`, followed by the number of tests and
assertions. A failure identifies the test method and, for data-provider tests,
the named row which failed.

Use the explicit module path shown above when checking this suite locally.
Do not assume that a broad `--testsuite unit` command discovered these tests;
check its reported test count before treating it as evidence.

## Other useful checks

Unit tests check behaviour, while these tools check different kinds of issues:

```shell
ddev exec vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/origins/origins_internal_link_checker
```

```shell
ddev exec vendor/bin/phpstan analyse web/modules/origins/origins_internal_link_checker/src web/modules/origins/origins_internal_link_checker/tests/src/Unit --configuration=.circleci/phpstan.neon --no-progress --memory-limit=1G
```

- **PHPCS** checks Drupal coding standards.
- **PHPStan** checks types and code paths without executing them.
- **PHPUnit** executes the scenarios described by the tests.

Passing one tool does not replace the others.

When service definitions or routes change, enable the module in a disposable
local site and run `ddev drush cr`. A successful cache rebuild confirms that
Drupal can compile the service container, but it still does not replace the
behavioural unit tests.

## Adding a test

Add a named row to an existing data provider when the new scenario uses the
same setup and assertion. Add a separate test method when it needs different
mocks or verifies a different interaction.

Choose a name which describes the rule, such as `subdomain is not equivalent`,
rather than a vague name such as `case 4`. A good name makes future failures
easier to understand.

Do not change an expected value only to make a failing test green. First decide
whether the requirement changed. If it did not, the failure may have found a
regression in the production code.

## Glossary

- **Absolute URL:** A complete URL containing a scheme and hostname.
- **Relative URL:** A site-local URL such as `/news`.
- **Authority:** The hostname and optional port portion of a URL.
- **Content entity:** A Drupal object which stores content, such as a node or
  taxonomy term.
- **Field delta:** The numeric position of one item in a multi-value field.
- **Mock:** A test-controlled replacement for a real dependency.
- **Assertion:** A statement describing the result a test requires.
- **Data provider:** A method supplying several named scenarios to one test.
