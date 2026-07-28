<?php

namespace Drupal\Tests\origins_internal_link_checker\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\origins_internal_link_checker\InternalLinkProcessor;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests internal link processing.
 *
 * @coversDefaultClass \Drupal\origins_internal_link_checker\InternalLinkProcessor
 * @group origins_internal_link_checker
 */
class InternalLinkProcessorTest extends UnitTestCase {

  /**
   * Tests conversion for the current host and a configured alias.
   *
   * @covers ::convertAbsoluteLink
   * @covers ::urlsToReplace
   */
  public function testConvertAbsoluteLink(): void {
    $processor = $this->createProcessor([
      'site_url_list' => 'https://old.finance-ni.gov.uk',
      'site_url_list_exclude' => '',
    ]);

    $current = '<a href="https://finance-ni.gov.uk/news">News</a>';
    $alias = '<a href="http://www.old.finance-ni.gov.uk/publications">Publications</a>';

    $this->assertSame(
      '<a href="/news">News</a>',
      $processor->convertAbsoluteLink($current, 'https://finance-ni.gov.uk/news'),
    );
    $this->assertSame(
      '<a href="/publications">Publications</a>',
      $processor->convertAbsoluteLink($alias, 'http://www.old.finance-ni.gov.uk/publications'),
    );
  }

  /**
   * Creates a processor with a real request and mocked configuration.
   */
  private function createProcessor(array $values): InternalLinkProcessor {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnCallback(static fn(string $key) => $values[$key] ?? NULL);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('origins_internal_link_checker.linksettings')
      ->willReturn($config);

    $request_stack = new RequestStack();
    $request_stack->push(Request::create('https://www.finance-ni.gov.uk/admin/content'));

    return new InternalLinkProcessor($config_factory, $request_stack);
  }

}
