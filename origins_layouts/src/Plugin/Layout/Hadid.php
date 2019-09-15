<?php

namespace Drupal\origins_layouts\Plugin\Layout;

use Drupal\origins_layouts\OriginsLayout;

/**
 * Hadid layout class.
 *
 * @Layout(
 *   id = "origins_layouts_hadid",
 *   label = @Translation("Hadid"),
 *   category = @Translation("NICS Origins"),
 *   template = "templates/origins-layouts--hadid",
 *   icon_map = {
 *     {"left", "right_top"},
 *     {"left", "right_bottom"}
 *   },
 *   regions = {
 *     "left" = {
 *       "label" = @Translation("Left content"),
 *     },
 *     "right_top" = {
 *       "label" = @Translation("Right top content"),
 *     },
 *     "right_bottom" = {
 *       "label" = @Translation("Right bottom content"),
 *     }
 *   }
 * )
 */
class Hadid extends OriginsLayout {

}
