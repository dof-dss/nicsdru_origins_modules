<?php

namespace Drupal\origins_layouts\Plugin\Layout;

use Drupal\origins_layouts\OriginsLayout;

/**
 * Koolhas layout class.
 *
 * @Layout(
 *   id = "origins_layouts_koolhas",
 *   label = @Translation("Koolhas"),
 *   category = @Translation("NICS Origins"),
 *   template = "templates/origins-layouts--koolhas",
 *   icon_map = {
 *     {"left", "right_top"},
 *     {"left", "right_middle"},
 *     {"left", "right_bottom"}
 *   },
 *   regions = {
 *     "left" = {
 *       "label" = @Translation("Left content"),
 *     },
 *     "right_top" = {
 *       "label" = @Translation("Right top content"),
 *     },
 *     "right_middle" = {
 *       "label" = @Translation("Right middle content"),
 *     },
 *     "right_bottom" = {
 *       "label" = @Translation("Right bottom content"),
 *     }
 *   }
 * )
 */
class Koolhas extends OriginsLayout {

}
