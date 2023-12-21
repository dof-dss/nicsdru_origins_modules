<?php

namespace Drupal\origins_common\Commands;

use Drupal\structure_sync\StructureSyncHelper;
use Drush\Commands\DrushCommands;

/**
 * Drush custom commands.
 */
class OriginsDrushCommands extends DrushCommands
{

  /**
   * Core EntityTypeManager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   */
  public function __construct()
  {
    parent::__construct();
    $this->entityTypeManager = \Drupal::entityTypeManager();
  }

  /**
   * Drush command to look for URL redirects that already have a URL alias set up
   * (command will search for redirects and matching alias then delete the redirect).
   *
   * @command delete-redirects
   */

  public function delete_redirects()
  {
    // Retrieve all redirects
    $redirect_storage = \Drupal::entityTypeManager()->getStorage('redirect');
    $redirects = $redirect_storage->loadMultiple();
    foreach ($redirects as $redirect) {
      $redirectpath = $redirect->getSource()['path'];

      // Load alias against redirects to look for duplicates
      $path_alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
      $alias_objects = $path_alias_storage->loadByProperties([
        'alias' => '/' . $redirectpath
      ]);

      // Output messages and delete any duplicate entries

      if (count($alias_objects) >= 1) {
        $redirect_storage->delete([$redirect]);

        // Logging the details
        \Drupal::logger('delete_redirects')->notice(
          'Deleted redirect @path',
          ['@path' => $redirect->getSource()['path']]
        );
      }
    }

    // Clear cache message and command
    $this->output()->writeln('Clearing all caches...');
    drupal_flush_all_caches();
  }
}
