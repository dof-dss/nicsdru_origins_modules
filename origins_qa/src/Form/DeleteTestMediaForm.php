<?php

declare(strict_types=1);

namespace Drupal\origins_qa\Form;

/**
 * Confirms and deletes media entities created by automated tests.
 */
final class DeleteTestMediaForm extends DeleteTestEntitiesFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetEntityTypeId(): string {
    return 'media';
  }

  /**
   * {@inheritdoc}
   */
  protected function getLabelPrefix(): string {
    return self::MEDIA_TITLE_PREFIX;
  }

}
