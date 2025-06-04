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
    $header['id'] = $this->t('Issue #');
    $header['label'] = $this->t('Issue');
    $header['content'] = $this->t('Content');
    $header['status'] = $this->t('Status');
    $header['severity'] = $this->t('severity');
    $header['uid'] = $this->t('Reporter');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\origins_content_issue\ContentIssueInterface $entity */

    $module_path = $this->moduleHandler->getModule('origins_content_issue')->getPath();

    $row['id'] = $entity->id();
    $row['label'] = $entity->toLink();

    if ($entity->get('content_entity_revision_id')->isEmpty()) {
      $row['content'] = Link::fromTextAndUrl('View', new Url('entity.node.canonical', ['node' => $entity->get('content_entity_id')->getString()]))->toString();
    } else {
      $row['content'] = Link::fromTextAndUrl('View', new Url('entity.node.revision', ['node' => $entity->get('content_entity_id')->getString(), 'node_revision' => $entity->get('content_entity_revision_id')->getString()]))->toString();
    }

    $status = $entity->get('status')->value;
    $status_field_definition = $entity->getFieldDefinition('status');
    $status_allowed_values = $status_field_definition->getSetting('allowed_values');
    $row['status'] = $status_allowed_values[$status] ?? $status;

    $severity = $entity->get('severity')->value;
    $severity_field_definition = $entity->getFieldDefinition('severity');
    $severity_allowed_values = $severity_field_definition->getSetting('allowed_values');
    $severity_label = $severity_allowed_values[$severity] ?? $severity;

    $row['severity']['data'] = [
      '#theme' => 'image',
      '#uri' => '/' . $module_path . '/assets/severity-' . $severity . '.png',
      '#alt' => $severity_label,
      '#title' => $severity_label,
      '#height' => 24,
      '#width' => 24,
    ];

    $username_options = [
      'label' => 'hidden',
      'settings' => ['link' => $entity->get('uid')->entity->isAuthenticated()],
    ];
    $row['uid']['data'] = $entity->get('uid')->view($username_options);

    $row['created']['data'] = ($entity->get('changed')->isEmpty()) ? $entity->get('created')->view(['label' => 'hidden']) : $entity->get('created')->view(['label' => 'hidden']);

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

    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('There are no @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
    foreach ($this->load() as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }

    if (empty($build['table']['#rows']) && !empty($entity_id)) {

      $node = Node::load($entity_id);

      if (empty($node)) {
        $build['table']['#empty'] = $this->t('The content with the ID %entity_id could not be found.', ['%entity_id' => $entity_id]);
      }
      else {
        $build['table']['#empty'] = $this->t('There are no issues for the content: %title.', ['%title' => $node->getTitle()]);
      }
    }

    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }
    return $build;
  }

}
