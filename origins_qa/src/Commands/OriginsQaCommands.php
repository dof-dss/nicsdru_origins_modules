<?php

namespace Drupal\origins_qa\Commands;

use Drupal\Core\Password\DefaultPasswordGenerator;
use Drupal\origins_qa\Controller\QaAccountsManager;
use Drupal\user\Entity\User;
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

  /**
   * Drush command set password on QA accounts.
   *
   * Assign the password set in the environment variable.
   *
   * @command password_qa_accounts
   */
  public function assignPasswordToQaAccounts() {
    $entity_type_manager = \Drupal::entityTypeManager();
    $pass = getenv('QA_PASSWORD');

    if (getenv('PLATFORM_ENVIRONMENT_TYPE') === 'production') {
      $this->io()->error('This command cannot be run on production environments.');
      return;
    }

    if (empty($pass)) {
      $this->io()->error('QA Password environment variable not set.');
      return;
    }

    $qa_users = $entity_type_manager->getStorage('user')->loadByProperties(['roles' => 'qa']);

    foreach ($qa_users as $user) {
      $user->setPassword($pass);

      try {
        $user->save();
      }
      catch (\Exception $e) {
        $msg = t("Unable to update password for @username error: @error.", [
          '@username' => $user->label(),
          '@error' => $e->getMessage()
        ]);
        $this->io()->error($msg);
      }
    }

    $msg = t("Password for @cnt QA accounts updated.", ['@cnt' => count($qa_users)]);
    $this->io()->write($msg, TRUE);
  }

  /**
   * Drush command to create QA staff accounts.
   *
   * @command create_qa_staff_accounts
   */
  public function createQaStaffAccounts() {
    if (empty($env_email_string = getenv('QA_STAFF_ACCOUNTS'))) {
      $this->io()->error('QA_STAFF_ACCOUNTS variable not set.');
      return;
    }

    if (empty($role = getenv('QA_STAFF_ROLE'))) {
      $this->io()->error(t('QA_STAFF_ROLE variable not set.'));
      return;
    }

    // Check that the requested QA role exists on the site.
    $user_role = \Drupal::entityTypeManager()->getStorage('user_role')->load($role);

    if (empty($user_role)) {
      $this->io()->error(t('The QA_STAFF_ROLE role \'@role\', does not exist.', ['@role' => $role]));
      return;
    }

    // Generate array of entity reference field values if we need to assign
    // users to a Domain.
    if (\Drupal::service('module_handler')->moduleExists('domain')) {
      $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
      $domain_references = [];
      foreach ($domains as $domain) {
        $domain_references[] = ['target_id' => $domain->id()];
      }
    }

    $emails = explode(',', $env_email_string);
    $generator = new DefaultPasswordGenerator();
    $user_manager = \Drupal::entityTypeManager()->getStorage('user');

    foreach ($emails as $email) {
      $user_exists = $user_manager->loadByProperties([
        'mail' => $email,
      ]);

      if (!empty($user_exists)) {
        $this->io()->warning(t('Account already exists for @email', ['@email' => $email]));
        $this->io()->newLine();
        continue;
      }

      $user = User::create();
      $user->setPassword($generator->generate());
      $user->enforceIsNew();
      $user->setEmail($email);
      $user->setUsername($email);
      $user->addRole($role);

      if (!empty($domain_references)) {
        $user->field_domain_access = $domain_references;
        $user->field_domain_source[] = reset($domain_references);
      }

      $user->activate();

      if ($user->save()) {
        $this->io()->writeln(t('Account generated for @email', ['@email' => $email]));
      }
      else {
        $this->io()->writeln(t('Unable to generate account for @email', ['@email' => $email]));
      }
    }

  }

}
