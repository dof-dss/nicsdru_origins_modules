<?php

declare(strict_types=1);

namespace Drupal\origins_qa\Form;

/**
 * Confirms and deletes nodes created by automated tests.
 */
final class DeleteTestNodesForm extends DeleteTestEntitiesFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetEntityTypeId(): string {
    return 'node';
  }

  /**
   * {@inheritdoc}
   */
  protected function getLabelPrefix(): string {
    return self::NODE_TITLE_PREFIX;
  }

}
