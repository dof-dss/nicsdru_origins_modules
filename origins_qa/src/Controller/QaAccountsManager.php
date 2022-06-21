<?php

namespace Drupal\origins_qa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

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
      'operations' => $this->t('Operations'),
    ];

    $rows = [];

    foreach ($accounts as $account) {
      $rows[] = [
        $account->label(),
        $account->isActive(),
        date('d F Y', $account->getLastAccessedTime()),
        [
          'data' => [
            '#type' => 'dropbutton',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute('entity.user.edit_form', ['user' => $account->id()]),
              ],

            ],
          ],
        ],
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
