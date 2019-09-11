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
class Hadid extends OriginsLayout {

}
