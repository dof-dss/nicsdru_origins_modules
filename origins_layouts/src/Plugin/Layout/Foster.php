<?php

namespace Drupal\origins_layouts\Plugin\Layout;

use Drupal\origins_layouts\OriginsLayout;

/**
 * Foster layout class.
 *
 * @Layout(
 *   id = "origins_layouts_foster",
 *   label = @Translation("Foster"),
 *   category = @Translation("NICS Origins"),
 *   template = "templates/origins-layouts--foster",
 *   icon_map = {
 *     { "sidebar_one", "main", "sidebar_two" }
 *   },
 *   regions = {
 *    "sidebar_one" = {
 *       "label" = @Translation("Sidebar one content"),
 *     },
 *     "main" = {
 *       "label" = @Translation("Main content"),
 *     },
 *      "sidebar_two" = {
 *       "label" = @Translation("Sidebar two content"),
 *     }
 *   }
 * )
 */

/**
 * {@inheritdoc}
 */
class Foster extends OriginsLayout {

}
