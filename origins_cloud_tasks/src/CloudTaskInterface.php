<?php

declare(strict_types=1);

namespace Drupal\origins_cloud_tasks;

/**
 * Provides an interface defining Cloud Tasks.
 */
interface CloudTaskInterface {

  public function id();

  public function task();

  public function name($name);

}
