<?php

declare(strict_types=1);

namespace Drupal\origins_cloud_tasks;

/**
 * Provides an interface defining Cloud Tasks.
 */
interface CloudTaskInterface {

  /**
   * The task identifier.
   *
   * @return string
   *   Identifier for this task.
   */
  public function id();

  /**
   * The Google Task instance.
   *
   * @return \Google\Cloud\Tasks\V2\Task
   *   Google Cloud Tasks task object.
   */
  public function task();

  /**
   * The Task name (generated via CloudTasksClient::taskName()).
   *
   * @param string $name
   *   The task name (comprising project, location, queue, id).
   */
  public function name(string $name);

}
