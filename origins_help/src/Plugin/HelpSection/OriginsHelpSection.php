<?php

declare(strict_types=1);

namespace Drupal\origins_help\Plugin\HelpSection;

use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\help\Attribute\HelpSection;
use Drupal\help\Plugin\HelpSection\HelpSectionPluginBase;

/**
 * Adds a link to the Origins Help page on Core's help page.
 */
#[HelpSection(
  id: 'origins_help',
  title: new TranslatableMarkup('Site help'),
  description: new TranslatableMarkup('Help for non-administrator users is available on the Origins Help page:'),
  weight: -20,
)]
final class OriginsHelpSection extends HelpSectionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function listTopics() {
    return [
      Link::createFromRoute($this->t('Site help page'), 'origins_help.help'),
    ];
  }

}
