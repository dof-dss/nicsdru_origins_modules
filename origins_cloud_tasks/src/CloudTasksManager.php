<?php

declare(strict_types=1);

namespace Drupal\origins_cloud_tasks;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Google\Cloud\Tasks\V2\Client\CloudTasksClient;
use Google\Cloud\Tasks\V2\CreateTaskRequest;
use Google\Cloud\Tasks\V2\ListTasksRequest;

/**
 * Manages Cloud Tasks.
 */
final class CloudTasksManager {

  /**
   * Google Cloud Tasks client.
   *
   * @var \Google\Cloud\Tasks\V2\Client\CloudTasksClient
   */
  protected $cloudClient;
  protected $projectId;
  protected $queueId;

  protected $location;

  /**
   * Constructs a Cloud Tasks manager object.
   */
  public function __construct(
    private readonly ConfigManagerInterface $configManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    $adc_path = getenv('FILE_PRIVATE_PATH') . '/google_application_credentials.json';

    if (file_exists($adc_path)) {
      putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $adc_path);

      $this->cloudClient = new CloudTasksClient();

      $config = $this->configManager->getConfigFactory()->get('origins_cloud_tasks.settings');
      $this->projectId = $config->get('project_id');
      $this->queueId = $config->get('queue_id');
      $this->location = $config->get('region');
    }
    else {
      \Drupal::logger('origins_cloud_tasks')->error('Google Application Credentials file not found.');
    }
  }

  /**
   * Get the current Cloud Tasks.
   */
  public function getTasks() {
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
   * Return the Task Queue based in the stored config.
   */
  protected function getQueueName() {
    return CloudTasksClient::queueName($this->projectId, $this->location, $this->queueId);
  }

}
