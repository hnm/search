<?php
namespace search\html\model;

use PHPUnit\Framework\TestCase;
use search\html\bo\HtmlScan;

class HtmlScannerTest extends TestCase {

	/**
	 * Test basic HTML scan with title and meta description.
	 */
	public function testBasicHtmlScan() {
		$html = '<html><head><title>Test Title</title><meta name="description" content="Test Description"><meta name="keywords" content="keyword1;keyword2"></head><body><p>Hello, world!</p></body></html>';
		$result = HtmlScanner::scan($html);

		$this->assertInstanceOf(HtmlScan::class, $result);
		$this->assertEquals('Test Title', $result->getTitle());
		$this->assertEquals('Test Description', $result->getDescription());
		$this->assertEquals('keyword1;keyword2', $result->getKeywordsStr());
	}

	/**
	 * Test HTML scan with empty HTML input.
	 */
	public function testEmptyHtmlScan() {
		$html = '';
		$result = HtmlScanner::scan($html);

		$this->assertInstanceOf(HtmlScan::class, $result);
		$this->assertNull($result->getTitle());
		$this->assertNull($result->getDescription());
	}

	/**
	 * Test HTML scan with no meta tags or title.
	 */
	public function testHtmlScanNoMeta() {
		$html = '<html><head></head><body><p>Hello, world!</p></body></html>';
		$result = HtmlScanner::scan($html);

		$this->assertInstanceOf(HtmlScan::class, $result);
		$this->assertNull($result->getTitle());
		$this->assertNull($result->getDescription());
	}

	/**
	 * Test HTML scan with comments.
	 */
	public function testHtmlScanWithComments() {
		$html = '<html><head><!-- Comment --><title>Test Title</title></head><body><!-- Comment --><p>Hello, world!</p><!-- Comment --></body></html>';
		$result = HtmlScanner::scan($html);

		$this->assertInstanceOf(HtmlScan::class, $result);
		$this->assertEquals('Test Title', $result->getTitle());
		// Verify that comments are not included
		$this->assertStringNotContainsString('<!-- Comment -->', $result->getTitle());
	}
}