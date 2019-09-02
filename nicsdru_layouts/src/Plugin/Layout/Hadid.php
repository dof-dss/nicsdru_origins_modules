<?php

namespace Drupal\nicsdru_layouts\Plugin\Layout;

use Drupal\nicsdru_layouts\NicsDruLayout;

/**
 *
 * @Layout(
 *   id = "nicsdru_layouts_hadid",
 *   label = @Translation("Hadid"),
 *   category = @Translation("NICS Origins"),
 *   template = "templates/nicsdru-layouts--hadid",
 *   icon_map = {
 *     {"main", "sidebar_top"},
 *     {"main", "sidebar_bottom"}
 *   },
 *   regions = {
 *     "main" = {
 *       "label" = @Translation("Main content"),
 *     },
 *     "sidebar_top" = {
 *       "label" = @Translation("Sidebar top content"),
 *     },
 *      "sidebar_bottom" = {
 *       "label" = @Translation("Sidebar bottom content"),
 *     }
 *   }
 * )
 */
class Hadid extends NicsDruLayout {

}
