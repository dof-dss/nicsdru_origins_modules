<?php

namespace Drupal\origins_qa\Commands;

use Drush\Commands\DrushCommands;
use Drupal\origins_qa\Controller\QaAccountsManager;

/**
* Drush custom commands.
*/
class OriginsQaCommands extends DrushCommands {
  /**
  * Drush command to enable or dsable all QA accounts.
  *
  * @param string $option
  *  Argument to select 'enable' or 'disable'
  *
  * @command bulk_update_qa_accounts
  */
  public function bulk_update_qa_accounts($option = 'disable')
  {
    // Use QaAccountsManager to enable or disable all QA accounts.
    $qac = new QaAccountsManager();
    $qac->toggleAll($option);
  }
}
