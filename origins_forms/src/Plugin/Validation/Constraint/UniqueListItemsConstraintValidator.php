<?php

declare(strict_types=1);

namespace Drupal\origins_forms\Plugin\Validation\Constraint;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItemInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Unique List Items constraint.
 */
final class UniqueListItemsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $items, Constraint $constraint): void {

    if (!$items instanceof FieldItemListInterface) {
      throw new \InvalidArgumentException(
        sprintf('The validated value must be instance of \Drupal\Core\Field\FieldItemListInterface, %s was given.', get_debug_type($items))
      );
    }

    if (!$items->isEmpty()) {
      $item_values_processed = [];

      if ($items instanceof EntityReferenceFieldItemList) {
        $key = 'target_id';
      }
      else {
        $key = 'value';
      }

      foreach ($items as $delta => $item) {
        $value = $item->getValue();

        if (in_array($value[$key], $item_values_processed)) {
          // @phpstan-ignore-next-line
          $this->context->buildViolation($constraint->message)
            ->atPath((string) $delta . '.' . $key)
            ->addViolation();
        }
        else {
          $item_values_processed[] = $value[$key];
        }
      }
    }
  }

}
