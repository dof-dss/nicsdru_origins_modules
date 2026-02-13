<?php

declare(strict_types=1);

namespace Drupal\origins_workflow\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
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
  public function __invoke($min_revisions = '50'): array {
    $table_header = [
      'Node ID',
      'Revisions count',
      'Title',
    ];
    $has_domains = \Drupal::moduleHandler()->moduleExists('domain') ?? FALSE;
    $rows = [];
    $total_revisions_count = 0;
    $footer_colspan = 3;

    $query = $this->database->select('node_revision', 'nr');

    $query->addField('nr', 'nid',);
    $query->addExpression('COUNT(*)', 'Total');
    $query->leftJoin('node_field_data', 'fd', 'nr.nid = fd.nid');
    $query->addField('fd', 'title', 'Title');
    $query->groupBy('nr.nid');
    $query->groupBy('fd.title');
    $query->having('COUNT(*) > :min', [':min' => $min_revisions]);
    $query->orderBy('Total', 'DESC');

    if ($has_domains) {
      $table_header[] = 'Department';
      $footer_colspan++;
      $query->leftJoin('node_revision__field_domain_source', 'ds', 'ds.entity_id = nr.nid AND revision_id = nr.vid');
      $query->addField('ds', 'field_domain_source_target_id', 'Department');
    }

    $table_header[] = 'Operations';

    $results = $query->execute();

    for ($i = 25; $i <= 150; $i += 25) {
      $filter_links[] = Link::createFromRoute($i, 'origins_workflow.revisions_report', ['min_revisions' => $i])->toString();
    }

    $build['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Nodes with more than @count revisions.', ['@count' => $min_revisions]),
      '#prefix' => $this->t('Minimum revisions: ') . implode(' ', $filter_links),
    ];

    foreach ($results as $result) {
      $total_revisions_count += $result->Total;

      $row = [
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
      ];

      if ($has_domains) {
        array_push($row, ucfirst($result->Department));
      }

      array_push($row, [
        'data' => [
          [
            '#type' => 'link',
            '#title' => $this->t('View revisions'),
            '#url' => URL::fromUri('internal:/node/' . $result->nid . '/revisions'),
          ],
        ],
      ]);

      $rows[] = $row;
    }

    $build['revisions'] = [
      '#theme' => 'table',
      '#header' => $table_header,
      '#footer' => [
        [
          [
            'data' => count($rows),
            'title' => $this->t('Total number of nodes with over @min_revisions revisions', ['@min_revisions' => $min_revisions]),
          ],
          [
            'data' => $total_revisions_count,
            'colspan' => $footer_colspan,
            'title' => $this->t('Total number of revisions across the @node_count nodes', ['@node_count' => count($rows)]),
          ],
        ]
      ],
      '#rows' => $rows,
      '#empty' => $this->t('There are no nodes with more than @revisions_count.', ['@revisions_count' => $min_revisions]),
    ];

    return $build;
  }

}
