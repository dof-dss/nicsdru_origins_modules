<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the content issue entity type.
 */
final class ContentIssueListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    private readonly ContentIssueManager $contentIssueManager,
  ) {
    parent::__construct($entity_type, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('content_issue.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['issue'] = $this->t('Issues');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\origins_content_issue\ContentIssueInterface $entity */

    $row = $this->contentIssueManager->renderRow($entity);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $query = $this->getStorage()->getQuery();
    $query->accessCheck(TRUE);

    // @phpstan-ignore-next-line.
    $entity_id = \Drupal::request()->request->get('entity_id');
    // @phpstan-ignore-next-line.
    $revision_id = \Drupal::request()->request->get('revision_id');

    if (!empty($entity_id)) {
      $query->condition('content_entity_id', $entity_id);
    }

    if (!empty($revision_id)) {
      $query->condition('content_entity_revision_id', $revision_id);
    }

    // @phpstan-ignore-next-line.
    $qs = \Drupal::request()->query->all();

    if (array_key_exists('severity', $qs)) {
      $query->condition('severity', $qs['severity']);
    }

    if (array_key_exists('assigned', $qs)) {
      if ($qs['assigned'] > 0) {
        // @phpstan-ignore-next-line.
        $query->condition('assigned_to', \Drupal::currentUser()->id());
      }
      if ($qs['assigned'] < 0) {
        $query->notExists('assigned_to');
      }
    }

    $entity_ids = $query->execute();

    return $this->storage->loadMultiple($entity_ids);
  }

  /**
   * {@inheritdoc}
   *
   * Builds the entity listing as renderable array for table.html.twig.
   *
   * @todo Add a link to add a new item to the #empty text.
   */
  public function render() {
    // @phpstan-ignore-next-line.
    $request = \Drupal::request();

    $issue_id = $request->request->get('content_issue');
    // The ID of the entity for which issues will be displayed.
    $entity_id = $request->request->get('entity_id');
    $module_path = $this->moduleHandler->getModule('origins_content_issue')->getPath();
    $current_qs = $request->query->all();

    // If the request is to display a specific issue, remove any applied filters, as the issue may not match them.
    if (!empty($issue_id)) {
      $current_qs = [];
    }

    $build['filters'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['filters']],
    ];

    $build['filters']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $this->t('Filter by:'),
      '#attributes' => ['class' => ['filters-label']],
    ];

    $build['filters']['severity']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $this->t('Severity'),
    ];

    $qs = $current_qs;
    foreach (['high' => 1, 'medium' => 2, 'low' => 3] as $severity => $val) {
      $classes = ['filter', 'severity-' . $severity];
      $qs['severity'] = $val;

      if (array_key_exists('severity', $current_qs)) {
        if ($current_qs['severity'] == $val) {
          unset($qs['severity']);
          $classes[] = 'active';
        }
      }

      $build['filters']['severity'][$severity] = [
        '#type' => 'link',
        '#title' => $this->t(ucfirst($severity)),
        '#url' => Url::fromRoute('entity.content_issue.collection', [], ['query' => $qs]),
        '#attributes' => [
          'class' => $classes,
        ]
      ];
    }

    $build['filters']['assigned_to']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $this->t('Assigned to'),
    ];

    $qs = $current_qs;
    foreach (['me' => 1, 'nobody' => -1] as $assigned => $val) {
      $classes = ['filter', 'assigned-' . $assigned];
      $qs['assigned'] = $val;

      if (array_key_exists('assigned', $current_qs)) {
        if ($current_qs['assigned'] == $val) {
          unset($qs['assigned']);
          $classes[] = 'active';
        }
      }

      $build['filters']['assigned'][$assigned] = [
        '#type' => 'link',
        '#title' => $this->t(ucfirst($assigned)),
        '#url' => Url::fromRoute('entity.content_issue.collection', [], ['query' => $qs]),
        '#attributes' => [
          'class' => $classes,
        ]
      ];
    }

    $build['dashboard'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['content-issue-dashboard'],
      ],
    ];

    $build['dashboard']['main'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['content-issue-dashboard-main'],
      ],
    ];

    $empty_text_severity = ' ';
    $empty_text_assigned = '';

    if (array_key_exists('severity', $current_qs)) {
      $empty_text_severity = match($current_qs['severity']) {
        '1' => ' high severity ',
        '2' => ' medium severity ',
        '3' => ' low severity ',
      };
    }

    if (array_key_exists('assigned', $current_qs)) {
      $empty_text_assigned = match($current_qs['assigned']) {
        '-1' => ' assigned to nobody',
        '1' => ' assigned to you',
      };
    }

    $empty_text = $this->t('There are no%severitycontent issues%assigned.', [
      '%severity' => $empty_text_severity,
      '%assigned' => $empty_text_assigned,
    ]);

    $build['dashboard']['main']['table'] = [
      '#type' => 'table',
      '#header' => [],
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $empty_text
      ],
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];

    $build['dashboard']['aside'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['content-issue-dashboard-aside'],
        'id' => ['content-issue-dashboard-aside'],
      ],
    ];

    $build['dashboard']['aside']['close'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '<img src="/' . $module_path . '/assets/icon-close.svg" /><span>Close</span',
      '#attributes' => [
        'class' => ['content-issue-layout-close'],
        'title' => $this->t('Close the details pane'),
      ]
    ];

    $build['table'] = [];

    // Display the requested issue in the side info-pane.
    if (!empty($issue_id)) {
      $issue_details = $this->contentIssueManager->renderIssue($issue_id);
      if (empty($issue_details)) {
        // @phpstan-ignore-next-line.
        \Drupal::messenger()->addWarning($this->t('The requested issue could not be found.'));
      }
      else {
        $build['dashboard']['aside']['container'] = $issue_details;
        $build['dashboard']['aside']['#attributes']['class'][] = 'open';
      }
    }
    else {
      $build['dashboard']['aside']['container'] = [
        '#type' => 'html_tag',
        '#tag' => 'article',
        '#attributes' => [
          'class' => ['content-issue-layout'],
          'id' => ['content-issue-details'],
        ]
      ];
    }

    foreach ($this->load() as $entity) {
      if ($row = $this->buildRow($entity)) {
        if (array_key_exists('operations', $row)) {
          unset($row['operations']);
        }
        $build['dashboard']['main']['table']['#rows'][$entity->id()] = $row;
      }
    }

    // Display a warning if the request to show issues for a specific node returns no results.
    if (empty($build['table']['#rows']) && !empty($entity_id)) {

      $node = Node::load($entity_id);

      if (empty($node)) {
        $build['dashboard']['main']['table']['#empty'] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'h3',
            '#value' => $this->t('Oops')
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t('The content item with ID %entity_id was not found.', ['%entity_id' => $entity_id])
          ]
        ];
      }
      else {
        $empty_text = $this->t('There are no%severitycontent issues%assigned for the content: %title', [
          '%severity' => $empty_text_severity,
          '%assigned' => $empty_text_assigned,
          '%title' => $node->getTitle(),
        ]);

        $build['dashboard']['main']['table']['#empty'] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $empty_text,
          ]
        ];
      }
    }

    if ($this->limit) {
      $build['dashboard']['main']['pager'] = [
        '#type' => 'pager',
      ];
    }

    $build['#attached']['library'][] = 'origins_content_issue/user-interface';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    return [];
  }

}
