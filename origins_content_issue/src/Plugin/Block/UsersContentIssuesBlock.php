<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\origins_content_issue\ContentIssueManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block that notifies and links users of assigned content issues.
 */
#[Block(
  id: 'origins_content_issue_users_content_issues',
  admin_label: new TranslatableMarkup('Issues for user'),
  category: new TranslatableMarkup('Content issue'),
)]
final class UsersContentIssuesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs the plugin instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly ContentIssueManager $contentIssueManager,
    private readonly RouteMatchInterface $currentRouteMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('content_issue.manager'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [];
    $user = $this->currentRouteMatch->getParameter('user');

    if ($user instanceof AccountInterface) {
      $user_id = $user->id();
    }
    else {
      $user_id = \Drupal::currentUser()->id();
    }

    $issues = $this->contentIssueManager->getIssuesAssignedTo($user_id);
    $link = Link::fromTextAndUrl('Click here to view', Url::fromRoute('entity.content_issue.collection', [], ['query' => ['assigned' => $user_id]]))->toString();
    $count = count($issues) . ((count($issues) > 1) ? ' issues' : ' issue');

    if ($issues) {
      $build['content'] = [
        '#markup' => $this->t('You have @count. @link', [
          '@count' => $count,
          '@link' => $link,
        ]),
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    if ($account->hasPermission('view content issue')) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

}
