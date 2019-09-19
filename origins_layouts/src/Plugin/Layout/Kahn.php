<?php

namespace Drupal\origins_layouts\Plugin\Layout;

use Drupal\origins_layouts\OriginsLayout;

/**
 * Kahn layout class.
 *
 * @Layout(
 *   id = "origins_layouts_kahn",
 *   label = @Translation("Kahn"),
 *   category = @Translation("NICS Origins"),
 *   template = "templates/origins-layouts--kahn",
 *   icon_map = {
 *     {"center"}
 *   },
 *   regions = {
 *     "center" = {
 *       "label" = @Translation("Center content"),
 *     }
 *   }
 * )
 */
class Kahn extends OriginsLayout {

}
