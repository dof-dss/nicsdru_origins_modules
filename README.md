[![CircleCI](https://circleci.com/gh/dof-dss/nicsdru_origins_modules.svg?style=svg)](https://circleci.com/gh/dof-dss/nicsdru_origins_modules)

# DOF-DSS Origins Modules

A collection of modules for use across DOF-DSS Drupal sites
* **origins_book:** Maintains Book navigation caches and protects parent pages from deletion or archiving.
* **origins_common:** General utilities and functions including tweaks to the adminimal theme.
* **origins_forms:** Form plugins and utilities.
* **origins_layouts:** A collection of Layout Builder layouts.
* **origins_taxonomy_access:** Provides permissions driven, configurable taxonomy access to site roles.
* **origins_toc:** Provides Table of Contents display options.
* **origins_workflow:** Generic editorial workflows across DoF sites.

# Executables

bin directory - contains executables compiled for Linux amd64 architectures.
src directory - contains the executables source code.


## Usage

You can include this package into your project using composer:
```
composer require dof-dss/nicsdru_origins_modules
```
Details can be found at: https://packagist.org/packages/dof-dss/nicsdru_origins_modules


## Deprecated

* Origins Form Descriptions - Install Origins Forms and enable via admin settings.
* Origins Unique Title - Install Origins Forms and configure via admin settings.

## Static analysis

CI runs PHPStan at level 0, matching the site repositories, with Drupal,
deprecation and disallowed-function rules enabled. Unused suppressions fail the
check; remove obsolete ignores instead of disabling unmatched-ignore reporting.
The PHPStan job also checks for debugging functions, so a separate
disallowed-functions job is not needed.

To run the same configuration from a Drupal project with this package installed
and its PHPStan extensions available:

```sh
vendor/bin/phpstan analyse --memory-limit=1G \
  -c web/modules/origins/.circleci/phpstan.neon web/modules/origins
```

The path is passed explicitly because the shared CI job relocates the configuration
file. Coding style is checked separately using Drupal and DrupalPractice PHPCS.
