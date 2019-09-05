<?php

namespace Drupal\origins_layouts\Plugin\Layout;

use Drupal\origins_layouts\OriginsLayout;

/**
 * Gehry layout class.
 *
 * @Layout(
 *   id = "origins_layouts_gehry",
 *   label = @Translation("Gehry"),
 *   category = @Translation("NICS Origins"),
 *   template = "templates/origins-layouts--gehry",
 *   icon_map = {
 *     { "main", "sidebar" }
 *   },
 *   regions = {
 *     "main" = {
 *       "label" = @Translation("Main content"),
 *     },
 *      "sidebar" = {
 *       "label" = @Translation("Sidebar content"),
 *      }
 *   }
 * )
 */
class Gehry extends OriginsLayout {

}
