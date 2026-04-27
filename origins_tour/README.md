# Admin Toolbar

The Origins Tour module contains a collection of generic tours applicable to multiple sites.
These tours intend to guide the user on how to operate and explain basic site features.

## Tours include

- Content overview
- Content/node create
    Content types include:
        - News
        - Page
        - Publication
        - Feature
        - Featured content list
- File overview
- Media add
- Media overview
- Menu
- Moderation sidebar
- Revisions
- Slideshow
- URL re-direct
- What links here

## Requirements

This module requires the following modules:

- [Tours Extras](https://www.drupal.org/project/tour_extras)
- [Tour](https://www.drupal.org/project/tour)

## Installation

Install dependant modules.

Tour:
Install Tour & Tourauto.

Note:
Must install Tour version 2.0 and not the version that is in core UI.
Run composer require 'drupal/tour:^2.0'

Tours Extras:
Install Tour Extras WYSIWYG, Extras URL Step.
- composer require 'drupal/tour_extras:^1.0'

## Configuration

Configure the site tours at (/admin/config/user-interface/tour).
