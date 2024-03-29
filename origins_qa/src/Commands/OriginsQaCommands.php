<?php

namespace Drupal\origins_qa\Commands;

use Drupal\origins_qa\Controller\QaAccountsManager;
use Drush\Commands\DrushCommands;

/**
 * Drush custom commands.
 */
class OriginsQaCommands extends DrushCommands {

  /**
   * Drush command to enable or dsable all QA accounts.
   *
   * @param string $option
   *   Argument to select 'enable' or 'disable'.
   *
   * @command bulk_update_qa_accounts
   */
  public function bulkUpdateQaAccounts($option = 'disable') {
    // Use QaAccountsManager to enable or disable all QA accounts.
    $qac = new QaAccountsManager();
    $qac->toggleAll($option);
  }

  /**
   * Drush command to create QA accounts.
   *
   * @param string $prefix
   *   Argument to set account prefix (usually 'nw_test').
   * @param string $password
   *   Argument to set account password.
   *
   * @command create_qa_accounts
   */
  public function createQaAccounts($prefix, $password) {
    // Use QaAccountsManager to create QA accounts.
    $qac = new QaAccountsManager();
    $accounts_created = $qac->createQaAccounts($prefix, $password, TRUE);
    $msg = t("@cnt QA accounts created", ['@cnt' => $accounts_created]);
    $this->io()->write($msg, TRUE);
  }

}
