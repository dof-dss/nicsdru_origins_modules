<?php

namespace Drupal\origins_layouts\Plugin\Layout;

use Drupal\origins_layouts\OriginsLayout;

/**
 * Calatrava layout class.
 *
 * @Layout(
 *   id = "origins_layouts_calatrava",
 *   label = @Translation("Calatrava"),
 *   category = @Translation("NICS Origins"),
 *   template = "templates/origins-layouts--calatrava",
 *   icon_map = {
 *     {"left_outer", "left_inner", "right_inner", "right_outer"}
 *   },
 *   regions = {
 *     "left_outer" = {
 *       "label" = @Translation("Left outer content"),
 *     },
 *     "left_inner" = {
 *       "label" = @Translation("Left inner content"),
 *     },
 *     "right_inner" = {
 *       "label" = @Translation("Right inner content"),
 *     },
 *     "right_outer" = {
 *       "label" = @Translation("Right outer content"),
 *     }
 *   }
 * )
 */
class Calatrava extends OriginsLayout {

}
