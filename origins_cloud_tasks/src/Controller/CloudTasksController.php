<?php

declare(strict_types=1);

namespace Drupal\origins_cloud_tasks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\origins_cloud_tasks\CloudTasksManager;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Returns responses for Origins cloud tasks routes.
 */
final class CloudTasksController extends ControllerBase {

  use AutowireTrait;

  /**
   * Constructs a Cloud Tasks manager object.
   */
  public function __construct(
    #[Autowire(service: 'origins_cloud_tasks.manager')]
    protected CloudTasksManager $taskManager
  ) {}

  public function displayAuthCheck(): array {
    $path = getenv('GOOGLE_APPLICATION_CREDENTIALS');

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Check'),
        $this->t('Status'),
      ],
      '#rows' => []
    ];

    if (file_exists($path)){
      $build['table']['#rows'][] = [$this->t('ADC File present'), $this->t('Yes')];

      $json_string = file_get_contents($path);

      $json_data =  json_decode((string) $json_string, true);

      $json_error = json_last_error();

      if ($json_error == JSON_ERROR_NONE) {
        $build['table']['#rows'][] = [$this->t('JSON valid'), $this->t('Yes')];
      }
      else {
        $build['table']['#rows'][] = [$this->t('JSON valid'), $this->t('No') . ' (' . json_last_error_msg() . ')'];
      }
    }
    else {
      $build['table']['#rows'][] = [$this->t('ADC File present'), $this->t('No')];
      $build['table']['#rows'][] = [$this->t('JSON valid'), $this->t('N/A')];
    }

    return $build;
  }

  /**
   * Display current Cloud Tasks in the Queue.
   */
  public function displayTasks(): array {

    $build = [];

    $tasks = $this->taskManager->getTasks();

    if ($tasks instanceof \Exception) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => 'Error: ' . $tasks->getMessage(),
      ];
    }
    else {
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

      return $build;
    }
  }

}
