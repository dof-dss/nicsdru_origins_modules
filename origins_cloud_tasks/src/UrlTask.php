<?php

namespace Drupal\origins_cloud_tasks;

use Drupal\Core\Url;
use Google\Cloud\Tasks\V2\Client\CloudTasksClient;
use Google\Cloud\Tasks\V2\HttpMethod;
use Google\Cloud\Tasks\V2\HttpRequest;
use Google\Cloud\Tasks\V2\Task;
use Google\Protobuf\Timestamp;

/**
 * Helper to create a Cloud Task to call A URL.
 */
class UrlTask implements CloudTaskInterface {

  /**
   * Task identifier.
   */
  protected string $id;

  /**
   * Linux timestamp for when the task's cron callback should be executed.
   */
  protected string $schedule;

  /**
   * Google Task object.
   *
   * @var \Google\Cloud\Tasks\V2\Task
   */
  protected $task;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function task() {
    return $this->task;
  }

  /**
   * The task schedule.
   *
   * @return string
   *   Schedule date time as a timestamp.
   */
  public function schedule() {
    return $this->schedule;
  }

  /**
   * {@inheritdoc}
   */
  public function name(string $name) {
    $this->task->setName($name);
  }

  /**
   * Constructs a new URL callback task.
   *
   * @param string $id
   *   The task identifier.
   * @param string $schedule
   *   Timestamp for when the task callback should execute.
   * @param string $url
   *   The URL the callback should call.
   */
  public function __construct(string $id, string $schedule, string $url) {
    $this->task = new Task();
    $this->id = $id;
    $this->schedule = $schedule + \Drupal::config('origins_cloud_tasks.settings')->get('callback_offset') ?? 5;

    $httpRequest = new HttpRequest();
    $httpRequest->setUrl($url);
    $httpRequest->setHttpMethod(HttpMethod::GET);

    $ts = new Timestamp();
    $ts->setSeconds($this->schedule);
    $ts->setNanos(0);

    $this->task->setHttpRequest($httpRequest);
    $this->task->setScheduleTime($ts);
  }

}
