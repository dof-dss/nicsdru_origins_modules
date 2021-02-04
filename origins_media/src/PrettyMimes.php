<?php

namespace Drupal\origins_media;

class PrettyMimes {

  /**
   * Convenience function to expose as service to avoid repetition
   * between preprocess functions and classes.
   *
   * @return string[]
   *   The keyed array of raw mime type keys and easier to read values.
   */
  public static function getMimeTypes() {
    $mimeTypes = [
      'application/pdf' => 'Adobe PDF',
      'application/msword' => 'Microsoft Word',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Microsoft Word',
      'application/vnd.ms-excel' => 'Microsoft Excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Microsoft Excel',
      'application/vnd.ms-excel.sheet.macroEnabled.12' => 'Microsoft Excel (macros enabled)',
      'application/vnd.ms-powerpoint' => 'Microsoft Powerpoint',
      'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'Microsoft Powerpoint',
      'application/vnd.oasis.opendocument.text' => 'OpenDocument text',
      'application/vnd.oasis.opendocument.spreadsheet' => 'OpenDocument spreadsheet',
      'application/vnd.oasis.opendocument.presentation' => 'OpenDocument presentation',
      'application/zip' => 'ZIP archive',
      'text/csv' => 'Comma-separated values',
      'text/html' => 'HTML (HyperText Markup Language)',
    ];

    return $mimeTypes;
  }

}
