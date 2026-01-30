<?php

declare(strict_types=1);

namespace Drupal\origins_workflow\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates a report detailing node revisions.
 */
final class RevisionsReportController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly Connection $database,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('database'),
    );
  }

  /**
   * Display revisions table.
   */
  public function __invoke(): array {
    $min_revisions = 50;
    $query = $this->database->select('node_revision', 'nr');

    $query->addField('nr', 'nid');
    $query->addExpression('COUNT(*)', 'Total');
    $query->leftJoin('node_field_data', 'fd', 'nr.nid = fd.nid');
    $query->addField('fd', 'title', 'Title');
    $query->groupBy('nr.nid');
    $query->groupBy('fd.title');
    $query->having('COUNT(*) > :min', [':min' => $min_revisions]);
    $query->orderBy('Total', 'DESC');

    $results = $query->execute();
    $rows = [];

    $build['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Nodes with more than @count revisions.', ['@count' => $min_revisions]),
    ];

    foreach ($results as $result) {
      $rows[] = [
        $result->nid,
        $result->Total,
        [
          'data' => [
            [
              '#type' => 'link',
              '#title' => $result->Title,
              '#url' => URL::fromUri('internal:/node/' . $result->nid),
            ],
          ],
        ],
        [
          'data' => [
            [
              '#type' => 'link',
              '#title' => $this->t('View revisions'),
              '#url' => URL::fromUri('internal:/node/' . $result->nid . '/revisions'),
            ],
          ],
        ],
      ];
    }

    $build['revisions'] = [
      '#theme' => 'table',
      '#header' => [
        'nid',
        'Revisions count',
        'Title',
        'Revisions',
      ],
      '#rows' => $rows,
      '#empty' => $this->t('There are no nodes with more than @count revisions.', ['@count' => $min_revisions]),
    ];

    return $build;
  }

}
