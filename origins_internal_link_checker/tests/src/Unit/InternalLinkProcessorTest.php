<?php

namespace Drupal\Tests\origins_internal_link_checker\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\origins_internal_link_checker\InternalLinkProcessor;
use Drupal\origins_internal_link_checker\MigrationExecutionContext;
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
   * Tests that migration events rather than request paths suppress processing.
   *
   * @covers ::processEntity
   */
  public function testMigrationContextControlsProcessing(): void {
    $migration_context = new MigrationExecutionContext();
    $processor = $this->createProcessor([
      'site_url_list' => '',
      'site_url_list_exclude' => '',
    ], $migration_context, 'https://www.finance-ni.gov.uk/');

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('getFieldDefinitions')
      ->willReturn([]);
    $processor->processEntity($entity);

    $migration_context->onPreImport();
    $processor->processEntity($entity);
  }

  /**
   * Tests that every text item is updated without rebuilding its field.
   *
   * @covers ::processEntity
   * @covers ::processField
   */
  public function testProcessesEveryFieldItemInPlace(): void {
    $processor = $this->createProcessor([
      'site_url_list' => '',
      'site_url_list_exclude' => '',
    ]);

    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->method('getType')->willReturn('text_with_summary');

    $first_item = $this->createMock(FieldItemInterface::class);
    $first_values = [
      'value' => '<a href="https://finance-ni.gov.uk/one">One</a>',
      'format' => 'full_html',
      'summary' => 'First summary',
    ];
    $first_item->expects($this->once())
      ->method('set')
      ->with('value', '<a href="/one">One</a>');

    $second_item = $this->createMock(FieldItemInterface::class);
    $second_values = [
      'value' => '<a href="https://finance-ni.gov.uk/two">Two</a>',
      'format' => 'basic_html',
      'summary' => 'Second summary',
    ];
    $second_item->expects($this->once())
      ->method('set')
      ->with('value', '<a href="/two">Two</a>');

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getValue')->willReturn([$first_values, $second_values]);
    $items->expects($this->exactly(2))
      ->method('get')
      ->willReturnMap([
        [0, $first_item],
        [1, $second_item],
      ]);

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getFieldDefinitions')->willReturn(['body' => $definition]);
    $entity->expects($this->once())->method('get')->with('body')->willReturn($items);
    $entity->expects($this->never())->method('set');

    $processor->processEntity($entity);
  }

  /**
   * Tests parsed URL conversion for current and configured hosts.
   *
   * @covers ::convertText
   * @dataProvider conversionProvider
   */
  public function testConvertText(string $text, string $expected): void {
    $processor = $this->createProcessor([
      'site_url_list' => "https://old.finance-ni.gov.uk\nhttps://preview.finance-ni.gov.uk:8443",
      'site_url_list_exclude' => 'https://finance-ni.gov.uk/excluded',
    ]);

    $this->assertSame($expected, $processor->convertText($text));
  }

  /**
   * Provides internal, external, excluded, and malformed links.
   */
  public static function conversionProvider(): array {
    return [
      'current host' => [
        '<a href="https://finance-ni.gov.uk/news">News</a>',
        '<a href="/news">News</a>',
      ],
      'www and scheme are equivalent' => [
        "<a href = 'http://www.finance-ni.gov.uk/publications'>Publications</a>",
        "<a href = '/publications'>Publications</a>",
      ],
      'configured alias' => [
        '<a href="https://old.finance-ni.gov.uk/page">Page</a>',
        '<a href="/page">Page</a>',
      ],
      'configured non-default port' => [
        '<a href="https://preview.finance-ni.gov.uk:8443/page">Page</a>',
        '<a href="/page">Page</a>',
      ],
      'unconfigured non-default port' => [
        '<a href="https://finance-ni.gov.uk:8443/page">Page</a>',
        '<a href="https://finance-ni.gov.uk:8443/page">Page</a>',
      ],
      'root query and fragment' => [
        '<a href="https://finance-ni.gov.uk?one=two#main">Root</a>',
        '<a href="/?one=two#main">Root</a>',
      ],
      'excluded exact URL' => [
        '<a href="https://finance-ni.gov.uk/excluded">External service</a>',
        '<a href="https://finance-ni.gov.uk/excluded">External service</a>',
      ],
      'subdomain is not equivalent' => [
        '<a href="https://valuationservices.finance-ni.gov.uk/page">Valuation</a>',
        '<a href="https://valuationservices.finance-ni.gov.uk/page">Valuation</a>',
      ],
      'external URL' => [
        '<a href="https://example.com/page">Example</a>',
        '<a href="https://example.com/page">Example</a>',
      ],
      'userinfo URL' => [
        '<a href="https://finance-ni.gov.uk@example.com/page">Example</a>',
        '<a href="https://finance-ni.gov.uk@example.com/page">Example</a>',
      ],
      'only href attributes change' => [
        '<span data-url="https://finance-ni.gov.uk/page">Text</span>',
        '<span data-url="https://finance-ni.gov.uk/page">Text</span>',
      ],
      'similar host cannot consume an earlier link' => [
        '<a href="https://oldXfinance-niXgovXuk/wrong">Wrong</a><a href="https://old.finance-ni.gov.uk/right">Right</a>',
        '<a href="https://oldXfinance-niXgovXuk/wrong">Wrong</a><a href="/right">Right</a>',
      ],
    ];
  }

  /**
   * Creates a processor with a real request and mocked configuration.
   */
  private function createProcessor(
    array $values,
    ?MigrationExecutionContext $migration_context = NULL,
    string $request_url = 'https://www.finance-ni.gov.uk/admin/content',
  ): InternalLinkProcessor {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnCallback(static fn(string $key) => $values[$key] ?? NULL);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('origins_internal_link_checker.linksettings')
      ->willReturn($config);

    $request_stack = new RequestStack();
    $request_stack->push(Request::create($request_url));

    return new InternalLinkProcessor(
      $config_factory,
      $request_stack,
      $migration_context ?? new MigrationExecutionContext(),
    );
  }

}
