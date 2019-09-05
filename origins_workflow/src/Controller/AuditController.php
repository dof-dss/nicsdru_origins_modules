<?php

namespace Drupal\origins_workflow\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller to handle auditing of nodes.
 */
class AuditController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Creates a new ModerationStateConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger interface.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, AccountInterface $account) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('origins_workflow'),
      $container->get('current_user')
    );
  }

  /**
   * Content Audit.
   *
   * @return string
   *   Return confirmation string.
   */
  public function contentAudit($nid) {
    $render_array = [];
    $msg = $this->t('Invalid node ID - @nid', ['@nid' => $nid]);
    if (!empty($nid)) {
      $node = $this->entityTypeManager()->getStorage('node')->load($nid);
      if ($node) {
        // Retrieve audit text from config.
        $audit_button_text = $this->config('origins_workflow.auditsettings')->get('audit_button_text');
        $audit_confirmation_text = $this->config('origins_workflow.auditsettings')->get('audit_confirmation_text');
        // Show confirmation text to user.
        $render_array['confirmation_text'] = [
          '#markup' => $this->t($audit_confirmation_text),
          '#prefix' => "<div class='confirmation_text'>",
          '#suffix' => "</div>",
          '#weight' => 0,
        ];
        // Build a confirm link.
        $link_object = Link::createFromRoute($this->t($audit_button_text),
          'origins_workflow.audit_controller_confirm_audit',
          ['nid' => $nid],
          ['attributes' => ['rel' => 'nofollow', 'class' => 'audit_link']]);
        // Add confirm link to render array.
        $render_array['link1'] = array_merge($link_object->toRenderable(),
          [
            '#prefix' => "<span class='confirm_audit'>",
            '#suffix' => "</span>",
            '#weight' => 1,
          ]);
        // Build a cancel link.
        $link_object_cancel = Link::createFromRoute($this->t("Cancel"),
          'entity.node.canonical',
          ['node' => $nid],
          ['attributes' => ['rel' => 'nofollow', 'class' => 'cancel_link']]);
        // Add cancel link to render array.
        $render_array['link2'] = array_merge($link_object_cancel->toRenderable(),
          [
            '#prefix' => "<span class='cancel'>",
            '#suffix' => "</span>",
            '#weight' => 2,
          ]);
      }
    }
    return $render_array;
  }

  /**
   * Confirm Audit.
   *
   * @return string
   *   Return confirmation string.
   */
  public function confirmAudit($nid) {
    // Bump up the 'next audit due' date and log it.
    $node = $this->entityTypeManager()->getStorage('node')->load($nid);
    if ($node) {
      $node->set('field_next_audit_due', date('Y-m-d', strtotime("+6 months")));
      $node->save();
      $message = "nid " . $nid . " " . $this->t("has been audited by") . " ";
      $message .= $this->account->getAccountName() . " (uid " . $this->account->id() . ")";
      $this->logger->notice($message);
      $this->messenger()->addMessage(t('Content has been marked as audited'));
    }
    // Redirect user to node view (although the 'destination'
    // url argument may override this).
    return $this->redirect('entity.node.canonical', ['node' => $nid]);
  }

}
