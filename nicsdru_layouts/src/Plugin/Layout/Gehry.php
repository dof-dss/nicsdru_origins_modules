<?php

namespace Drupal\nicsdru_layouts\Plugin\Layout;

use Drupal\nicsdru_layouts\NicsDruLayout;

/**
 * Gehry layout class.
 *
 * @Layout(
 *   id = "nicsdru_layouts_gehry",
 *   label = @Translation("Gehry"),
 *   category = @Translation("NICS Origins"),
 *   template = "templates/nicsdru-layouts--gehry",
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
class Gehry extends NicsDruLayout {

}
