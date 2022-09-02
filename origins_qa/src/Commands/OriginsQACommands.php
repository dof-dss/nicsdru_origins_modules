<?php

namespace Drupal\origins_qa\Commands;

use Drush\Commands\DrushCommands;

/**
* Drush custom commands.
*/
class OriginsQACommands extends DrushCommands {
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
    $this->io()->write("Option is " . $option, TRUE);
  }
}
