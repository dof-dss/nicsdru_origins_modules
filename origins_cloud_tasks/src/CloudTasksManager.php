<?php

declare(strict_types=1);

namespace Drupal\origins_cloud_tasks;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Google\Cloud\Tasks\V2\Client\CloudTasksClient;
use Google\Cloud\Tasks\V2\CreateTaskRequest;
use Google\Cloud\Tasks\V2\DeleteTaskRequest;
use Google\Cloud\Tasks\V2\ListTasksRequest;

/**
 * Manages Cloud Tasks.
 */
final class CloudTasksManager {

  /**
   * Absolute path to the ADC file.
   */
  protected string $adcPath;

  /**
   * Google Cloud Tasks client.
   *
   * @var \Google\Cloud\Tasks\V2\Client\CloudTasksClient
   */
  protected $cloudClient;

  /**
   * Google Cloud project identifier.
   */
  protected string $projectId;

  /**
   * Google Cloud queue identifier.
   */
  protected string $queueId;

  /**
   * Google Cloud region/location.
   */
  protected string $location;

  /**
   * Constructs a Cloud Tasks manager object.
   */
  public function __construct(
    private readonly ConfigManagerInterface $configManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  private function ready() {
    if (empty($this->cloudClient)) {
      $this->adcPath = getenv('FILE_PRIVATE_PATH') . '/google_application_credentials.json';

      if (!file_exists($this->adcPath)) {
        throw new \Exception('google_application_credentials.json file does not exist.');
      }

      $config = $this->configManager->getConfigFactory()->get('origins_cloud_tasks.settings')->get();

      if (empty($config)) {
        throw new \Exception('Origins Cloud Tasks settings are missing or incomplete.');
      }

      $this->projectId = $config['project_id'];
      $this->queueId = $config['queue_id'];
      $this->location = $config['region'];

      if (empty($this->projectId) || empty($this->queueId) || empty($this->location)) {
        throw new \Exception('Origins Cloud Tasks settings are missing or incomplete.');
      }

      putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $this->adcPath);
      $this->cloudClient = new CloudTasksClient();
    }
  }

  /**
   * Get the current Cloud Tasks.
   */
  public function getTasks() {
    $this->ready();
    $request = (new ListTasksRequest())->setParent($this->getQueueName());

    try {
      return $this->cloudClient->listTasks($request);
    } catch (\Exception $ex) {
      return $ex;
    } finally {
      $this->cloudClient->close();
    }
  }

  /**
   * Create a new Cloud Task.
   */
  public function createTask(CloudTaskInterface $task) {
    $this->ready();

    // TODO: GCT only allow tasks up to 30 days in advance. If the
    // transition_on date is greater then save to a table for processing by
    // regular cron, which will add the Cloud Task when it's within 30 days.
    $task_name = CloudTasksClient::taskName($this->projectId, $this->location, $this->queueId, $task->id());
    $task->name($task_name);

    $request = (new CreateTaskRequest())
        ->setParent($this->getQueueName())
        ->setTask($task->task());

    try {
      $response = $this->cloudClient->createTask($request);
    }
    catch (\Exception $ex) {
      return $ex;
    } finally {
      $this->cloudClient->close();
    }
  }


  /**
   * Delete a Cloud Task.
   */
  public function deleteTask($task_id) {
    $this->ready();
    $task_name = CloudTasksClient::taskName($this->projectId, $this->location, $this->queueId, $task_id);

    $request = (new DeleteTaskRequest())
      ->setName($task_name);

    try {
      $this->cloudClient->deleteTask($request);
    }
    catch (\Exception $ex) {
      return $ex;
    } finally {
      $this->cloudClient->close();
    }
  }

  /**
   * Return the Task Queue based in the stored config.
   */
  protected function getQueueName() {
    $this->ready();
    return CloudTasksClient::queueName($this->projectId, $this->location, $this->queueId);
  }

}
