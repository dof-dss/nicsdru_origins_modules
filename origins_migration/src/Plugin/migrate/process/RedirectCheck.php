<?php

namespace Drupal\origins_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides a 'VideoUrl' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "redirect_check"
 * )
 */
class RedirectCheck extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    //print $value;
    // The next line should probably use dependency injection
    $path_alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
    $alias_objects = $path_alias_storage->loadByProperties([
      'alias' => '/' . $value
    ]);
    if (count($alias_objects) > 0) {
      $msg = "Redirect /" . $value . " not migrated as alias already exists";
      throw new MigrateSkipRowException($msg);
    }
    return $value;
  }

}
