<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides a list controller for the content issue entity type.
 */
final class ContentIssueListBuilder extends EntityListBuilder {

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

    $module_path = $this->moduleHandler->getModule('origins_content_issue')->getPath();

    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::load($entity->get('content_entity_id')->value);

    $state = $entity->get('state')->value;
    $state_field_definition = $entity->getFieldDefinition('state');
    $state_allowed_values = $state_field_definition->getSetting('allowed_values');
    $state_label = $state_allowed_values[$state] ?? $state;
    $state_class = strtolower(preg_replace("/[^A-Za-z0-9]/", '', $state_label));

    $severity = $entity->get('severity')->value;
    $severity_field_definition = $entity->getFieldDefinition('severity');
    $severity_allowed_values = $severity_field_definition->getSetting('allowed_values');
    $severity_label = $severity_allowed_values[$severity] ?? $severity;

    $severity_image = [
      '#theme' => 'image',
      '#uri' => '/' . $module_path . '/assets/severity-' . $severity . '.png',
      '#alt' => $severity_label,
      '#title' => $severity_label,
      '#height' => 20,
      '#width' => 20,
    ];

    $severity_image = \Drupal::service('renderer')->render($severity_image);

    $reporter = $entity->get('uid')->entity->label();
    $updated = ($entity->get('changed')->isEmpty()) ? $entity->get('created')->view(['label' => 'hidden']) : $entity->get('created')->view(['label' => 'hidden']);

    $row['issue']['data'] = [
      '#theme' => 'content_issue_row',
      '#id' => $entity->id(),
      '#title' => $entity->label(),
      '#node_type' => ucfirst($node->bundle()),
      '#node_title' => $node->getTitle(),
      '#state' => $state_label,
      '#state_class' => $state_class,
      '#severity' => $severity_label,
      '#severity_image' => $severity_image,
      '#reporter' => $reporter,
      '#updated' => $updated,
      '#module_path' => $module_path,
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $query = $this->getStorage()->getQuery();
    $query->accessCheck(TRUE);

    $entity_id = \Drupal::request()->get('entity_id');
    $revision_id = \Drupal::request()->get('revision_id');

    if (!empty($entity_id)) {
      $query->condition('content_entity_id', $entity_id);
    }

    if (!empty($revision_id)) {
      $query->condition('content_entity_revision_id', $revision_id);
    }

    $qs = \Drupal::request()->query->all();

    if (array_key_exists('severity', $qs)) {
      $query->condition('severity', $qs['severity']);
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

    $entity_id = \Drupal::request()->get('entity_id');
    $module_path = $this->moduleHandler->getModule('origins_content_issue')->getPath();
    $qs = \Drupal::request()->query->all();

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

    $build['filters']['severity']['label'] =[
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $this->t('Severity'),
    ];

    foreach (['high' => 1, 'medium' => 2, 'low' => 3] as $severity => $val) {

      $querystring_severity = ['severity' => $val];
      $classes = ['filter', 'severity-' . $severity];

      if (array_key_exists('severity', $qs)) {
        if ($qs['severity'] == $val) {
          $querystring_severity = [];
          $classes[] = 'active';
        }
      }

      $build['filters']['severity'][$severity] = [
        '#type' => 'link',
        '#title' => $this->t(ucfirst($severity)),
        '#url' => Url::fromRoute('entity.content_issue.collection', [], ['query' => $querystring_severity]),
        '#attributes' => [
          'class' => $classes,
        ]
      ];
    }

    $header = $this->buildHeader();
    if (array_key_exists('operations', $header)) {
      unset($header['operations']);
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

    $build['dashboard']['main']['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('There are no @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
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

    $build['dashboard']['aside']['container'] = [
      '#type' => 'html_tag',
      '#tag' => 'article',
      '#attributes' => [
        'class' => ['content-issue-layout'],
        'id' => ['content-issue-details'],
      ]
    ];

    foreach ($this->load() as $entity) {
      if ($row = $this->buildRow($entity)) {
        if (array_key_exists('operations', $row)) {
          unset($row['operations']);
        }
        $build['dashboard']['main']['table']['#rows'][$entity->id()] = $row;
      }
    }

    if (empty($build['table']['#rows']) && !empty($entity_id)) {

      $node = Node::load($entity_id);

      if (empty($node)) {
        $build['dashboard']['main']['table']['#empty'] = $this->t('The content with the ID %entity_id could not be found.', ['%entity_id' => $entity_id]);
      }
      else {
        $build['dashboard']['main']['table']['#empty'] = $this->t('There are no issues for the content: %title.', ['%title' => $node->getTitle()]);
      }
    }

    if ($this->limit) {
      $build['dashboard']['main']['pager'] = [
        '#type' => 'pager',
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    return [];
  }

}
