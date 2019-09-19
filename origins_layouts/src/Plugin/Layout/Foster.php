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
 *     {"left", "center", "right"}
 *   },
 *   regions = {
 *     "left" = {
 *       "label" = @Translation("Left content"),
 *     },
 *     "center" = {
 *       "label" = @Translation("Center content"),
 *     },
 *     "right" = {
 *       "label" = @Translation("Right content"),
 *     }
 *   }
 * )
 */
class Foster extends OriginsLayout {

}
