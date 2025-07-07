<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;

/**
 * Manager for Content Issues.
 */
final class ContentIssueManager {

  /**
   * The storage for the Content Issue entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private EntityStorageInterface $issueStorage;

  /**
   * The storage for the Content Issue Comment entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private EntityStorageInterface $commentStorage;

  /**
   * Constructs a ContentIssueManager object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->issueStorage = $entityTypeManager->getStorage('content_issue');
    $this->commentStorage = $entityTypeManager->getStorage('content_issue_comment');
  }

  /**
   * Create a Content Issue entity.
   */
  public function createIssue($title, $description, $content_entity_id, $content_revision_id, $severity): void {

    $node = $this->entityTypeManager->getStorage('node')->load($content_entity_id);

    $issue = $this->issueStorage->create([
      'label' => $title,
      'description' => $description,
      'content_entity_id' => $content_entity_id,
      'content_revision_id' => $content_revision_id,
      'severity' => $severity,
      'assigned_to' => $node->getOwnerId(),
    ]);

    $issue->save();
  }

  /**
   * Delete a Content Issue entity.
   */
  public function deleteIssue($issue_id): void {
    $this->issueStorage->delete([$issue_id]);
  }

  /**
   * Return a render array for the default Issue display mode.
   */
  public function renderIssue($issue_id): array|null {
    $issue = $this->issueStorage->load($issue_id);

    if (empty($issue)) {
      return NULL;
    }

    $viewBuilder = $this->entityTypeManager->getViewBuilder('content_issue');
    return $viewBuilder->view($issue, 'default');
  }

  /**
   * Return a render array for an Issue row.
   */
  public function renderRow($issue): array|null {
    $module_path = \Drupal::service('module_handler')->getModule('origins_content_issue')->getPath();

    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::load($issue->get('content_entity_id')->value);

    $state = $issue->get('state')->value;
    $state_field_definition = $issue->getFieldDefinition('state');
    $state_allowed_values = $state_field_definition->getSetting('allowed_values');
    $state_label = $state_allowed_values[$state] ?? $state;
    $state_class = strtolower(preg_replace("/[^A-Za-z0-9]/", '', $state_label));

    $severity = $issue->get('severity')->value;
    $severity_field_definition = $issue->getFieldDefinition('severity');
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

    $reporter = $issue->get('uid')->entity->label();
    $updated = ($issue->get('changed')->isEmpty()) ? $issue->get('created')->view(['label' => 'hidden']) : $issue->get('created')->view(['label' => 'hidden']);

    $row['issue']['data'] = [
      '#theme' => 'content_issue_row',
      '#id' => $issue->id(),
      '#title' => $issue->label(),
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

    return $row;

  }

  /**
   * Return a render array for the default Issue display mode.
   */
  public function renderComment($comment_id): array|null {
    $comment = $this->commentStorage->load($comment_id);

    if (empty($comment)) {
      return NULL;
    }

    $viewBuilder = $this->entityTypeManager->getViewBuilder('content_issue_comment');
    return $viewBuilder->view($comment, 'default');
  }

  /**
   * Delete a Content Issue entity.
   */
  public function getIssuesByContentId(string|int $node_id, string|int|null $revision_id = NULL) {
    $issues = $this->issueStorage->loadByProperties([
      'content_entity_id' => $node_id,
      'content_entity_revision_id' => $revision_id ?? $node_id,
    ]);

    return $issues;
  }

  /**
   * Delete a Content Issue entity.
   */
  public function getIssuesAssignedTo(string|int $user_id) {
    $issues = $this->issueStorage->loadByProperties([
      'assigned_to' => $user_id,
    ]);

    return $issues;
  }

}
