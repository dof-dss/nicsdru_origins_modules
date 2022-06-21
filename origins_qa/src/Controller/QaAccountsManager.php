<?php

namespace Drupal\origins_qa\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Origins QA.
 */
class QaAccountsManager extends ControllerBase {

  /**
   * Returns a list of QA accounts.
   */
  public function list() {
    $build = [];

    $accounts = $this->entityTypeManager()
                  ->getListBuilder('user')
                  ->getStorage()
                  ->loadByProperties([
                    'roles' => 'qa'
                  ]);

    $header = [
      'username' => $this->t('Username'),
      'status' => $this->t('Status'),
      'last_access' => $this->t('Last access'),
    ];

    $rows = [];

    foreach ($accounts as $account) {
      $rows[] = [
        $account->label(),
        $account->isActive(),
        date('d F Y', $account->getLastAccessedTime()),
      ];
    }

    $build['qa_accounts'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

}
