<?php

namespace Drupal\nicsdru_layouts\Plugin\Layout;

use Drupal\nicsdru_layouts\NicsDruLayout;

/**
*
* @Layout(
*   id = "nicsdru_layouts_foster",
*   label = @Translation("Foster"),
*   category = @Translation("NICS Origins"),
*   template = "templates/nicsdru-layouts--foster",
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
class Foster extends NicsDruLayout {

}
