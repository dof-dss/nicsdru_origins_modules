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
    $value = " https://one.example.com \r\n\r\nhttps://two.example.com\t";
    $this->assertSame(
      "https://one.example.com\nhttps://two.example.com",
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
      'https://valid.example.com',
      'http://valid.example.com:8080/',
      'ftp://wrong-scheme.example.com',
      'https://example.com/content',
      'https://example.com?preview=1',
      'not a URL',
    ]);

    $this->assertSame([
      'ftp://wrong-scheme.example.com',
      'https://example.com/content',
      'https://example.com?preview=1',
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
      'https://example.com/content?preview=1#main',
      'https://user@example.com/content',
    ]);

    $this->assertSame([
      'https://user@example.com/content',
    ], UrlList::invalidUrls($value, TRUE));
  }

}
