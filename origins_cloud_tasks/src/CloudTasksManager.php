<?php

declare(strict_types=1);

namespace Drupal\origins_cloud_tasks;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Google\Cloud\Tasks\V2\Client\CloudTasksClient;
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
   * Return the Task Queue based in the stored config.
   */
  protected function getQueueName() {
    $config = $this->configManager->getConfigFactory()->get('origins_cloud_tasks.settings');
    $project_id = $config->get('project_id');
    $queue_id = $config->get('queue_id');
    $location = $config->get('region');

    return CloudTasksClient::queueName($project_id, $location, $queue_id);
  }

}
