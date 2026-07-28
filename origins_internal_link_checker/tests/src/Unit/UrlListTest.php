<?php

namespace Drupal\Tests\origins_internal_link_checker\Unit;

use Drupal\origins_internal_link_checker\UrlList;
use Drupal\Tests\UnitTestCase;

/**
 * Tests configured URL-list validation and normalisation.
 *
 * @coversDefaultClass \Drupal\origins_internal_link_checker\UrlList
 * @group origins_internal_link_checker
 */
class UrlListTest extends UnitTestCase {

  /**
   * Tests whitespace and empty-line normalisation.
   *
   * @covers ::lines
   * @covers ::normalise
   */
  public function testNormalise(): void {
    $value = " https://one.example.gov.uk \r\n\r\nhttps://two.example.gov.uk\t";
    $this->assertSame(
      "https://one.example.gov.uk\nhttps://two.example.gov.uk",
      UrlList::normalise($value),
    );
  }

  /**
   * Tests that domain aliases contain an authority but no content path.
   *
   * @covers ::invalidUrls
   */
  public function testDomainAliasValidation(): void {
    $value = implode("\n", [
      'https://valid.example.gov.uk',
      'http://valid.example.gov.uk:8080/',
      'ftp://wrong-scheme.example.gov.uk',
      'https://example.gov.uk/content',
      'https://example.gov.uk?preview=1',
      'not a URL',
    ]);

    $this->assertSame([
      'ftp://wrong-scheme.example.gov.uk',
      'https://example.gov.uk/content',
      'https://example.gov.uk?preview=1',
      'not a URL',
    ], UrlList::invalidUrls($value, FALSE));
  }

  /**
   * Tests that exclusion entries may identify a complete internal URL.
   *
   * @covers ::invalidUrls
   */
  public function testExclusionValidation(): void {
    $value = implode("\n", [
      'https://example.gov.uk/content?preview=1#main',
      'https://user@example.gov.uk/content',
    ]);

    $this->assertSame([
      'https://user@example.gov.uk/content',
    ], UrlList::invalidUrls($value, TRUE));
  }

}
