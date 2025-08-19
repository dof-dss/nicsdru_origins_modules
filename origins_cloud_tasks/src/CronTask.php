<?php

namespace Drupal\origins_cloud_tasks;

use Drupal\Core\Url;
use Google\Cloud\Tasks\V2\Client\CloudTasksClient;
use Google\Cloud\Tasks\V2\HttpMethod;
use Google\Cloud\Tasks\V2\HttpRequest;
use Google\Cloud\Tasks\V2\Task;
use Google\Protobuf\Timestamp;

class CronTask implements CloudTaskInterface {

  protected $id;

  protected $schedule;

  /**
   * Google Task object.
   *
   * @var \Google\Cloud\Tasks\V2\Task
   */
  protected $task;

  public function id() {
    return $this->id;
  }

  public function task() {
    return $this->task;
  }

  public function schedule() {
    return $this->schedule;
  }

  public function name($name) {
    $this->task->setName($name);
  }

  public function __construct($id, $schedule) {
    $state = \Drupal::service('state');
    $this->task = new Task();
    $this->id = $id;
    $this->schedule = $schedule + \Drupal::config('origins_cloud_tasks.settings')->get('callback_offset') ?? 5;

    $url = Url::fromRoute('system.cron', ['key' => $state->get('system.cron_key')], ['absolute' => TRUE])->toString();

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
