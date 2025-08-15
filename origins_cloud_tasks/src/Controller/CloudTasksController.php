<?php

declare(strict_types=1);

namespace Drupal\origins_cloud_tasks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Exception;
use Google\Cloud\Tasks\V2\Client\CloudTasksClient;
use Google\Cloud\Tasks\V2\ListTasksRequest;

/**
 * Returns responses for Origins cloud tasks routes.
 */
final class CloudTasksController extends ControllerBase {

  /**
   * Display current Cloud Tasks in the Queue.
   */
  public function displayTasks(): array {

//    putenv('GOOGLE_APPLICATION_CREDENTIALS=/app/google_application_credentials.json');
    $config = \Drupal::config('origins_cloud_tasks.settings');
    $project_id = $config->get('project_id');
    $queue_id = $config->get('queue_id');
    $location = $config->get('region');

    if (empty($project_id)) {
      return [
        '#markup' => '<p>Project ID for Cloud tasks is missing.</p>',
      ];
    }

    if (empty($queue_id)) {
      return [
        '#markup' => '<p>Queue ID for Cloud tasks is missing.</p>',
      ];
    }


    $build = [];
    $client = new CloudTasksClient();

    try {
      $queue_name = $client->queueName($project_id, $location, $queue_id);

      $request = (new ListTasksRequest())->setParent($queue_name);

      $tasks = $client->listTasks($request);

      $rows = [];
      foreach ($tasks as $task) {

        $rows[] =
          [
            'name' => $task->getName(),
            'schedule' => $task->getScheduleTime()->toDateTime()->format('d/m/Y H:i:s'),
            'url' => $task->getHttpRequest()->getUrl(),
          ];
      }

      $build['tasks'] = [
        '#type' => 'table',
        '#header' => [
          'name' => $this->t('name'),
          'schedule' => $this->t('schedule'),
          'url' => $this->t('url'),
        ],
        '#rows' => $rows,
        '#empty' => $this->t('No tasks found.'),
      ];


    } catch (Exception $ex) {
      $build[] = [
        '#markup' => 'Error: ' . $ex->getMessage()
      ];
    } finally {
      $client->close();
    }

    return $build;
  }

}
