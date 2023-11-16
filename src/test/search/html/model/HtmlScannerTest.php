<?php
namespace search\html\model;

use PHPUnit\Framework\TestCase;
use search\html\bo\HtmlScan;
use search\model\Indexer;

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

	public function testHtmlScanWithMultipleTitles() {
		$html = '<html><head><!-- Comment --><title>Test Title</title></head><body><!-- Comment --><p>Hello, world!</p><!-- Comment --><title>Test Title 2</title></body></html>';
		$result = HtmlScanner::scan($html);

		$this->assertInstanceOf(HtmlScan::class, $result);
		$this->assertEquals('Test Title 2', $result->getTitle());
		// Verify that comments are not included
		$this->assertStringNotContainsString('<!-- Comment -->', $result->getTitle());
	}

	/**
	 * Test HTML scan with multiple titles search excluded.
	 */
	public function testHtmlScanWithMultipleTitlesSearchExcluded() {
		$html = '<html><head><!-- Comment --><title><!-- Comment -->Test Title</title></head><body><!-- Comment -->
				<p>Hello, world!</p><!-- Comment -->
				<title data-search="' . Indexer::SEARCH_EXCLUDE . '">Test Title 2</title></body></html>';

		$result = HtmlScanner::scan($html);

		$this->assertInstanceOf(HtmlScan::class, $result);
		$this->assertEquals('Test Title', $result->getTitle());
		// Verify that comments are not included
		$this->assertStringNotContainsString('<!-- Comment -->', $result->getTitle());
	}

	/**
	 * Test HTML scan with multiple titles search excluded.
	 */
	public function testHtmlScanWithMultipleTitlesSearchIncluded() {
		$html = '<html><head><!-- Comment --><title data-search="excluded">Test Title</title></head><body><!-- Comment -->
				<p>Hello, world!</p><!-- Comment -->
				<div data-search="excluded"><span data-search="included"><title><!-- Comment -->Test Title 2</title></span></div></body></html>';

		$result = HtmlScanner::scan($html);

		$this->assertInstanceOf(HtmlScan::class, $result);
		$this->assertEquals('Test Title 2', $result->getTitle());
		// Verify that comments are not included
		$this->assertStringNotContainsString('<!-- Comment -->', $result->getTitle());
	}

	/**
	 * Test HTML scan with multiple titles search excluded.
	 */
	public function testHtmlScanWithMultipleTitlesExcluded() {
		$html = '<html><head><!-- Comment --><title data-search="excluded">Test Title</title></head><body><!-- Comment -->
				<p>Hello, world!</p>
				<div data-search="excluded"><title><!-- Comment -->Test Title 2</title></div></body></html>';

		$result = HtmlScanner::scan($html);

		$this->assertInstanceOf(HtmlScan::class, $result);
		$this->assertNull($result->getTitle());
	}

	/**
	 * Test complex nested included excluded elements
	 */
	public function testComplexExcludedIncludedStructure() {
		$html = '<html>
					<head><!-- Comment -->
						<title data-search="excluded">EXCLUDED</title>
					</head>
					<body>
						<div>
							<span data-search="excluded"></span><!-- Comment -->
						<div>
						<span data-search="included">
							<title>INCLUDED TITLE</title>
						</span>
						</div>
					<footer class="page-footer-included">
						<div class="div-excluded">INCLUDED</div>
						<div class="div-included" data-search="excluded">EXCLUDED</div>
					</footer>
					<div data-search="excluded">EXCLUDED</title></div></body>
				</html>';

		$result = HtmlScanner::scan($html);

		$this->assertInstanceOf(HtmlScan::class, $result);

		$this->assertEquals('INCLUDED TITLE INCLUDED', $result->getSearchableStr());
	}

	public function testFenacoTitle() {
		$html = '<html class="js hnm-animate" lang="de">
		<head>
			<title>AGROLINE schützt ihre Kulturen nachhaltig</title>
			<meta name="description" content="Nachhaltiger Pflanzenschutz ist komplex: Dabei müssen Bodenbearbeitung, Düngung und Fruchtfolge mit ganzheitlichen Ansätzen zur Bekämpfung von Krankheiten, Schädlingen und Unkraut kombiniert und perfekt aufeinander abgestimmt werden. Kontaktieren Sie Ihren AGROLINE Berater!">
			<meta name="author" content="AGROLINE">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<meta http-equiv="x-ua-compatible" content="ie=edge">
			<meta name="msapplication-TileImage" content="/assets/bstmpl/img/tile-558x558.png">
			<meta name="msapplication-TileImage" content="/assets/bstmpl/img/tile-wide-558x270.png">
			<meta name="msapplication-TileColor" content="#fff">
			<link rel="manifest" href="/assets/bstmpl/json/site.webmanifest">
			<link rel="icon" type="image/x-icon" href="/assets/bstmpl/img/favicon.png">
			<link rel="apple-touch-icon" href="/assets/bstmpl/img/icon-192x192.png">
			<link rel="alternate" hreflang="de" href="https://www.agroline.ch">
			<link rel="alternate" hreflang="fr" href="https://www.agroline.ch/fr">
		</head>
	<body style="">
		<svg aria-hidden="true" style="position: absolute; width: 0; height: 0; overflow: hidden;" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
<defs>
<symbol id="svg-angle-down-solid" viewBox="0 0 32 32">
<path d="M4.219 10.781l-1.438 1.438 12.5 12.5 0.719 0.688 0.719-0.688 12.5-12.5-1.438-1.438-11.781 11.781z"></path>
</symbol>
<symbol id="svg-angle-right-solid" viewBox="0 0 32 32">
<path d="M12.969 4.281l-1.438 1.438 10.281 10.281-10.281 10.281 1.438 1.438 11-11 0.688-0.719-0.688-0.719z"></path>
</symbol>
<symbol id="svg-angle-up-solid" viewBox="0 0 32 32">
<path d="M16 6.594l-0.719 0.688-12.5 12.5 1.438 1.438 11.781-11.781 11.781 11.781 1.438-1.438-12.5-12.5z"></path>
</symbol>
<symbol id="svg-arrow-right-solid" viewBox="0 0 32 32">
<path d="M18.719 6.781l-1.438 1.438 6.781 6.781h-20.063v2h20.063l-6.781 6.781 1.438 1.438 8.5-8.5 0.688-0.719-0.688-0.719z"></path>
</symbol>
<symbol id="svg-cart-arrow-down-solid" viewBox="0 0 32 32">
<path d="M4 7c-0.551 0-1 0.449-1 1s0.449 1 1 1h2.219l2.625 10.5c0.223 0.891 1.020 1.5 1.938 1.5h12.469c0.902 0 1.668-0.598 1.906-1.469l2.594-9.531h-2.094l-2.406 9h-12.469l-2.625-10.5c-0.223-0.891-1.020-1.5-1.938-1.5zM22 21c-1.645 0-3 1.355-3 3s1.355 3 3 3c1.645 0 3-1.355 3-3s-1.355-3-3-3zM13 21c-1.645 0-3 1.355-3 3s1.355 3 3 3c1.645 0 3-1.355 3-3s-1.355-3-3-3zM16 7v5h-3l4 4 4-4h-3v-5zM13 23c0.563 0 1 0.438 1 1s-0.438 1-1 1c-0.563 0-1-0.438-1-1s0.438-1 1-1zM22 23c0.563 0 1 0.438 1 1s-0.438 1-1 1c-0.563 0-1-0.438-1-1s0.438-1 1-1z"></path>
</symbol>
<symbol id="svg-cart-plus-solid" viewBox="0 0 32 32">
<path d="M4 7c-0.551 0-1 0.449-1 1s0.449 1 1 1h2.219l2.625 10.5c0.223 0.891 1.020 1.5 1.938 1.5h12.469c0.902 0 1.668-0.598 1.906-1.469l2.594-9.531h-2.094l-2.406 9h-12.469l-2.625-10.5c-0.223-0.891-1.020-1.5-1.938-1.5zM22 21c-1.645 0-3 1.355-3 3s1.355 3 3 3c1.645 0 3-1.355 3-3s-1.355-3-3-3zM13 21c-1.645 0-3 1.355-3 3s1.355 3 3 3c1.645 0 3-1.355 3-3s-1.355-3-3-3zM16 7v3h-3v2h3v3h2v-3h3v-2h-3v-3zM13 23c0.563 0 1 0.438 1 1s-0.438 1-1 1c-0.563 0-1-0.438-1-1s0.438-1 1-1zM22 23c0.563 0 1 0.438 1 1s-0.438 1-1 1c-0.563 0-1-0.438-1-1s0.438-1 1-1z"></path>
</symbol>
<symbol id="svg-download-solid" viewBox="0 0 32 32">
<path d="M15 4v16.563l-5.281-5.281-1.438 1.438 7 7 0.719 0.688 0.719-0.688 7-7-1.438-1.438-5.281 5.281v-16.563zM7 26v2h18v-2z"></path>
</symbol>
<symbol id="svg-file-pdf" viewBox="0 0 32 32">
<path d="M6 3v26h20v-26zM8 5h16v22h-16zM15.406 10.344c-0.305-0.004-0.66 0.105-0.906 0.313-0.254 0.215-0.367 0.48-0.438 0.75-0.137 0.539-0.098 1.098 0.031 1.719 0.152 0.727 0.586 1.602 0.938 2.438-0.18 0.762-0.227 1.438-0.5 2.219-0.234 0.672-0.535 1.059-0.813 1.656-0.629 0.238-1.379 0.379-1.875 0.688-0.535 0.332-1.004 0.699-1.281 1.219s-0.246 1.254 0.125 1.781c0.184 0.277 0.426 0.496 0.75 0.625s0.676 0.133 0.969 0.031c0.59-0.203 1.008-0.656 1.406-1.188 0.371-0.492 0.633-1.328 0.969-2 0.504-0.168 0.867-0.379 1.406-0.5 0.563-0.125 0.941-0.066 1.469-0.125 0.227 0.258 0.418 0.672 0.656 0.875 0.477 0.414 1 0.742 1.625 0.781s1.25-0.352 1.594-0.938h0.031v-0.031c0.152-0.266 0.258-0.555 0.25-0.875s-0.168-0.656-0.375-0.875c-0.41-0.438-0.934-0.551-1.5-0.625-0.438-0.059-1.047 0.098-1.563 0.125-0.453-0.598-0.902-1.047-1.313-1.813-0.223-0.414-0.281-0.766-0.469-1.188 0.145-0.68 0.43-1.438 0.469-2.031 0.047-0.719 0.020-1.34-0.188-1.906-0.105-0.285-0.273-0.566-0.531-0.781-0.25-0.207-0.574-0.336-0.906-0.344-0.012 0-0.020 0-0.031 0zM16.063 17.75c0.18 0.316 0.402 0.516 0.594 0.813-0.281 0.051-0.496 0-0.781 0.063-0.047 0.012-0.078 0.051-0.125 0.063 0.059-0.156 0.133-0.25 0.188-0.406 0.063-0.184 0.066-0.348 0.125-0.531zM19.75 19.781c0.336 0.043 0.457 0.105 0.5 0.125-0.008 0.016 0.012 0.012 0 0.031-0.125 0.207-0.137 0.191-0.219 0.188-0.066-0.004-0.32-0.141-0.563-0.313 0.070 0.004 0.219-0.039 0.281-0.031zM12.75 21.344c-0.055 0.082-0.102 0.273-0.156 0.344-0.305 0.406-0.586 0.594-0.656 0.625-0.012-0.016 0.020 0 0-0.031h-0.031c-0.102-0.145-0.074-0.086 0-0.219s0.309-0.402 0.719-0.656c0.031-0.020 0.094-0.043 0.125-0.063z"></path>
</symbol>
<symbol id="svg-info-circle-solid" viewBox="0 0 32 32">
<path d="M16 3c-7.168 0-13 5.832-13 13s5.832 13 13 13c7.168 0 13-5.832 13-13s-5.832-13-13-13zM16 5c6.086 0 11 4.914 11 11s-4.914 11-11 11c-6.086 0-11-4.914-11-11s4.914-11 11-11zM15 10v2h2v-2zM15 14v8h2v-8z"></path>
</symbol>
<symbol id="svg-long-arrow-alt-right-solid" viewBox="0 0 32 32">
<path d="M21.188 9.281l-1.406 1.438 4.281 4.281h-20.063v2h20.063l-4.281 4.281 1.406 1.438 6.719-6.719z"></path>
</symbol>
<symbol id="svg-minus-solid" viewBox="0 0 32 32">
<path d="M5 15v2h22v-2z"></path>
</symbol>
<symbol id="svg-plus-solid" viewBox="0 0 32 32">
<path d="M15 5v10h-10v2h10v10h2v-10h10v-2h-10v-10z"></path>
</symbol>
<symbol id="svg-search-solid" viewBox="0 0 32 32">
<path d="M19 3c-5.512 0-10 4.488-10 10 0 2.395 0.84 4.59 2.25 6.313l-7.969 7.969 1.438 1.438 7.969-7.969c1.723 1.41 3.918 2.25 6.313 2.25 5.512 0 10-4.488 10-10s-4.488-10-10-10zM19 5c4.43 0 8 3.57 8 8s-3.57 8-8 8c-4.43 0-8-3.57-8-8s3.57-8 8-8z"></path>
</symbol>
<symbol id="svg-shopping-bag-solid" viewBox="0 0 32 32">
<path d="M16 3c-2.746 0-5 2.254-5 5v1h-4.938l-0.063 0.938-1 18-0.063 1.063h22.125l-0.063-1.063-1-18-0.063-0.938h-4.938v-1c0-2.746-2.254-5-5-5zM16 5c1.656 0 3 1.344 3 3v1h-6v-1c0-1.656 1.344-3 3-3zM7.938 11h3.063v3h2v-3h6v3h2v-3h3.063l0.875 16h-17.875z"></path>
</symbol>
<symbol id="svg-shopping-cart-solid" viewBox="0 0 32 32">
<path d="M5 7c-0.551 0-1 0.449-1 1s0.449 1 1 1h2.219l2.625 10.5c0.223 0.891 1.020 1.5 1.938 1.5h11.469c0.902 0 1.668-0.598 1.906-1.469l2.594-9.531h-16.75l0.5 2h13.656l-1.906 7h-11.469l-2.625-10.5c-0.223-0.891-1.020-1.5-1.938-1.5zM22 21c-1.645 0-3 1.355-3 3s1.355 3 3 3c1.645 0 3-1.355 3-3s-1.355-3-3-3zM13 21c-1.645 0-3 1.355-3 3s1.355 3 3 3c1.645 0 3-1.355 3-3s-1.355-3-3-3zM13 23c0.563 0 1 0.438 1 1s-0.438 1-1 1c-0.563 0-1-0.438-1-1s0.438-1 1-1zM22 23c0.563 0 1 0.438 1 1s-0.438 1-1 1c-0.563 0-1-0.438-1-1s0.438-1 1-1z"></path>
</symbol>
<symbol id="svg-times-circle" viewBox="0 0 32 32">
<path d="M16 3c-7.168 0-13 5.832-13 13s5.832 13 13 13c7.168 0 13-5.832 13-13s-5.832-13-13-13zM16 5c6.086 0 11 4.914 11 11s-4.914 11-11 11c-6.086 0-11-4.914-11-11s4.914-11 11-11zM12.219 10.781l-1.438 1.438 3.781 3.781-3.781 3.781 1.438 1.438 3.781-3.781 3.781 3.781 1.438-1.438-3.781-3.781 3.781-3.781-1.438-1.438-3.781 3.781z"></path>
</symbol>
<symbol id="svg-times-solid" viewBox="0 0 32 32">
<path d="M7.219 5.781l-1.438 1.438 8.781 8.781-8.781 8.781 1.438 1.438 8.781-8.781 8.781 8.781 1.438-1.438-8.781-8.781 8.781-8.781-1.438-1.438-8.781 8.781z"></path>
</symbol>
<symbol id="svg-trash-solid" viewBox="0 0 32 32">
<path d="M14 4c-0.523 0-1.059 0.184-1.438 0.563s-0.563 0.914-0.563 1.438v1h-7v2h1.094l1.906 18.094 0.094 0.906h15.813l0.094-0.906 1.906-18.094h1.094v-2h-7v-1c0-0.523-0.184-1.059-0.563-1.438s-0.914-0.563-1.438-0.563zM14 6h4v1h-4zM8.125 9h15.75l-1.781 17h-12.188zM12 12v11h2v-11zM15 12v11h2v-11zM18 12v11h2v-11z"></path>
</symbol>
</defs>
</svg>
		<ul class="skiplinks" data-search="excluded">
							<li>
					<a href="/" accesskey="0">Zur Startseite</a>				</li>
						<li><a href="#globalnavi" accesskey="1">Zur Navigation springen</a></li>
			<li><a href="#main-container" accesskey="2">Zum Inhalt springen</a></li>
						<li><a href="#search" accesskey="5">Zur Suche springen</a></li>
		</ul>
		
		<header class="al-header-content-holder page-header" data-search="excluded">
	<div class="page-header-main">
		<div class="container-fluid">
			<div class="row align-items-center no-gutters">
				<div id="branding" class="col col-lg-3 pl-0">
										<a href="/" class="branding-img" title="AGROLINE" target="_top">						<!-- Generator: Adobe Illustrator 24.1.3, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<svg version="1.1" id="Logo" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 135 80" style="enable-background:new 0 0 135 80;" width="135" height="80" xml:space="preserve">
<style type="text/css">
	.st0{fill:#FFFFFF;}
</style>
<g>
	<g id="Zusatz">
		<path class="st0" d="M8.7,71.7c0-0.7,0.6-1.3,2-1.3c0.7,0,1.5,0.2,2.2,0.7l0.4-0.9c-0.7-0.5-1.7-0.7-2.6-0.7
			c-2.2,0-3.1,1.1-3.1,2.3c0,3,5,1.8,5,3.8c0,0.7-0.6,1.3-2,1.3c-1.1,0-2.1-0.4-2.7-0.9l-0.4,0.9c0.7,0.6,1.9,1,3.1,1
			c2.2,0,3.2-1.1,3.2-2.3C13.7,72.5,8.7,73.7,8.7,71.7z"></path>
		<path class="st0" d="M17.9,71.5c-1.8,0-3.1,1.3-3.1,3.2c0,1.8,1.3,3.2,3.3,3.2c1,0,1.9-0.4,2.4-1l-0.6-0.7
			c-0.5,0.5-1.1,0.7-1.8,0.7c-1.2,0-2.1-0.7-2.2-1.8h5c0-0.1,0-0.2,0-0.3C21,72.8,19.7,71.5,17.9,71.5z M15.9,74.2
			c0.1-1.1,0.9-1.8,2-1.8c1.1,0,1.9,0.7,2,1.8H15.9z"></path>
		<path class="st0" d="M23.5,72.6v-1h-1.1v6.2h1.1v-3.1c0-1.4,0.7-2.1,2-2.1c0.1,0,0.2,0,0.3,0v-1.1C24.7,71.5,23.9,71.9,23.5,72.6z
			"></path>
		<polygon class="st0" points="29.9,76.5 27.8,71.5 26.6,71.5 29.3,77.7 30.4,77.7 33.2,71.5 32.1,71.5 		"></polygon>
		<rect x="34.3" y="71.5" class="st0" width="1.1" height="6.2"></rect>
		<path class="st0" d="M34.8,68.9c-0.4,0-0.8,0.3-0.8,0.7c0,0.4,0.3,0.7,0.8,0.7c0.4,0,0.8-0.3,0.8-0.7
			C35.6,69.2,35.2,68.9,34.8,68.9z"></path>
		<path class="st0" d="M40.1,72.5c0.7,0,1.3,0.3,1.7,0.9l0.9-0.5c-0.5-0.9-1.4-1.3-2.5-1.3c-1.9,0-3.3,1.3-3.3,3.2
			c0,1.8,1.4,3.2,3.3,3.2c1.1,0,2-0.5,2.5-1.3l-0.9-0.5c-0.4,0.6-1,0.9-1.7,0.9c-1.2,0-2.1-0.8-2.1-2.2C38,73.3,38.9,72.5,40.1,72.5
			z"></path>
		<path class="st0" d="M46.6,71.5c-1.8,0-3.1,1.3-3.1,3.2c0,1.8,1.3,3.2,3.3,3.2c1,0,1.9-0.4,2.4-1l-0.6-0.7
			c-0.5,0.5-1.1,0.7-1.8,0.7c-1.2,0-2.1-0.7-2.2-1.8h5c0-0.1,0-0.2,0-0.3C49.7,72.8,48.4,71.5,46.6,71.5z M44.6,74.2
			c0.1-1.1,0.9-1.8,2-1.8c1.1,0,1.9,0.7,2,1.8H44.6z"></path>
		<path class="st0" d="M61.2,74.1l-0.9-0.3c-0.1,0.6-0.3,1.1-0.5,1.5l-2-2c1.3-0.7,1.7-1.3,1.7-2.1c0-1.1-0.8-1.7-2-1.7
			c-1.3,0-2.2,0.7-2.2,1.8c0,0.6,0.2,1.1,1,1.9c-1.4,0.8-2,1.5-2,2.5c0,1.3,1.2,2.1,2.9,2.1c1.1,0,2-0.4,2.7-1.1l1.1,1.1l0.6-0.7
			L60.5,76C60.8,75.5,61.1,74.9,61.2,74.1z M57.5,70.3c0.7,0,1.1,0.4,1.1,0.9c0,0.5-0.4,0.9-1.4,1.5c-0.7-0.7-0.8-1-0.8-1.4
			C56.3,70.7,56.8,70.3,57.5,70.3z M57.2,76.9c-1.1,0-1.8-0.5-1.8-1.3c0-0.7,0.4-1.1,1.5-1.8l2.3,2.3C58.7,76.6,58,76.9,57.2,76.9z"></path>
		<path class="st0" d="M72.1,73.5c0.6-0.3,1.1-0.9,1.1-1.8c0-1.3-1.1-2.1-2.9-2.1h-3.7v8.2h3.9c2.1,0,3.1-0.8,3.1-2.2
			C73.7,74.4,73,73.7,72.1,73.5z M67.8,70.5h2.4c1.2,0,1.9,0.4,1.9,1.3c0,0.9-0.7,1.3-1.9,1.3h-2.4V70.5z M70.5,76.8h-2.7v-2.7h2.7
			c1.3,0,2,0.4,2,1.4C72.5,76.4,71.8,76.8,70.5,76.8z"></path>
		<path class="st0" d="M75.9,68.9c-0.4,0-0.8,0.3-0.8,0.7c0,0.4,0.3,0.7,0.8,0.7c0.4,0,0.8-0.3,0.8-0.7
			C76.6,69.2,76.3,68.9,75.9,68.9z"></path>
		<rect x="75.3" y="71.5" class="st0" width="1.1" height="6.2"></rect>
		<path class="st0" d="M81.2,71.5c-1.9,0-3.2,1.3-3.2,3.2c0,1.8,1.4,3.2,3.2,3.2c1.9,0,3.2-1.3,3.2-3.2C84.4,72.8,83,71.5,81.2,71.5
			z M81.2,76.8c-1.2,0-2.1-0.9-2.1-2.2s0.9-2.2,2.1-2.2c1.2,0,2.1,0.9,2.1,2.2S82.4,76.8,81.2,76.8z"></path>
		<path class="st0" d="M89.2,71.5c-0.9,0-1.7,0.4-2.2,1v-1h-1.1V80H87v-3.2c0.5,0.7,1.3,1,2.2,1c1.8,0,3.1-1.3,3.1-3.2
			C92.3,72.7,91,71.5,89.2,71.5z M89.1,76.8c-1.2,0-2.1-0.9-2.1-2.2c0-1.3,0.9-2.2,2.1-2.2c1.2,0,2.1,0.9,2.1,2.2
			C91.2,76,90.3,76.8,89.1,76.8z"></path>
		<path class="st0" d="M95,72.6v-1h-1.1v6.2H95v-3.1c0-1.4,0.7-2.1,2-2.1c0.1,0,0.2,0,0.3,0v-1.1C96.1,71.5,95.4,71.9,95,72.6z"></path>
		<path class="st0" d="M101.2,71.5c-1.9,0-3.2,1.3-3.2,3.2c0,1.8,1.4,3.2,3.2,3.2c1.9,0,3.2-1.3,3.2-3.2
			C104.4,72.8,103,71.5,101.2,71.5z M101.2,76.8c-1.2,0-2.1-0.9-2.1-2.2s0.9-2.2,2.1-2.2c1.2,0,2.1,0.9,2.1,2.2
			S102.3,76.8,101.2,76.8z"></path>
		<path class="st0" d="M108.1,76.9c-0.6,0-1-0.4-1-1v-3.4h1.8v-0.9h-1.8v-1.4H106v1.4h-1.1v0.9h1.1v3.4c0,1.3,0.7,1.9,2,1.9
			c0.5,0,1-0.1,1.4-0.4l-0.4-0.8C108.7,76.8,108.4,76.9,108.1,76.9z"></path>
		<path class="st0" d="M113,71.5c-1.8,0-3.1,1.3-3.1,3.2c0,1.8,1.3,3.2,3.3,3.2c1,0,1.9-0.4,2.4-1l-0.6-0.7
			c-0.5,0.5-1.1,0.7-1.8,0.7c-1.2,0-2.1-0.7-2.2-1.8h5c0-0.1,0-0.2,0-0.3C116,72.8,114.7,71.5,113,71.5z M111,74.2
			c0.1-1.1,0.9-1.8,2-1.8c1.1,0,1.9,0.7,2,1.8H111z"></path>
		<path class="st0" d="M120.4,72.5c0.7,0,1.3,0.3,1.7,0.9l0.9-0.5c-0.5-0.9-1.4-1.3-2.5-1.3c-1.9,0-3.3,1.3-3.3,3.2
			c0,1.8,1.4,3.2,3.3,3.2c1.1,0,2-0.5,2.5-1.3l-0.9-0.5c-0.4,0.6-1,0.9-1.7,0.9c-1.2,0-2.1-0.8-2.1-2.2
			C118.2,73.3,119.1,72.5,120.4,72.5z"></path>
		<path class="st0" d="M126.9,76.9c-0.6,0-1-0.4-1-1v-3.4h1.8v-0.9h-1.8v-1.4h-1.1v1.4h-1.1v0.9h1.1v3.4c0,1.3,0.7,1.9,2,1.9
			c0.5,0,1-0.1,1.4-0.4l-0.4-0.8C127.5,76.8,127.2,76.9,126.9,76.9z"></path>
	</g>
	<g id="Agroline">
		<path class="st0" d="M12.2,44.5H7.6L0.4,60.8h4.7l1.3-3.2h6.9l1.3,3.2h4.8L12.2,44.5z M7.7,54.2L9.9,49l2.1,5.2H7.7z"></path>
		<path class="st0" d="M29.7,48c1.6,0,2.9,0.6,4,1.8l2.9-2.7c-1.6-1.9-4.1-3-7.2-3c-5.3,0-9.1,3.5-9.1,8.5c0,5,3.8,8.5,9,8.5
			c2.4,0,5-0.7,6.9-2.1v-6.8h-4.1v4.5c-0.8,0.4-1.6,0.5-2.5,0.5c-2.8,0-4.6-1.9-4.6-4.7C25,49.8,26.8,48,29.7,48z"></path>
		<path class="st0" d="M51.4,55.7c2-1,3.2-2.8,3.2-5.2c0-3.7-2.8-6.1-7.3-6.1h-7.5v16.4h4.6v-4.3H47l3,4.3h5L51.4,55.7z M47.1,52.9
			h-2.6v-4.8h2.6c1.9,0,2.9,0.9,2.9,2.4C49.9,52,49,52.9,47.1,52.9z"></path>
		<path class="st0" d="M75.1,52.7c0-4.9-3.9-8.5-9.1-8.5c-5.2,0-9.1,3.6-9.1,8.5s3.9,8.5,9.1,8.5C71.3,61.2,75.1,57.6,75.1,52.7z
			 M66.1,57.3c-2.5,0-4.4-1.8-4.4-4.7s1.9-4.7,4.4-4.7c2.5,0,4.4,1.8,4.4,4.7S68.5,57.3,66.1,57.3z"></path>
		<polygon class="st0" points="78.3,44.5 78.3,60.8 90.8,60.8 90.8,57.2 83,57.2 83,44.5 		"></polygon>
		<rect x="93.3" y="44.5" class="st0" width="4.6" height="16.4"></rect>
		<polygon class="st0" points="101.9,44.5 101.9,60.8 106.5,60.8 106.5,52.1 113.7,60.8 117.5,60.8 117.5,44.5 113,44.5 113,53.2 
			105.7,44.5 		"></polygon>
		<polygon class="st0" points="126.1,57.3 126.1,54.3 133.3,54.3 133.3,50.8 126.1,50.8 126.1,48.1 134.3,48.1 134.3,44.5 
			121.5,44.5 121.5,60.8 134.6,60.8 134.6,57.3 		"></polygon>
	</g>
	<path id="Bildmarke" class="st0" d="M21.8,32.7c0.3,0,5.1-2.7,5.2-3.3c1.4-5.5,5.6-13.3,12.5-16.9c17.4-8.8,31.5,1.9,32.7,2.7
		c1,0.7,1.2,1.3,1.1,1.5c-0.2,0.3-0.3,0.5-2.1-0.1c-19.2-8.3-33.5,1.1-33.5,1.1c4.5,6.4,10,10.6,17.3,13.4
		c10.7,4.1,21.4,3.6,32.1-0.1c8.4-3,15.7-7.7,21.2-14.9c1.2-1.5,2.2-3.2,3-5c0,0,0.7-1.8-0.7-0.9c-3.6,2.3-6.9,2.6-11.1,1.6
		c-4.2-1.1-8.3-2.7-12.3-4.4C81,5,74.9,2.2,68.3,0.8C60.1-0.9,52.2,0,44.7,4.1c-7.5,4.1-13.4,10.1-18.3,17c-2,2.9-3.7,6-4.6,9.5
		C21.8,31.3,21.6,32.7,21.8,32.7z"></path>
</g>
</svg>
					</a>				</div>
															<div class="ml-auto d-flex align-items-center d-lg-none">
    						<button aria-label="Search Trigger" class="hnm-search-trigger btn al-iconed-link">
    							<span class="d-none d-lg-inline">Suche</span>  <span class="icon"><svg class="svg svg-search-solid" aria-hidden="true"><use xlink:href="#svg-search-solid"></use></svg></span>    						</button>
    						<a href="#" role="button" class="mobile-navi-toggle" aria-label="Open Navigation">
    							<span class="mobile-navi-bar"></span>
    							<span class="mobile-navi-bar"></span>
    							<span class="mobile-navi-bar"></span>
    						</a>
						</div>
											<div id="navi-container" class="d-none col-lg d-lg-flex justify-content-center">
						
													<nav id="globalnavi" class="navbar-nav navbar-expand-md">
								<ul class="level-1 level-rel-0 navbar-nav"><li class="level-1 level-rel-0 has-children nav-item"><a href="/de/service" class="nav-link" title="Gut beraten mit AGROLINE">Service</a><ul class="level-2 level-rel-1"><li class="level-2 level-rel-1 nav-item"><a href="/de/service/beratung" class="nav-link" title="AGROLINE ist der Spezialist für nachhaltigen Pflanzenschutz. Nutzen Sie unser Know-how – lassen Sie sich beraten!">Beratung</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/service/dienstleistung" class="nav-link" title="Dienstleistung">Dienstleistung</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/service/zielsortiment" class="nav-link" title="Zielsortiment">Zielsortiment</a></li><li class="level-2 level-rel-1 has-children nav-item"><a href="/de/service/fachinformationen" class="nav-link" title="Fachinformationen">Fachinformationen</a><ul class="level-3 level-rel-2"><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/versuchswesen" class="nav-link" title="Versuchsberichte - Lösungen für morgen">Versuchswesen</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/anbauempfehlungen-2" class="nav-link" title="Anbauempfehlungen für die Landwirtschaft">Anbauempfehlungen</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/konservierung-2" class="nav-link" title="Konservierungsmittel für Tierfutter">Konservierung</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/sicherheitsartikel" class="nav-link" title="Sicherheitsartikel">Sicherheitsartikel</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/pflanzkartoffel-bedarfsrechner" class="nav-link" title="Pflanzkartoffel-Bedarfsrechner und Sortenblätter Kartoffeln">Pflanzkartoffel-Bedarfsrechner und Sortenblätter</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/keimhemmung-kartoffeln" class="nav-link" title="Keimhemmung Kartoffeln">Keimhemmung Kartoffeln</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/filme" class="nav-link" title="Fachvideos für landwirtschaftliche Betriebe">Filme</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/waschplatz" class="nav-link" title="Waschplatz">Waschplatz</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/grundlagen-zum-pflanzenschutzmitteleinsatz" class="nav-link" title="Grundlagen zum Pflanzenschutzmitteleinsatz">Grundlagen zum Pflanzenschutzmitteleinsatz</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/fachartikel" class="nav-link" title="Fachartikel Pflanzenschutz">Fachartikel</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/fachblaetter" class="nav-link" title="Fachblätter zu Kulturen, Krankheiten, Schädlingen etc.">Fachblätter</a></li></ul></li></ul></li><li class="level-1 level-rel-0 has-children nav-item"><a href="/de/bioprotect" class="nav-link" title="Bioprotect. Ihr Partner rund um biologische Lösungen.">Bioprotect</a><ul class="level-2 level-rel-1"><li class="level-2 level-rel-1 nav-item"><a href="/de/bioprotect/shop" class="nav-link" title="Ihr Online-Shop rund um biologischen Pflanzenschutz, natürliche Schädlingsbekämpfung durch Nützlinge und Biodiversitätsförderung">Profi-Shop</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/bioprotect/privat-shop" class="nav-link" title="Shop für Privatkunden">Privatkunden-Shop</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/bioprotect/ratgeber" class="nav-link" title="Online-Ratgeber">Online-Ratgeber</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/bioprotect/agroline-bioprotect" class="nav-link" title="Bioprotect. Ihr Partner rund um biologische Lösungen.">Bioprotect</a></li><li class="level-2 level-rel-1 has-children nav-item"><a href="/de/bioprotect/kataloge" class="nav-link" title="Kataloge">Kataloge</a><ul class="level-3 level-rel-2"><li class="level-3 level-rel-2 nav-item"><a href="/de/bioprotect/kataloge/profikatalog" class="nav-link" title="Katalog für Landwirtschaft &amp; Gartenbau">Katalog für Landwirtschaft &amp; Gartenbau</a></li></ul></li></ul></li><li class="level-1 level-rel-0 has-children nav-item"><a href="/de/innovationen/innovagri-shop" class="nav-link" title="Innovationen">Innovationen</a><ul class="level-2 level-rel-1"><li class="level-2 level-rel-1 nav-item"><a href="/de/innovationen/innovagri-shop" class="nav-link" title="Innovagri-Shop">Innovagri-Shop</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/innovationen/agroline-innovationen" class="nav-link" title="Innovationen. Digitale Lösungen für eine ressourcenschonende Landwirtschaft.">Innovationen</a></li></ul></li><li class="level-1 level-rel-0 has-children nav-item"><a href="/de/agroline" class="nav-link" title="Nachhaltiger Pflanzenschutz">AGROLINE</a><ul class="level-2 level-rel-1"><li class="level-2 level-rel-1 nav-item"><a href="/de/agroline/team" class="nav-link" title="Team">Team</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/agroline/jobs" class="nav-link" title="Jobs">Jobs</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/agroline/eventkalender" class="nav-link" title="Eventkalender">Eventkalender</a></li></ul></li></ul>							</nav>
											</div>
										<div class="d-none d-lg-flex col-lg-auto al-search-language-container justify-content-end align-items-start">
						<div>
							<button aria-label="Search Trigger" class="hnm-search-trigger al-icon-btn al-icon-btn-orange">
								<span class="d-lg-none d-xl-inline">Suche</span>  <span class="icon"><svg class="svg svg-search-solid" aria-hidden="true"><use xlink:href="#svg-search-solid"></use></svg></span>							</button>
						</div>
													<nav id="languagenavi" class="nav nav-inline">
								<ul class="nav nav-inline mb-0"><li class="nav-item active"><a href="/" class="nav-link">DE</a></li>
<li class="nav-item"><a href="/fr" class="nav-link">FR</a></li>
</ul>							</nav>
											</div>
							</div>
		</div>
	</div>
					    	<div id="mobile-nav" class="expand-nav" data-toggler-ref=".mobile-navi-toggle" data-child-toggler-class="expand-nav-child-toggler d-lg-none" style="display: none;">
    		        		<nav class="mobile-nav__main">
        			<ul class="level-1 level-rel-0 nav flex-column"><li class="level-1 level-rel-0 has-children nav-item"><a href="/de/service" class="nav-link" title="Gut beraten mit AGROLINE" style="position: relative;">Service<span class="expand-nav-child-toggler d-lg-none"><svg class="svg svg-plus-solid" aria-hidden="true"><use xlink:href="#svg-plus-solid"></use></svg></span></a><ul class="level-2 level-rel-1" style="display: none;"><li class="level-2 level-rel-1 nav-item"><a href="/de/service/beratung" class="nav-link" title="AGROLINE ist der Spezialist für nachhaltigen Pflanzenschutz. Nutzen Sie unser Know-how – lassen Sie sich beraten!">Beratung</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/service/dienstleistung" class="nav-link" title="Dienstleistung">Dienstleistung</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/service/zielsortiment" class="nav-link" title="Zielsortiment">Zielsortiment</a></li><li class="level-2 level-rel-1 has-children nav-item"><a href="/de/service/fachinformationen" class="nav-link" title="Fachinformationen" style="position: relative;">Fachinformationen<span class="expand-nav-child-toggler d-lg-none"><svg class="svg svg-plus-solid" aria-hidden="true"><use xlink:href="#svg-plus-solid"></use></svg></span></a><ul class="level-3 level-rel-2" style="display: none;"><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/versuchswesen" class="nav-link" title="Versuchsberichte - Lösungen für morgen">Versuchswesen</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/anbauempfehlungen-2" class="nav-link" title="Anbauempfehlungen für die Landwirtschaft">Anbauempfehlungen</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/konservierung-2" class="nav-link" title="Konservierungsmittel für Tierfutter">Konservierung</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/sicherheitsartikel" class="nav-link" title="Sicherheitsartikel">Sicherheitsartikel</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/pflanzkartoffel-bedarfsrechner" class="nav-link" title="Pflanzkartoffel-Bedarfsrechner und Sortenblätter Kartoffeln">Pflanzkartoffel-Bedarfsrechner und Sortenblätter</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/keimhemmung-kartoffeln" class="nav-link" title="Keimhemmung Kartoffeln">Keimhemmung Kartoffeln</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/filme" class="nav-link" title="Fachvideos für landwirtschaftliche Betriebe">Filme</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/waschplatz" class="nav-link" title="Waschplatz">Waschplatz</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/grundlagen-zum-pflanzenschutzmitteleinsatz" class="nav-link" title="Grundlagen zum Pflanzenschutzmitteleinsatz">Grundlagen zum Pflanzenschutzmitteleinsatz</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/fachartikel" class="nav-link" title="Fachartikel Pflanzenschutz">Fachartikel</a></li><li class="level-3 level-rel-2 nav-item"><a href="/de/service/fachinformationen/fachblaetter" class="nav-link" title="Fachblätter zu Kulturen, Krankheiten, Schädlingen etc.">Fachblätter</a></li></ul></li></ul></li><li class="level-1 level-rel-0 has-children nav-item"><a href="/de/bioprotect" class="nav-link" title="Bioprotect. Ihr Partner rund um biologische Lösungen." style="position: relative;">Bioprotect<span class="expand-nav-child-toggler d-lg-none"><svg class="svg svg-plus-solid" aria-hidden="true"><use xlink:href="#svg-plus-solid"></use></svg></span></a><ul class="level-2 level-rel-1" style="display: none;"><li class="level-2 level-rel-1 nav-item"><a href="/de/bioprotect/shop" class="nav-link" title="Ihr Online-Shop rund um biologischen Pflanzenschutz, natürliche Schädlingsbekämpfung durch Nützlinge und Biodiversitätsförderung">Profi-Shop</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/bioprotect/privat-shop" class="nav-link" title="Shop für Privatkunden">Privatkunden-Shop</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/bioprotect/ratgeber" class="nav-link" title="Online-Ratgeber">Online-Ratgeber</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/bioprotect/agroline-bioprotect" class="nav-link" title="Bioprotect. Ihr Partner rund um biologische Lösungen.">Bioprotect</a></li><li class="level-2 level-rel-1 has-children nav-item"><a href="/de/bioprotect/kataloge" class="nav-link" title="Kataloge" style="position: relative;">Kataloge<span class="expand-nav-child-toggler d-lg-none"><svg class="svg svg-plus-solid" aria-hidden="true"><use xlink:href="#svg-plus-solid"></use></svg></span></a><ul class="level-3 level-rel-2" style="display: none;"><li class="level-3 level-rel-2 nav-item"><a href="/de/bioprotect/kataloge/profikatalog" class="nav-link" title="Katalog für Landwirtschaft &amp; Gartenbau">Katalog für Landwirtschaft &amp; Gartenbau</a></li></ul></li></ul></li><li class="level-1 level-rel-0 has-children nav-item"><a href="/de/innovationen/innovagri-shop" class="nav-link" title="Innovationen" style="position: relative;">Innovationen<span class="expand-nav-child-toggler d-lg-none"><svg class="svg svg-plus-solid" aria-hidden="true"><use xlink:href="#svg-plus-solid"></use></svg></span></a><ul class="level-2 level-rel-1" style="display: none;"><li class="level-2 level-rel-1 nav-item"><a href="/de/innovationen/innovagri-shop" class="nav-link" title="Innovagri-Shop">Innovagri-Shop</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/innovationen/agroline-innovationen" class="nav-link" title="Innovationen. Digitale Lösungen für eine ressourcenschonende Landwirtschaft.">Innovationen</a></li></ul></li><li class="level-1 level-rel-0 has-children nav-item"><a href="/de/agroline" class="nav-link" title="Nachhaltiger Pflanzenschutz" style="position: relative;">AGROLINE<span class="expand-nav-child-toggler d-lg-none"><svg class="svg svg-plus-solid" aria-hidden="true"><use xlink:href="#svg-plus-solid"></use></svg></span></a><ul class="level-2 level-rel-1" style="display: none;"><li class="level-2 level-rel-1 nav-item"><a href="/de/agroline/team" class="nav-link" title="Team">Team</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/agroline/jobs" class="nav-link" title="Jobs">Jobs</a></li><li class="level-2 level-rel-1 nav-item"><a href="/de/agroline/eventkalender" class="nav-link" title="Eventkalender">Eventkalender</a></li></ul></li></ul>        		</nav>
        	    		    			<nav class="mobile-nav__side">
    				<ul class="level-1 level-rel-0 nav flex-column"><li class="level-1 level-rel-0 nav-item"><a href="/de/standorte" class="nav-link" title="Standorte">Standorte</a></li><li class="level-1 level-rel-0 nav-item"><a href="/de/kontakt" class="nav-link" title="Kontakt">Kontakt</a></li></ul>    			</nav>
        	        	    			<nav class="mobile-nav__lang">
    				<ul class="nav mb-0"><li class="nav-item active"><a href="/" class="nav-link">DE</a></li>
<li class="nav-item"><a href="/fr" class="nav-link">FR</a></li>
</ul>    			</nav>
    			    </div>
    </header>

<div class="al-page" data-search="included">
						<div class="owl-hero-carousel-holder">
	<div class="owl-hero-carousel owl-carousel owl-loaded owl-drag">
	    	   					
			   					
			   					
			   					
			<div class="owl-stage-outer"><div class="owl-stage" style="transform: translate3d(-3969px, 0px, 0px); transition: all 0.25s ease 0s; width: 10584px;"><div class="owl-item cloned" style="width: 1323px;"><div class="slider-image ie-object-fit">
				<picture class="img-fluid"><source media="(min-width: 992px)" srcset="/files/sliderimage/res-1920x1080xccenters/res-var-1024x576xccenters/Innovationen-Virus-02.JPG 1024w, /files/sliderimage/res-1920x1080xccenters/Innovationen-Virus-02.JPG 1920w" width="1920" height="1080" sizes="(min-width: 1400px) 1920px, (min-width: 992px) 1024px">
<source media="(min-width: 768px)" srcset="/files/sliderimage/res-768x944xccenters/Innovationen-Virus-02.JPG 768w" width="768" height="944" sizes="(min-width: 768px) 768px">
<source media="(min-width: 576px)" srcset="/files/sliderimage/res-667x447xccenters/Innovationen-Virus-02.JPG 667w" width="667" height="447" sizes="(min-width: 576px) 667px">
<source srcset="/files/sliderimage/res-375x587xccenters/Innovationen-Virus-02.JPG 375w" width="375" height="587" sizes="375px">
<img src="/files/sliderimage/res-1920x1080xccenters/Innovationen-Virus-02.JPG" alt="" width="1920" height="1080">
</picture>									<div class="slider-text">
						<div class="container">
							<div class="row">
								<div class="col-md-10 offset-lg-1 col-lg-8 offset-xl-0 col-xl-7">
																			<div class="slider-kicker">
											digital, innovativ, präzise										</div>
																												<h2 class="slider-title">
											Digitale Lösungen für eine ressourcenschonende Landwirtschaft										</h2>
																	</div>
							</div>
						</div>
					</div>
							</div></div><div class="owl-item cloned" style="width: 1323px;"><div class="slider-image ie-object-fit">
				<picture class="img-fluid"><source media="(min-width: 992px)" srcset="/files/sliderimage/res-1920x1080xccenters/res-var-1024x576xccenters/Starseite_Ueber-uns.jpg 1024w, /files/sliderimage/res-1920x1080xccenters/Starseite_Ueber-uns.jpg 1920w" width="1920" height="1080" sizes="(min-width: 1400px) 1920px, (min-width: 992px) 1024px">
<source media="(min-width: 768px)" srcset="/files/sliderimage/res-768x944xccenters/Starseite_Ueber-uns.jpg 768w" width="768" height="944" sizes="(min-width: 768px) 768px">
<source media="(min-width: 576px)" srcset="/files/sliderimage/res-667x447xccenters/Starseite_Ueber-uns.jpg 667w" width="667" height="447" sizes="(min-width: 576px) 667px">
<source srcset="/files/sliderimage/res-375x587xccenters/Starseite_Ueber-uns.jpg 375w" width="375" height="587" sizes="375px">
<img src="/files/sliderimage/res-1920x1080xccenters/Starseite_Ueber-uns.jpg" alt="" width="1920" height="1080">
</picture>									<div class="slider-text">
						<div class="container">
							<div class="row">
								<div class="col-md-10 offset-lg-1 col-lg-8 offset-xl-0 col-xl-7">
																			<div class="slider-kicker">
											Kompetenz, Verantwortung, Wissenswertes										</div>
																												<h2 class="slider-title">
											AGROLINE - Nachhaltiger Pflanzenschutz										</h2>
																	</div>
							</div>
						</div>
					</div>
							</div></div><div class="owl-item" style="width: 1323px;"><div class="slider-image ie-object-fit">
				<picture class="img-fluid"><source media="(min-width: 992px)" srcset="/files/sliderimage/res-1920x1080xccenters/res-var-1024x576xccenters/Beratungssituation-Full-HD.jpg 1024w, /files/sliderimage/res-1920x1080xccenters/Beratungssituation-Full-HD.jpg 1920w" width="1920" height="1080" sizes="(min-width: 1400px) 1920px, (min-width: 992px) 1024px">
<source media="(min-width: 768px)" srcset="/files/sliderimage/res-768x944xccenters/Beratungssituation-Full-HD.jpg 768w" width="768" height="944" sizes="(min-width: 768px) 768px">
<source media="(min-width: 576px)" srcset="/files/sliderimage/res-667x447xccenters/Beratungssituation-Full-HD.jpg 667w" width="667" height="447" sizes="(min-width: 576px) 667px">
<source srcset="/files/sliderimage/res-375x587xccenters/Beratungssituation-Full-HD.jpg 375w" width="375" height="587" sizes="375px">
<img src="/files/sliderimage/res-1920x1080xccenters/Beratungssituation-Full-HD.jpg" alt="" width="1920" height="1080">
</picture>									<div class="slider-text">
						<div class="container">
							<div class="row">
								<div class="col-md-10 offset-lg-1 col-lg-8 offset-xl-0 col-xl-7">
																			<div class="slider-kicker">
											kompetent, innovativ, zukunftsorientiert										</div>
																												<h2 class="slider-title">
											Umfassende Beratung rund um nachhaltigen Pflanzenschutz										</h2>
																	</div>
							</div>
						</div>
					</div>
							</div></div><div class="owl-item active" style="width: 1323px;"><div class="slider-image ie-object-fit">
				<picture class="img-fluid"><source media="(min-width: 992px)" srcset="/files/sliderimage/res-1920x1080xccenters/res-var-1024x576xccenters/AGROLINE-Bioprotect-Imagebild-04.jpg 1024w, /files/sliderimage/res-1920x1080xccenters/AGROLINE-Bioprotect-Imagebild-04.jpg 1920w" width="1920" height="1080" sizes="(min-width: 1400px) 1920px, (min-width: 992px) 1024px">
<source media="(min-width: 768px)" srcset="/files/sliderimage/res-768x944xccenters/AGROLINE-Bioprotect-Imagebild-04.jpg 768w" width="768" height="944" sizes="(min-width: 768px) 768px">
<source media="(min-width: 576px)" srcset="/files/sliderimage/res-667x447xccenters/AGROLINE-Bioprotect-Imagebild-04.jpg 667w" width="667" height="447" sizes="(min-width: 576px) 667px">
<source srcset="/files/sliderimage/res-375x587xccenters/AGROLINE-Bioprotect-Imagebild-04.jpg 375w" width="375" height="587" sizes="375px">
<img src="/files/sliderimage/res-1920x1080xccenters/AGROLINE-Bioprotect-Imagebild-04.jpg" alt="" width="1920" height="1080">
</picture>									<div class="slider-text">
						<div class="container">
							<div class="row">
								<div class="col-md-10 offset-lg-1 col-lg-8 offset-xl-0 col-xl-7">
																			<div class="slider-kicker">
											biologisch, spezifisch, wirksam										</div>
																												<h2 class="slider-title">
											Spezialisten in der Zucht von Nützlingen und in der Entwicklung natürlicher Produkte										</h2>
																	</div>
							</div>
						</div>
					</div>
							</div></div><div class="owl-item" style="width: 1323px;"><div class="slider-image ie-object-fit">
				<picture class="img-fluid"><source media="(min-width: 992px)" srcset="/files/sliderimage/res-1920x1080xccenters/res-var-1024x576xccenters/Innovationen-Virus-02.JPG 1024w, /files/sliderimage/res-1920x1080xccenters/Innovationen-Virus-02.JPG 1920w" width="1920" height="1080" sizes="(min-width: 1400px) 1920px, (min-width: 992px) 1024px">
<source media="(min-width: 768px)" srcset="/files/sliderimage/res-768x944xccenters/Innovationen-Virus-02.JPG 768w" width="768" height="944" sizes="(min-width: 768px) 768px">
<source media="(min-width: 576px)" srcset="/files/sliderimage/res-667x447xccenters/Innovationen-Virus-02.JPG 667w" width="667" height="447" sizes="(min-width: 576px) 667px">
<source srcset="/files/sliderimage/res-375x587xccenters/Innovationen-Virus-02.JPG 375w" width="375" height="587" sizes="375px">
<img src="/files/sliderimage/res-1920x1080xccenters/Innovationen-Virus-02.JPG" alt="" width="1920" height="1080">
</picture>									<div class="slider-text">
						<div class="container">
							<div class="row">
								<div class="col-md-10 offset-lg-1 col-lg-8 offset-xl-0 col-xl-7">
																			<div class="slider-kicker">
											digital, innovativ, präzise										</div>
																												<h2 class="slider-title">
											Digitale Lösungen für eine ressourcenschonende Landwirtschaft										</h2>
																	</div>
							</div>
						</div>
					</div>
							</div></div><div class="owl-item" style="width: 1323px;"><div class="slider-image ie-object-fit">
				<picture class="img-fluid"><source media="(min-width: 992px)" srcset="/files/sliderimage/res-1920x1080xccenters/res-var-1024x576xccenters/Starseite_Ueber-uns.jpg 1024w, /files/sliderimage/res-1920x1080xccenters/Starseite_Ueber-uns.jpg 1920w" width="1920" height="1080" sizes="(min-width: 1400px) 1920px, (min-width: 992px) 1024px">
<source media="(min-width: 768px)" srcset="/files/sliderimage/res-768x944xccenters/Starseite_Ueber-uns.jpg 768w" width="768" height="944" sizes="(min-width: 768px) 768px">
<source media="(min-width: 576px)" srcset="/files/sliderimage/res-667x447xccenters/Starseite_Ueber-uns.jpg 667w" width="667" height="447" sizes="(min-width: 576px) 667px">
<source srcset="/files/sliderimage/res-375x587xccenters/Starseite_Ueber-uns.jpg 375w" width="375" height="587" sizes="375px">
<img src="/files/sliderimage/res-1920x1080xccenters/Starseite_Ueber-uns.jpg" alt="" width="1920" height="1080">
</picture>									<div class="slider-text">
						<div class="container">
							<div class="row">
								<div class="col-md-10 offset-lg-1 col-lg-8 offset-xl-0 col-xl-7">
																			<div class="slider-kicker">
											Kompetenz, Verantwortung, Wissenswertes										</div>
																												<h2 class="slider-title">
											AGROLINE - Nachhaltiger Pflanzenschutz										</h2>
																	</div>
							</div>
						</div>
					</div>
							</div></div><div class="owl-item cloned" style="width: 1323px;"><div class="slider-image ie-object-fit">
				<picture class="img-fluid"><source media="(min-width: 992px)" srcset="/files/sliderimage/res-1920x1080xccenters/res-var-1024x576xccenters/Beratungssituation-Full-HD.jpg 1024w, /files/sliderimage/res-1920x1080xccenters/Beratungssituation-Full-HD.jpg 1920w" width="1920" height="1080" sizes="(min-width: 1400px) 1920px, (min-width: 992px) 1024px">
<source media="(min-width: 768px)" srcset="/files/sliderimage/res-768x944xccenters/Beratungssituation-Full-HD.jpg 768w" width="768" height="944" sizes="(min-width: 768px) 768px">
<source media="(min-width: 576px)" srcset="/files/sliderimage/res-667x447xccenters/Beratungssituation-Full-HD.jpg 667w" width="667" height="447" sizes="(min-width: 576px) 667px">
<source srcset="/files/sliderimage/res-375x587xccenters/Beratungssituation-Full-HD.jpg 375w" width="375" height="587" sizes="375px">
<img src="/files/sliderimage/res-1920x1080xccenters/Beratungssituation-Full-HD.jpg" alt="" width="1920" height="1080">
</picture>									<div class="slider-text">
						<div class="container">
							<div class="row">
								<div class="col-md-10 offset-lg-1 col-lg-8 offset-xl-0 col-xl-7">
																			<div class="slider-kicker">
											kompetent, innovativ, zukunftsorientiert										</div>
																												<h2 class="slider-title">
											Umfassende Beratung rund um nachhaltigen Pflanzenschutz										</h2>
																	</div>
							</div>
						</div>
					</div>
							</div></div><div class="owl-item cloned" style="width: 1323px;"><div class="slider-image ie-object-fit">
				<picture class="img-fluid"><source media="(min-width: 992px)" srcset="/files/sliderimage/res-1920x1080xccenters/res-var-1024x576xccenters/AGROLINE-Bioprotect-Imagebild-04.jpg 1024w, /files/sliderimage/res-1920x1080xccenters/AGROLINE-Bioprotect-Imagebild-04.jpg 1920w" width="1920" height="1080" sizes="(min-width: 1400px) 1920px, (min-width: 992px) 1024px">
<source media="(min-width: 768px)" srcset="/files/sliderimage/res-768x944xccenters/AGROLINE-Bioprotect-Imagebild-04.jpg 768w" width="768" height="944" sizes="(min-width: 768px) 768px">
<source media="(min-width: 576px)" srcset="/files/sliderimage/res-667x447xccenters/AGROLINE-Bioprotect-Imagebild-04.jpg 667w" width="667" height="447" sizes="(min-width: 576px) 667px">
<source srcset="/files/sliderimage/res-375x587xccenters/AGROLINE-Bioprotect-Imagebild-04.jpg 375w" width="375" height="587" sizes="375px">
<img src="/files/sliderimage/res-1920x1080xccenters/AGROLINE-Bioprotect-Imagebild-04.jpg" alt="" width="1920" height="1080">
</picture>									<div class="slider-text">
						<div class="container">
							<div class="row">
								<div class="col-md-10 offset-lg-1 col-lg-8 offset-xl-0 col-xl-7">
																			<div class="slider-kicker">
											biologisch, spezifisch, wirksam										</div>
																												<h2 class="slider-title">
											Spezialisten in der Zucht von Nützlingen und in der Entwicklung natürlicher Produkte										</h2>
																	</div>
							</div>
						</div>
					</div>
							</div></div></div></div><div class="owl-nav disabled"><button type="button" role="presentation" class="owl-prev">prev</button><button type="button" role="presentation" class="owl-next">next</button></div></div>
			<div class="owl-hero-dots-holder">
			<div class="container">
				<div class="row">
					<div class="offset-lg-1 col-lg-10 offset-xl-0 col-xl-12">
						<div class="owl-hero-dots">
																							<div class="owl-hero-dot owl-dot">
									<a href="/de/service" class="btn btn-lg w-100 text-left" target="_top"><span>Service</span> <span class="icon"><svg class="svg svg-arrow-right-solid" aria-hidden="true"><use xlink:href="#svg-arrow-right-solid"></use></svg></span></a>																			<p class="slider-text-flow">
											Bei AGROLINE Services erhalten Sie alle Produkte und Dienstleistungen, die Sie für einen nachhaltigen Pflanzenschutz brauchen. Und eine kompetente Beratung dazu. Entdecken Sie unser Zielsortiment mit den bewährten fenaco Lösungen für Schweizer Kulturen. 										</p>
																	</div>
																							<div class="owl-hero-dot owl-dot active">
									<a href="/de/bioprotect/shop" class="btn btn-lg w-100 text-left" target="_top"><span>Bioprotect Shop</span> <span class="icon"><svg class="svg svg-arrow-right-solid" aria-hidden="true"><use xlink:href="#svg-arrow-right-solid"></use></svg></span></a>																			<p class="slider-text-flow">
											Ihr Partner rund um biologischen Pflanzenschutz, natürliche Schädlingsbekämpfung und Biodiversitätsförderung. Entdecken Sie Nützlinge, Mikroorganismen, Monitoringsysteme und weitere interessante Produkte von AGROLINE Bioprotect.										</p>
																	</div>
																							<div class="owl-hero-dot owl-dot">
									<a href="/de/innovationen/agroline-innovationen" class="btn btn-lg w-100 text-left" target="_top"><span>Innovationen</span> <span class="icon"><svg class="svg svg-arrow-right-solid" aria-hidden="true"><use xlink:href="#svg-arrow-right-solid"></use></svg></span></a>																			<p class="slider-text-flow">
											Für eine ressourcenschonende Landwirtschaft: AGROLINE Innovationen entwickelt digitale Lösungen, die Präzision, Arbeitserleichterung und Zeitersparnis vereinen und so den Bedürfnissen der engagierten Landwirtschaft gerecht werden.										</p>
																	</div>
																							<div class="owl-hero-dot owl-dot">
									<a href="/de/agroline" class="btn btn-lg w-100 text-left" target="_top"><span>AGROLINE</span> <span class="icon"><svg class="svg svg-arrow-right-solid" aria-hidden="true"><use xlink:href="#svg-arrow-right-solid"></use></svg></span></a>																			<p class="slider-text-flow">
											AGROLINE steht für nachhaltigen Pflanzenschutz mit kompetenter Beratung, Nützlingen, Pflanzenschutzmitteln und Pflanzenstärkung aus einer Hand. Lesen Sie mehr über die AGROLINE! 										</p>
																	</div>
													</div>
					</div>
				</div>
			</div>
		</div>
	</div>				
	<div class="al-page-main">
		<div class="al-bg-none">
	<div class="container main-container" role="main">
				<div class="row">
							<div class="offset-xl-2 col-xl-8 offset-md-1 col-md-10">
																							<h1 class="">AGROLINE ist die Schweizer Anbieterin für nachhaltigen Pflanzenschutz</h1>
																
 

<div class="al-breakout">
	<div class="container">
		<div class="row ci-col ci-2-col ">
			<div class="col-md-10 offset-md-1 offset-xl-0 col-xl-6 d-flex flex-column ci-2-col-left">
				
<h2>Ihre Herausforderung</h2>

<p class="lead">Die Konsumentinnen und Konsumenten wollen natürlich hergestellte Lebensmittel.</p>

<p style="margin-left:0cm; margin-right:0cm">Immer mehr Konsumentinnen und Konsumenten wünschen sich, dass für die Herstellung ihrer Lebensmittel immer weniger Fungizide, Herbizide und Insektizide verwendet werden. Darüber hinaus hat der Bundesrat einen Aktionsplan zur Risikoreduktion und zur nachhaltigen Anwendung von Pflanzenschutzmitteln verabschiedet. Das stellt die Schweizer Produzentinnen und Produzenten vor grosse Herausforderungen.</p>

			</div>
			<div class="col-md-10 offset-md-1 offset-xl-0 col-xl-6 d-flex flex-column ci-2-col-right">
				
<h2>Ihr Partner</h2>

<p class="lead">Die Produzentinnen und Produzenten brauchen einen verlässlichen Partner.</p>

<p style="margin-left:0cm; margin-right:0cm">Nachhaltige Landwirtschaft ist möglich. Aber nicht ganz einfach. Denn die Probleme sind komplex. Es braucht vielschichtige Lösungen aus verschiedenen Perspektiven. AGROLINE ist der Spezialist für nachhaltigen Pflanzenschutz. Und bietet Ihnen kompetente Beratung, innovative Produkte und umfassende Dienstleistungen für zeitgemässen Pflanzenschutz aus einer Hand. Mit AGROLINE sind Sie gut beraten!</p>

			</div>
		</div>
	</div>
</div>
</div></div></div></div><div class="al-bg-orange al-bg" style="transform: translate(0px, 150px);"><div class="al-bg-helper"></div><div class="container"><div class="row"><div class="offset-xl-2 col-xl-8 offset-md-1 col-md-10">
<h2>Nachhaltiger Pflanzenschutz</h2>

<p style="margin-left:0cm; margin-right:0cm">Nachhaltiger Pflanzenschutz ist komplex. Um den Anforderungen der Gesellschaft nach einer ressourcenschonenden Produktion von Lebensmitteln gerecht zu werden, müssen Bodenbearbeitung, Düngung und Fruchtfolge mit ganzheitlichen Ansätzen zur Bekämpfung von Krankheiten, Schädlingen und Unkraut kombiniert und perfekt aufeinander abgestimmt werden. AGROLINE betrachtet die Situation aus verschiedenen Blickwinkeln und setzt sich für umfassende Lösungen ein.<br>
&nbsp;</p>

<div class="ci-image ci-item">
	<figure>
						
					<img src="/files/ciimage/FEN_Grafik_Pflanzenschutz_Dunkelguen32.png" srcset="/files/ciimage/res-var-510x681/FEN_Grafik_Pflanzenschutz_Dunkelguen32.png 510w, /files/ciimage/res-var-545x727/FEN_Grafik_Pflanzenschutz_Dunkelguen32.png 545w, /files/ciimage/res-var-690x921/FEN_Grafik_Pflanzenschutz_Dunkelguen32.png 690w, /files/ciimage/FEN_Grafik_Pflanzenschutz_Dunkelguen32.png 1110w" sizes="(min-width: 1400px) 1110px, (min-width: 992px) 930px, (min-width: 768px) 690px, (min-width: 576px) 510px, 545px" width="930" height="930" class="img-fluid" alt="Agroline: Wir betrachten die Situation aus verschiedenen Blickwinkeln">					
				
					<figcaption class="figure-caption">
									© Agroline: Wir betrachten die Situation aus verschiedenen Blickwinkeln											</figcaption>
			</figure>

</div></div></div></div></div><div class="al-bg-green al-bg" style="transform: translate(0px, 150px);"><div class="al-bg-helper"></div><div class="container"><div class="row"><div class="offset-xl-2 col-xl-8 offset-md-1 col-md-10">
<h2>Entdecken Sie unsere Produkte und Dienstleistungen für eine effiziente, marktorientierte und nachhaltige Landwirtschaft.</h2>


<div class="al-breakout">
	<div class="container">
		<div class="row ci-col ci-3-col ">
			<div class="col-md-10 offset-md-1 offset-xl-0 col-xl-4 d-flex flex-column ci-3-col-left">
				
<article class="ci-item-nested ci-article-nested ci-article-nested--has-title d-flex flex-column flex-md-row flex-xl-column w-100 flex-fill">
	<figure class="ci-article-nested__image">	
				
		<a href="/de/service/beratung">			
    	    		<img src="/files/ciarticle/res-545x409xccenters/DSF3892.jpg" srcset="/files/ciarticle/res-545x409xccenters/res-var-172x130xccenters/DSF3892.jpg 172w, /files/ciarticle/res-545x409xccenters/res-var-238x179xccenters/DSF3892.jpg 238w, /files/ciarticle/res-545x409xccenters/res-var-356x268xccenters/DSF3892.jpg 356w, /files/ciarticle/res-545x409xccenters/res-var-516x388xccenters/DSF3892.jpg 516w, /files/ciarticle/res-545x409xccenters/DSF3892.jpg 545w" sizes="(min-width: 1400px) 356px, (min-width: 992px) 238px, (min-width: 768px) 172px, (min-width: 576px) 516px, 545px" width="545" height="409" class="img-fluid" title="Kompetente Beratung" alt="Kompetente Beratung">    	
		</a>	</figure>
	<div class="ci-article-nested__body">
					<div class="ci-article-nested__kicker">
				Service			</div>
							<h3 class="ci-article-nested__title">
									<a href="/de/service/beratung">Kompetente Beratung</a>							</h3>
				
<p>AGROLINE bietet Ihnen eine kompetente Beratung für alle Produktionsrichtungen und Kulturen. Probieren Sie es aus: Nehmen Sie Kontakt mit uns auf.</p>

					<a href="/de/service/beratung" class="ci-article-nested__link"></a>			<a href="/de/service/beratung" class="al-iconed-link mt-auto align-self-start">				<span>mehr lesen</span> <span class="icon"><svg class="svg svg-arrow-right-solid" aria-hidden="true"><use xlink:href="#svg-arrow-right-solid"></use></svg></span>			</a>			</div>
</article>
			</div>
			<div class="col-md-10 offset-md-1 offset-xl-0 col-xl-4 d-flex flex-column ci-3-col-middle">
				
<article class="ci-item-nested ci-article-nested ci-article-nested--has-title d-flex flex-column flex-md-row flex-xl-column w-100 flex-fill">
	<figure class="ci-article-nested__image">	
				
		<a href="/de/bioprotect/shop">			
    	    		<img src="/files/ciarticle/res-545x409xccenters/Trichogramma-parasitieren-Maiszuensler-Ei.jpg" srcset="/files/ciarticle/res-545x409xccenters/res-var-172x130xccenters/Trichogramma-parasitieren-Maiszuensler-Ei.jpg 172w, /files/ciarticle/res-545x409xccenters/res-var-238x179xccenters/Trichogramma-parasitieren-Maiszuensler-Ei.jpg 238w, /files/ciarticle/res-545x409xccenters/res-var-356x268xccenters/Trichogramma-parasitieren-Maiszuensler-Ei.jpg 356w, /files/ciarticle/res-545x409xccenters/res-var-516x388xccenters/Trichogramma-parasitieren-Maiszuensler-Ei.jpg 516w, /files/ciarticle/res-545x409xccenters/Trichogramma-parasitieren-Maiszuensler-Ei.jpg 545w" sizes="(min-width: 1400px) 356px, (min-width: 992px) 238px, (min-width: 768px) 172px, (min-width: 576px) 516px, 545px" width="545" height="409" class="img-fluid" title="Biologische Produkte im Online-Shop" alt="Biologische Produkte im Online-Shop">    	
		</a>	</figure>
	<div class="ci-article-nested__body">
					<div class="ci-article-nested__kicker">
				Bioprotect			</div>
							<h3 class="ci-article-nested__title">
									<a href="/de/bioprotect/shop">Biologische Produkte im Online-Shop</a>							</h3>
				
<p>Besuchen Sie den neuen&nbsp;Online-Shop von Bioprotect und entdecken Sie Nützlinge, Mikroorganismen, Monitoringsysteme und vieles mehr! Unser Online-Ratgeber liefert Ihnen zudem viele nützliche Zusatzinformationen.</p>

					<a href="/de/bioprotect/shop" class="ci-article-nested__link"></a>			<a href="/de/bioprotect/shop" class="al-iconed-link mt-auto align-self-start">				<span>zum Online-Shop</span> <span class="icon"><svg class="svg svg-arrow-right-solid" aria-hidden="true"><use xlink:href="#svg-arrow-right-solid"></use></svg></span>			</a>			</div>
</article>
			</div>
			<div class="col-md-10 offset-md-1 offset-xl-0 col-xl-4 d-flex flex-column ci-3-col-right">
				
<article class="ci-item-nested ci-article-nested ci-article-nested--has-title d-flex flex-column flex-md-row flex-xl-column w-100 flex-fill">
	<figure class="ci-article-nested__image">	
				
		<a href="/de/innovationen/agroline-innovationen">			
    	    		<img src="/files/ciarticle/res-545x409xccenters/AGROLINE-digitale-Loesung-Virus.jpg" srcset="/files/ciarticle/res-545x409xccenters/res-var-172x130xccenters/AGROLINE-digitale-Loesung-Virus.jpg 172w, /files/ciarticle/res-545x409xccenters/res-var-238x179xccenters/AGROLINE-digitale-Loesung-Virus.jpg 238w, /files/ciarticle/res-545x409xccenters/res-var-356x268xccenters/AGROLINE-digitale-Loesung-Virus.jpg 356w, /files/ciarticle/res-545x409xccenters/res-var-516x388xccenters/AGROLINE-digitale-Loesung-Virus.jpg 516w, /files/ciarticle/res-545x409xccenters/AGROLINE-digitale-Loesung-Virus.jpg 545w" sizes="(min-width: 1400px) 356px, (min-width: 992px) 238px, (min-width: 768px) 172px, (min-width: 576px) 516px, 545px" width="545" height="409" class="img-fluid" title="Entwicklung digitaler Lösungen" alt="Entwicklung digitaler Lösungen">    	
		</a>	</figure>
	<div class="ci-article-nested__body">
					<div class="ci-article-nested__kicker">
				Innovationen			</div>
							<h3 class="ci-article-nested__title">
									<a href="/de/innovationen/agroline-innovationen">Entwicklung digitaler Lösungen</a>							</h3>
				
<p>AGROLINE Innovationen setzt sich für eine ressourcenschonende Landwirtschaft ein. Erfahren Sie mehr über digitale Entwicklungen und innovative Möglichkeiten.</p>

					<a href="/de/innovationen/agroline-innovationen" class="ci-article-nested__link"></a>			<a href="/de/innovationen/agroline-innovationen" class="al-iconed-link mt-auto align-self-start">				<span>mehr lesen</span> <span class="icon"><svg class="svg svg-arrow-right-solid" aria-hidden="true"><use xlink:href="#svg-arrow-right-solid"></use></svg></span>			</a>			</div>
</article>
			</div>
		</div>
	</div>
</div>
</div></div></div></div><div class="al-bg-none"><div class="al-bg-helper"></div><div class="container"><div class="row"><div class="offset-xl-2 col-xl-8 offset-md-1 col-md-10">
<h2>AGROLINE ist der Pionier für eine ökologische Landwirtschaft</h2>

<p style="margin-left:0cm; margin-right:0cm">AGROLINE hat unter dem alten Namen «UFA-Samen Nützlinge» schon Nützlinge produziert und vermarktet, als «ökologische Landwirtschaft» noch nicht in aller Munde und in allen Medien war. Auch bei der Drohnentechnik hatte AGROLINE die Nase vorn: «UFA-Samen Nützlinge» hat als erstes Schweizer Unternehmen Drohnen eingesetzt, um Trichogramma-Schlupfwespen auf Maisfeldern auszubringen.</p>

<p>AGROLINE ist unter dem alten Namen «fenaco Pflanzenschutz» seit Jahrzehnten in der Beratung tätig. Und bekannt für die kompetente, kundenorientierte und nachhaltige Beratung. Die regionale LANDI sichert zusammen mit AGROLINE die Verfügbarkeit der Produkte und ist Verkaufs- und Kontaktpunkt für die Landwirte.</p>

<p>&nbsp;</p>

				</div>
					</div>
	</div>
</div>	</div>
	
	</div>

	<div class="bstmpl-cookie-banner py-3" data-cookie-name="accepted">
		<div class="container position-relative">
			<div class="d-flex justify-content-end">
			<button type="button" class="btn-save-cookie close position-absolute" aria-label="Close">
			<span aria-hidden="true"> <span class="icon"><svg class="svg svg-times-solid" aria-hidden="true"><use xlink:href="#svg-times-solid"></use></svg></span></span>
			</button>
			</div>
			<div class="bstmpl-cookies-text pr-3">
				<p class="mb-3 mb-md-0">
					Wir verwenden auf dieser Website Cookies, um das Nutzungserlebnis zu verbessern. Welche Daten wir zu welchem Zweck genau sammeln finden Sie auf unserer Seite zu den Datenschutzbestimmungen.											<a href="/de/datenschutz">Zur Datenschutz-Seite</a>									</p>
			</div>
		</div>
	</div>
	    	<div id="side-navi-container" class="d-none d-lg-block" data-search="excluded">
    		<div class="container">
    			<nav id="sidenavi" class="nav-inline">
    				<ul class="level-1 level-rel-0 nav nav-inline mb-0 nav-fill"><li class="level-1 level-rel-0 nav-item"><a href="/de/standorte" class="nav-link" title="Standorte">Standorte</a></li><li class="level-1 level-rel-0 nav-item"><a href="/de/kontakt" class="nav-link" title="Kontakt">Kontakt</a></li></ul>    			</nav>
    		</div>
    	</div>
			
					<footer class="page-footer" data-search="excluded">
									<div class="page-pre-footer">
						<div class="container">
													</div>
					</div>
								<div class="page-main-footer">
					<div class="container">
													
<div class="row">
    <div class="col-md-8 col-xl-7">
   		<h2 class="page-main-footer__headline">Für kompetente Beratung in allen Fragen rund um den Schutz Ihrer Kulturen</h2>
    </div>
	<div class="col-md-6 mb-4 mb-md-0">
		<p class="page-main-footer__intro">
			<span class="font-weight-bold">Service</span>
			Finden Sie Ihren persönlichen Berater in allen Fragen rund um den Pflanzenschutz.		</p>
		<p>
					 		<a href="/de/service/beratung" class="btn btn-orange"><span>Beratung</span> <span class="icon"><svg class="svg svg-arrow-right-solid" aria-hidden="true"><use xlink:href="#svg-arrow-right-solid"></use></svg></span></a>		 			</p>
		<p class="page-main-footer__text">	
			 			 	<a href="/de/standorte/service" class="al-iconed-link"><span>Standorte</span> <span class="icon"><svg class="svg svg-arrow-right-solid" aria-hidden="true"><use xlink:href="#svg-arrow-right-solid"></use></svg></span></a>			 								
		</p>
	</div>
	<div class="col-md-6">
		<p class="page-main-footer__intro">
			<span class="font-weight-bold">Bioprotect</span>
			Für biologische Massnahmen steht Ihnen das Team von Bioprotect zur Verfügung.		</p>
		<p class="page-main-footer__contacts">
			<a class="mb-2 mb-md-0 d-inline-block" href="tel:+41584343282">
				+41 (0)58 434 32 82			</a><br>
			<a href="mailto:bioprotect%40fenaco.com">bioprotect@fenaco.com</a>		</p>
		<p class="page-main-footer__text">
			 		 		<a href="/de/standorte/bioprotect" class="al-iconed-link"><span>Standorte</span> <span class="icon"><svg class="svg svg-arrow-right-solid" aria-hidden="true"><use xlink:href="#svg-arrow-right-solid"></use></svg></span></a>			 								
		</p>
	</div>
</div>													    					<nav id="footernavi" class="nav-inline">
	    						<ul class="level-1 level-rel-0 nav nav-inline mb-0"><li class="level-1 level-rel-0 nav-item"><a href="/de/impressum" class="nav-link" title="Impressum">Impressum</a></li><li class="level-1 level-rel-0 nav-item"><a href="/de/agb" class="nav-link" title="AGB">AGB</a></li><li class="level-1 level-rel-0 nav-item"><a href="/de/datenschutz" class="nav-link" title="Datenschutz">Datenschutz</a></li></ul>	    					</nav>
											</div>
				</div>
				<div id="fenaco-footer" class="py-4">
					<div class="container">
						<div class="row align-items-end">
							<div class="col-6 col-md-3 text-center">
								<figure class="al-footer-fenaco-link">
									<figcaption class="al-footer-fenaco-link__caption">AGROLINE gehört zur fenaco Genossenschaft</figcaption>
									<a href="https://www.fenaco.com" class="al-footer-fenaco-link__image" target="_blank" rel="noopener"><svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="150px" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 567 142" style="enable-background:new 0 0 567 142;" xml:space="preserve">
												<path fill="#009640" d="M118.37,38.02h-0.28
													c-13.16,0-39.26,4.01-44.68,36.12H44.18V41.58c0-11.27,4.93-23.94,21.83-23.94c9.16,0,15.14,1.01,22.3,2.72V3.23
													c-7.05-1.64-15.49-2.96-22.54-2.96c-28.63,0-42.71,13.61-42.71,39.9v33.97H0v16.43h23.06v48.85h21.12V90.57h28.35
													C73.87,121.93,92.55,142,126.34,142c12.2,0,20.33-1.91,29.72-4.97v-15.44c-8.01,2.66-19.27,4.42-28.27,3.58
													c-19.96-1.87-32.26-14.99-32.86-34.6h63.81v-7.72C158.74,56.56,145.6,38.02,118.37,38.02 M95.28,74.14
													c0.01-4.94,4.58-19.6,21.92-19.6c14.42,0,19.28,12.56,19.29,19.6H95.28z"></path>
												<path fill="#009640" d="M227.97,38.03c-22.76,0-31.02,15.24-34.77,24.63h-0.47V40.6H171.6v98.81h21.12v-34.51
													c0-22.3,12.3-45.77,29.66-45.77c15.26,0,15.44,11.5,15.44,32.15v48.12h21.12V74.87C258.95,61.02,253.79,38.03,227.97,38.03"></path>
												<path fill="#009640" d="M414.76,54.55c15.49,0,19.49,13.99,19.49,22.43h23.71c0-18.31-10.73-38.96-39.9-38.96
													c-33.33,0-47.64,23.34-47.64,48c0,21.88,11.97,55.98,54.68,55.98c13.15,0,21.82-1.64,30.51-4.23v-14.64
													c-6.57,1.87-12.44,2.25-18.31,2.25c-35.2,0-43.41-24.6-43.41-43.35C393.89,64.48,403.43,54.55,414.76,54.55"></path>
												<path fill="#009640" d="M517.64,38.02c-33.79,0-49.52,23.76-49.52,51.95c0,27.81,15.73,52.03,49.52,52.03
													c33.56,0,49.29-25.23,49.29-51.99C566.93,62.9,551.2,38.02,517.64,38.02 M517.64,125.47c-23.23,0-26.05-25.85-26.05-35.46
													c0-9.78,2.82-35.47,26.05-35.47c22.77,0,25.82,24.61,25.82,35.47C543.46,100.7,540.41,125.47,517.64,125.47z"></path>
												<path fill="#009640" d="M317.68,37.88c-29.2,0-40.77,14.84-43.19,33.56h22.86c1.36-9.51,6.45-17.04,19.63-17.04
													c8.36,0,20.24,2.41,20.24,18.14v1.95c-33.43,0-66.49,7.89-66.49,37.47c0,17.37,11.08,30.04,31.97,30.04
													c17.13,0,31.23-10.32,34.05-16.43h0.47v13.85h21.12V76.14C358.35,66.05,357.81,37.88,317.68,37.88 M337.23,93.88
													c0,13.14-13.94,31.6-31,31.6c-6.34,0-12.86-5.54-12.86-14.22c0-15.32,22.79-20.88,39.45-20.88h4.42V93.88z"></path>
												</svg>
												</a>								</figure>
							</div>
							<div class="col-6 col-md-3 text-center">
								<figure class="al-footer-fenaco-link">
									<figcaption class="al-footer-fenaco-link__caption">Saatgut</figcaption>
									<a href="https://www.ufasamen.ch" class="al-footer-fenaco-link__image" target="_blank" rel="noopener"><svg width="132px" height="40px" viewBox="0 0 132 40" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
	                                            <title>ufa-samen</title>
	                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
	                                                <g transform="translate(-1431.000000, -840.000000)">
	                                                    <g transform="translate(0.000000, 778.000000)">
	                                                        <g transform="translate(1330.000000, 24.000000)">
	                                                            <g transform="translate(101.000000, 38.000000)">
	                                                                <path d="M97.9668053,11.5832785 C93.726801,14.5951284 90.2532964,18.9170507 87.649785,24.7300856 L87.520418,24.7300856 L89.5417777,16.3396972 C91.7507197,6.53061224 101.854284,0 116.207556,0 L127,0 L126.2335,3.14022383 L120.505775,3.14022383 C110.499236,3.14022383 102.624018,6.33640553 97.9668053,11.5832785 Z M37.9405054,31.7314022 L36,40 L36.7664996,40 L38.707005,31.7314022 L37.9405054,31.7314022 Z" fill="#65B32E"></path>
	                                                                <path d="M92,28.9933431 L94.048173,20.5224338 C96.3978371,10.6435894 106.524005,4 121.064395,4 L132,4 L129.660167,13.6891226 C127.343274,23.3749168 118.078978,29 104.855972,29 L92,28.9933431 Z" fill="#FFDD00"></path>
	                                                                <path d="M101.615818,24.5331786 L93.8854626,24.5331786 C95.544309,17.8640601 101.117508,12.5456491 107.510296,9.37823996 C115.2636,5.55573761 123.246389,5.16403242 123.246389,5.16403242 L123.275894,5.05259905 C112.860568,5.05259905 105.212171,6.8254027 99.3439197,11.0598709 C104.068026,5.67730129 112.050814,2.39845873 122.194037,2.39845873 L128,2.39845873 L126.449339,9.00004185 C124.151214,18.8939746 114.843971,24.5331786 101.615818,24.5331786 Z M92.3249667,31.8945347 C91.8331393,31.7024299 91.3100261,31.6095699 90.784141,31.6210164 C89.6367175,31.6210164 88.5417478,32.1849368 88.4892941,33.4816161 C88.4401188,35.119349 90.4825325,34.943757 90.4563057,36.0547139 C90.4563057,36.7300677 89.8662022,37.0137163 89.2924905,37.0137163 C88.7842147,36.9842877 88.2884234,36.840192 87.8401803,36.5916202 L87.512345,37.6417953 C88.0527954,37.8550805 88.6252973,37.9693612 89.203975,37.9794722 C90.5153161,37.9794722 91.6758529,37.4324356 91.7217498,35.92302 C91.7709251,34.2143749 89.7317898,34.2751568 89.7547382,33.2891403 C89.7547382,32.7589875 90.3481201,32.5833956 90.7743059,32.5833956 C91.2035969,32.5887834 91.6257188,32.6974819 92.0069665,32.9008118 L92.3249667,31.8945347 Z M5.08472493,37.8916762 L6.39606598,37.8916762 L7.19926237,36.4801868 L9.74326401,36.4801868 L9.90718164,37.8916762 L11.1037804,37.8916762 L10.1956767,31.7392033 L8.88433562,31.7392033 L5.08472493,37.8916762 Z M9.28757299,32.8265229 L9.61540826,35.5043006 L7.76641738,35.5043006 L9.28757299,32.8265229 Z M81.8145682,37.8916762 L85.3814158,37.8916762 L85.6141789,36.9225435 L83.2504866,36.9225435 L83.653724,35.1936379 L85.7485913,35.1936379 L85.9649626,34.2245052 L83.876652,34.2245052 L84.2307141,32.708336 L86.5649011,32.708336 L86.7747157,31.7392033 L83.2504866,31.7392033 L81.8145682,37.8916762 Z M81.3195369,31.9316792 C80.7605997,31.7195765 80.1676406,31.6187381 79.572175,31.6345235 C77.4609159,31.6345235 76.1069563,33.0966644 76.0512243,35.0788277 C76.002049,36.7469516 76.8281938,37.9997328 78.6345661,37.9997328 C79.092178,38.0044104 79.5484311,37.9476532 79.9918041,37.8308944 L80.2147321,36.6490253 C79.8336416,36.8928777 79.3946374,37.023742 78.9460096,37.0272234 C77.8936584,37.0272234 77.2674931,36.112119 77.3068333,35.0788277 C77.3461735,33.6335707 78.3100092,32.6036562 79.637742,32.6036562 C80.1139626,32.5849954 80.584609,32.7144257 80.9884233,32.9751007 L81.3195369,31.9316792 Z M12.624936,37.8916762 L13.7625243,37.8916762 L15.0082983,32.796132 L15.3558037,37.8916762 L16.4999488,37.8916762 L19.3553939,32.796132 L18.0932282,37.8916762 L19.3586723,37.8916762 L20.8142608,31.7392033 L18.8308575,31.7392033 L16.3458662,36.1729009 L16.0573712,31.7392033 L14.0903596,31.7392033 L12.624936,37.8916762 Z M21.9879111,37.8916762 L25.548202,37.8916762 L25.7809651,36.9225435 L23.4205512,36.9225435 L23.8205102,35.1936379 L25.9153775,35.1936379 L26.1350271,34.2245052 L24.0467165,34.2245052 L24.3975003,32.708336 L26.7349657,32.708336 L26.9415019,31.7392033 L23.4205512,31.7392033 L21.9879111,37.8916762 Z M27.9774613,37.8916762 L29.1248847,37.8916762 L30.2132978,33.1641998 L31.3410511,37.8916762 L32.8818769,37.8916762 L34.3538572,31.7392033 L33.2228255,31.7392033 L32.111464,36.4126514 L30.9607622,31.7392033 L29.4461633,31.7392033 L27.9774613,37.8916762 Z M4.81590001,31.9080418 C4.3240726,31.7159369 3.80095941,31.623077 3.27507428,31.6345235 C2.12765086,31.6345235 1.03268108,32.1984439 0.980227436,33.4951232 C0.934330499,35.1328561 2.97674419,34.9572641 2.94723901,36.068221 C2.9275689,36.7435748 2.35385719,37.0272234 1.78014548,37.0272234 C1.27186962,36.9977948 0.77607831,36.8536991 0.327835263,36.6051273 L0,37.6553024 C0.540616657,37.8679884 1.11301538,37.9822485 1.69162996,37.9929793 C3.00297101,37.9929793 4.16678619,37.4459427 4.20940477,35.936527 C4.25858006,34.227882 2.21944473,34.2886638 2.2423932,33.3026473 C2.25878496,32.7724946 2.83577502,32.5969026 3.26523922,32.5969026 C3.69343506,32.6026909 4.11440401,32.7113816 4.49462145,32.9143189 L4.81590001,31.9080418 Z M68.8126217,37.8916762 L69.9731585,37.8916762 L71.0615716,33.1641998 L72.1893249,37.8916762 L73.7301506,37.8916762 L75.2021309,31.7392033 L74.0710993,31.7392033 L72.9597377,36.4126514 L71.8024793,31.7392033 L70.2878803,31.7392033 L68.8126217,37.8916762 Z M62.9935457,37.8916762 L66.5538367,37.8916762 L66.7865997,36.9225435 L64.4261858,36.9225435 L64.8261449,35.1936379 L66.9242905,35.1936379 L67.1406618,34.2245052 L65.0556295,34.2245052 L65.4064133,32.708336 L67.7438787,32.708336 L67.9504149,31.7392033 L64.4294642,31.7392033 L62.9935457,37.8916762 Z M53.8141584,37.8916762 L54.9517467,37.8916762 L56.1975207,32.796132 L56.5319127,37.8916762 L57.6793361,37.8916762 L60.5315029,32.796132 L59.2693372,37.8916762 L60.5380596,37.8916762 L61.9903698,31.7392033 L59.9971314,31.7392033 L57.5154185,36.1729009 L57.2236451,31.7392033 L55.2566335,31.7392033 L53.8141584,37.8916762 Z M47.9426288,37.8916762 L51.5029198,37.8916762 L51.7389612,36.9225435 L49.3752689,36.9225435 L49.7752279,35.1936379 L51.8733736,35.1936379 L52.0897449,34.2245052 L49.9752075,34.2245052 L50.3292695,32.708336 L52.6634566,32.708336 L52.8732712,31.7392033 L49.3687122,31.7392033 L47.9426288,37.8916762 Z M46.6968548,31.9080418 C46.2050924,31.7156918 45.6819326,31.6228236 45.1560291,31.6345235 C44.011884,31.6345235 42.9169143,32.1984439 42.877574,33.4951232 C42.8283987,35.1328561 44.8708124,34.9572641 44.8445856,36.068221 C44.8249155,36.7435748 44.2512038,37.0272234 43.6774921,37.0272234 C43.1692162,36.9977948 42.6734249,36.8536991 42.2251818,36.6051273 L41.8973466,37.6553024 C42.437797,37.8685876 43.0102989,37.9828683 43.5889765,37.9929793 C44.9003176,37.9929793 46.0608544,37.4459427 46.1067514,35.936527 C46.1559266,34.227882 44.1167913,34.2886638 44.1397398,33.3026473 C44.1561315,32.7724946 44.7331216,32.5969026 45.1625858,32.5969026 C45.5907816,32.6026909 46.0117506,32.7113816 46.391968,32.9143189 L46.6968548,31.9080418 Z M58.1973158,15.966316 L61.0462043,12.0391338 L49.952259,12.0391338 L50.3817232,10.2494463 C50.8013523,8.48339621 52.0208995,7.1833402 54.6107981,7.13606544 L64.2753816,7.13606544 L67.4848888,2.47950118 L52.4634771,2.47950118 C47.9393505,2.63145578 45.2511013,4.50556249 44.1954718,7.86207074 L40.2155517,24.5331786 L46.9689581,24.5331786 L49.0113718,15.9629392 L58.1973158,15.966316 Z M72.2942321,21.1766704 L77.9526688,21.1766704 L78.7034115,24.5534392 L85.7092511,24.5331786 C84.9322815,21.2948573 82.9226514,12.7853997 81.617867,6.49447935 C81.0048151,3.84709257 79.9000102,2 76.801967,2 C73.7039238,2 71.7205204,3.84709257 69.8485811,6.49447935 C65.5408257,12.7989068 59.4824301,21.3083643 57.1547997,24.5331786 L64.1344125,24.5331786 L66.4981047,21.1564097 L72.2942321,21.1766704 Z M75.2185227,8.2571527 L77.0740703,16.9725931 L69.4355087,16.9725931 L75.2185227,8.2571527 Z M31.0230509,14.5514499 C30.3673804,17.3440377 29.6068026,20.6059964 25.0039955,20.6059964 C20.3421781,20.6059964 21.0142403,17.4284569 21.699416,14.5514499 L24.5384694,2.64833962 L17.7752279,2.64833962 L14.5460506,16.1959363 C13.1002971,22.2741202 15.8573917,25.097099 23.8959123,25.097099 C31.9344329,25.097099 35.970085,22.2234687 37.4158385,16.1587918 L40.6384592,2.65171639 L33.8621043,2.65171639 L31.0230509,14.5514499 Z" fill="#005F27"></path>
	                                                            </g>
	                                                        </g>
	                                                    </g>
	                                                </g>
	                                            </g>
	                                        </svg></a>								</figure>
							</div>
							<div class="col-6 col-md-3 text-center">
								<figure class="al-footer-fenaco-link">
									<figcaption class="al-footer-fenaco-link__caption">Dünger</figcaption>
									<a href="https://www.landor.ch" class="al-footer-fenaco-link__image" target="_blank" rel="noopener"><svg width="109px" height="40px" viewBox="0 0 109 40" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
	                                            <title>Logo-Landor-ohneSlogan</title>
	                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
	                                                <g transform="translate(-726.000000, -840.000000)" fill-rule="nonzero">
	                                                    <g transform="translate(0.000000, 778.000000)">
	                                                        <g transform="translate(613.000000, 24.000000)">
	                                                            <g transform="translate(113.000000, 38.000000)">
	                                                                <polygon fill="#457C1F" points="14.980425 33.4875346 6.81060095 33.5682628 6.84118146 16.2053819 0.0410422603 16.195093 0 38.9968342 18.6026057 38.9691334 24.2624138 27.2366442 26.2364661 31.0593589 24.5987994 31.1179264 21.6912369 37.1733281 29.4208626 37.223981 30.0968528 38.9897111 37 39 24.09744 15"></polygon>
	                                                                <path d="M48.3080788,15.9290946 L48.2872764,27.2541338 L34.0200023,15 L34,27.2765216 L40.639179,39.6186075 L40.7183883,28.0952762 L54.8560471,40 L54.8864507,21.6347907 L58.2380439,21.6395881 C62.2745175,21.6475837 65.1004491,23.672882 65.0940484,27.4772124 C65.0860474,31.7516871 61.9840835,33.3939937 58.1572344,33.3867976 L56.6210542,33.3835993 L56.6378562,38.9821537 L59.3085695,38.9869511 C65.6973192,38.998145 70.3850692,34.7140755 70.8163198,28.5758148 C70.8483235,28.1264592 71.1235558,26.6264752 71.07395,26.1915118 C71.7012236,23.7728276 73.7478637,21.8098954 76.5689948,21.8138932 C79.9317893,21.8202898 82.1912544,24.165414 82.1864539,27.2141555 C82.1800531,31.0248825 79.6485561,32.9910129 76.2849614,33.0277929 C75.8113059,33.0341894 74.8295907,32.7967186 73.9558882,32.384143 C73.1181899,31.9923562 72.4653133,31.3207215 72.2148839,31.1648063 C72.2148839,31.1648063 71.8660429,32.4097291 71.0019416,34.0976109 C70.4866811,35.1034637 69.172927,36.6418268 69.172927,36.6418268 C71.1867632,38.2265647 73.5990463,39.3531519 76.5393913,39.2931845 C83.2721813,39.1572585 88.4927938,34.0912144 88.50561,26.9391051 C88.5159965,21.1934308 83.0017495,15.5812838 76.7634176,15.5700899 C73.3038116,15.5620942 69.9938233,17.0005117 67.7279574,19.4223942 C65.5981075,17.2731634 62.6209582,15.9554802 59.346174,15.9490837 L48.3080788,15.9290946 L48.3080788,15.9290946 Z M109,38.9685611 L104.011415,29.8607158 C106.342888,28.689353 107.97588,26.0547862 107.644641,22.8941056 C106.970962,16.4488118 102.063986,15.9618767 97.5090517,15.9554802 L88.5520008,15.9394889 L88.511196,38.9757572 L95.338397,38.9885502 L95.3680005,21.7387341 L99.7933197,21.718745 C99.7933197,21.718745 102.052785,21.4716794 101.992778,23.2882912 C101.934371,25.1041034 100.029347,25.0761186 100.029347,25.0761186 L97.0225947,25.1872581 L97.1130053,30.5419452 L101.751149,38.9685611 L109,38.9685611 Z" fill="#457C1F"></path>
	                                                                <path d="M105.988634,15.977167 C103.979242,14.5437632 101.208311,14.372093 98.556726,14.367019 L88.6412914,14.3501057 C88.6412914,14.3501057 76.7676132,3.92980973 61.5563151,3.92980973 C41.8188115,3.92980973 25,13.9010571 25,13.9010571 C25,13.9010571 41.0783811,0 63.8474276,0 C88.6445389,0 106,16 106,16 L105.988634,15.977167 L105.988634,15.977167 Z" fill="#B3C69C"></path>
	                                                            </g>
	                                                        </g>
	                                                    </g>
	                                                </g>
	                                            </g>
	                                        </svg></a>								</figure>
							</div>
							<div class="col-6 col-md-3 text-center">
								<figure class="al-footer-fenaco-link">
									<figcaption class="al-footer-fenaco-link__caption">Pflanzenschutz</figcaption>
									<a href="https://www.agroline.ch" class="al-footer-fenaco-link__image" target="_blank" rel="noopener"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 94 56" width="94px" height="56px">
	                                          <path d="M5.87,50.22c0-.5.42-.9,1.39-.9a2.93,2.93,0,0,1,1.57.46l.27-.65a3.34,3.34,0,0,0-1.84-.51c-1.5,0-2.19.75-2.19,1.64,0,2.12,3.47,1.24,3.47,2.64,0,.5-.43.88-1.42.88a3,3,0,0,1-1.89-.66l-.3.64a3.44,3.44,0,0,0,2.19.72c1.52,0,2.22-.75,2.22-1.63C9.34,50.75,5.87,51.62,5.87,50.22Z" fill="#00b140"></path>
	                                          <path d="M12.28,50a2.12,2.12,0,0,0-2.18,2.2,2.17,2.17,0,0,0,2.33,2.22,2.16,2.16,0,0,0,1.71-.7l-.43-.51a1.63,1.63,0,0,1-1.25.52,1.47,1.47,0,0,1-1.58-1.26H14.4a2.32,2.32,0,0,0,0-.24A2.09,2.09,0,0,0,12.28,50Zm-1.4,1.9a1.4,1.4,0,0,1,2.79,0Z" fill="#00b140"></path>
	                                          <path d="M16.23,50.8v-.72h-.75v4.33h.78V52.25a1.29,1.29,0,0,1,1.37-1.46h.18V50A1.67,1.67,0,0,0,16.23,50.8Z" fill="#00b140"></path>
	                                          <polygon points="20.66 53.55 19.18 50.08 18.36 50.08 20.26 54.41 21.06 54.41 22.96 50.08 22.19 50.08 20.66 53.55" fill="#00b140"></polygon>
	                                          <rect x="23.73" y="50.08" width="0.79" height="4.34" fill="#00b140"></rect>
	                                          <path d="M24.12,48.22a.52.52,0,0,0-.53.51.53.53,0,1,0,.53-.51Z" fill="#00b140"></path>
	                                          <path d="M27.85,50.72a1.34,1.34,0,0,1,1.17.62l.6-.38A1.92,1.92,0,0,0,27.85,50a2.21,2.21,0,1,0,0,4.42,2,2,0,0,0,1.77-.93l-.6-.38a1.32,1.32,0,0,1-1.17.62,1.53,1.53,0,0,1,0-3.05Z" fill="#00b140"></path>
	                                          <path d="M32.37,50a2.12,2.12,0,0,0-2.18,2.2,2.36,2.36,0,0,0,4,1.52l-.43-.51a1.63,1.63,0,0,1-1.25.52A1.47,1.47,0,0,1,31,52.51h3.52a2.32,2.32,0,0,0,0-.24A2.09,2.09,0,0,0,32.37,50ZM31,51.94a1.4,1.4,0,0,1,2.79,0Z" fill="#00b140"></path>
	                                          <path d="M42.6,51.9,42,51.7a3.44,3.44,0,0,1-.35,1L40.2,51.33c.88-.5,1.22-.92,1.22-1.5,0-.74-.55-1.19-1.42-1.19s-1.55.51-1.55,1.28a1.78,1.78,0,0,0,.69,1.31c-1,.56-1.39,1.06-1.39,1.76,0,.87.84,1.49,2,1.49a2.71,2.71,0,0,0,1.92-.74l.76.76.44-.52-.77-.77A3.77,3.77,0,0,0,42.6,51.9ZM40,49.21c.49,0,.76.25.76.63s-.25.62-1,1c-.46-.48-.58-.69-.58-1S39.48,49.21,40,49.21Zm-.2,4.61c-.77,0-1.28-.36-1.28-.91s.25-.8,1-1.25l1.63,1.62A2,2,0,0,1,39.8,53.82Z" fill="#00b140"></path>
	                                          <path d="M50.24,51.44A1.34,1.34,0,0,0,51,50.17c0-.93-.73-1.49-2-1.49H46.38v5.73h2.73c1.45,0,2.2-.56,2.2-1.54A1.4,1.4,0,0,0,50.24,51.44Zm-3-2.09h1.68c.83,0,1.31.31,1.31.91s-.48.92-1.31.92H47.2Zm1.88,4.4H47.2V51.84h1.88c.91,0,1.4.3,1.4,1S50,53.75,49.08,53.75Z" fill="#00b140"></path>
	                                          <path d="M52.87,48.22a.52.52,0,0,0-.53.51.53.53,0,0,0,1.06,0A.5.5,0,0,0,52.87,48.22Z" fill="#00b140"></path>
	                                          <rect x="52.48" y="50.08" width="0.79" height="4.34" fill="#00b140"></rect>
	                                          <path d="M56.57,50a2.21,2.21,0,1,0,2.25,2.2A2.15,2.15,0,0,0,56.57,50Zm0,3.73A1.53,1.53,0,1,1,58,52.24,1.43,1.43,0,0,1,56.57,53.77Z" fill="#00b140"></path>
	                                          <path d="M62.19,50a1.84,1.84,0,0,0-1.54.72v-.68h-.76V56h.79V53.76a1.86,1.86,0,0,0,1.51.7,2.21,2.21,0,0,0,0-4.42Zm-.06,3.73a1.53,1.53,0,1,1,1.46-1.53A1.43,1.43,0,0,1,62.13,53.77Z" fill="#00b140"></path>
	                                          <path d="M66.23,50.8v-.72h-.76v4.33h.79V52.25a1.29,1.29,0,0,1,1.37-1.46h.18V50A1.67,1.67,0,0,0,66.23,50.8Z" fill="#00b140"></path>
	                                          <path d="M70.56,50a2.21,2.21,0,1,0,2.25,2.2A2.15,2.15,0,0,0,70.56,50Zm0,3.73A1.53,1.53,0,1,1,72,52.24,1.43,1.43,0,0,1,70.56,53.77Z" fill="#00b140"></path>
	                                          <path d="M75.41,53.8a.64.64,0,0,1-.68-.73V50.72H76v-.64H74.73v-.95h-.79v.95h-.73v.64h.73V53.1a1.24,1.24,0,0,0,1.38,1.36,1.52,1.52,0,0,0,1-.3L76,53.59A.92.92,0,0,1,75.41,53.8Z" fill="#00b140"></path>
	                                          <path d="M78.82,50a2.11,2.11,0,0,0-2.17,2.2,2.36,2.36,0,0,0,4,1.52l-.44-.51a1.6,1.6,0,0,1-1.25.52,1.46,1.46,0,0,1-1.57-1.26H81a2.32,2.32,0,0,0,0-.24A2.1,2.1,0,0,0,78.82,50Zm-1.39,1.9a1.4,1.4,0,0,1,2.78,0Z" fill="#00b140"></path>
	                                          <path d="M84,50.72a1.36,1.36,0,0,1,1.18.62l.6-.38A1.92,1.92,0,0,0,84,50a2.21,2.21,0,1,0,0,4.42,2,2,0,0,0,1.77-.93l-.6-.38a1.34,1.34,0,0,1-1.18.62,1.53,1.53,0,0,1,0-3.05Z" fill="#00b140"></path>
	                                          <path d="M88.55,53.8a.64.64,0,0,1-.68-.73V50.72h1.25v-.64H87.87v-.95h-.78v.95h-.74v.64h.74V53.1a1.24,1.24,0,0,0,1.37,1.36,1.52,1.52,0,0,0,1-.3l-.24-.57A1,1,0,0,1,88.55,53.8Z" fill="#00b140"></path>
	                                          <path d="M8.27,31.13H5.08L0,42.59H3.33l.9-2.23H9.07l.9,2.23h3.37ZM5.17,38l1.48-3.66L8.12,38Z" fill="#00b140"></path>
	                                          <path d="M20.51,33.59a3.58,3.58,0,0,1,2.78,1.28L25.35,33a6.25,6.25,0,0,0-5-2.09c-3.71,0-6.38,2.47-6.38,5.95s2.67,6,6.32,6a8.55,8.55,0,0,0,4.81-1.46V36.62H22.23v3.14a4.2,4.2,0,0,1-1.76.38,3.28,3.28,0,0,1,0-6.55Z" fill="#00b140"></path>
	                                          <path d="M35.74,39A3.79,3.79,0,0,0,38,35.37c0-2.62-2-4.24-5.09-4.24H27.66V42.59H30.9v-3h1.77l2.07,3h3.47Zm-3-2H30.9V33.69h1.8c1.34,0,2,.62,2,1.68S34,37,32.7,37Z" fill="#00b140"></path>
	                                          <path d="M52.35,36.86c0-3.43-2.7-5.95-6.36-5.95s-6.37,2.52-6.37,5.95,2.7,6,6.37,6S52.35,40.3,52.35,36.86ZM46,40.14a3.08,3.08,0,0,1-3.1-3.28,3.1,3.1,0,1,1,6.19,0A3.07,3.07,0,0,1,46,40.14Z" fill="#00b140"></path>
	                                          <polygon points="54.59 31.14 54.59 42.59 63.3 42.59 63.3 40.02 57.83 40.02 57.83 31.14 54.59 31.14" fill="#00b140"></polygon>
	                                          <rect x="65.07" y="31.13" width="3.24" height="11.46" fill="#00b140"></rect>
	                                          <polygon points="71.1 31.14 71.1 42.59 74.28 42.59 74.28 36.49 79.33 42.59 82 42.59 82 31.14 78.83 31.14 78.83 37.24 73.77 31.14 71.1 31.14" fill="#00b140"></polygon>
	                                          <polygon points="87.99 40.09 87.99 37.99 93.08 37.99 93.08 35.57 87.99 35.57 87.99 33.64 93.77 33.64 93.77 31.14 84.78 31.14 84.78 42.59 93.98 42.59 93.98 40.09 87.99 40.09" fill="#00b140"></polygon>
	                                          <path d="M15,22.91c.23,0,3.55-1.91,3.65-2.3,1-3.85,3.9-9.33,8.78-11.81,12.21-6.18,22,1.3,22.91,1.93.67.49.82.92.76,1s-.18.33-1.44-.08c-13.43-5.8-23.46.77-23.46.77a25.1,25.1,0,0,0,12.11,9.4c7.48,2.88,15,2.55,22.45-.09A30.82,30.82,0,0,0,75.6,11.32a18.31,18.31,0,0,0,2.11-3.49s.49-1.24-.48-.64a9.09,9.09,0,0,1-7.76,1.1,69.33,69.33,0,0,1-8.64-3.06C56.48,3.47,52.21,1.52,47.57.57A23.36,23.36,0,0,0,31.06,2.88,38.57,38.57,0,0,0,18.29,14.81a19.35,19.35,0,0,0-3.19,6.66C15,21.94,14.84,22.9,15,22.91Z" fill="#00b140"></path>
	                                        </svg></a>								</figure>
							</div>
						</div>
					</div>
				</div>
				<div id="copyright-footer" class="bg-primary text-white">
					<div class="container-fluid">
						<div class="row">
							<div class="col-lg-6 pt-3 pb-lg-3">
								© 2020 fenaco Genossenschaft																	<nav id="copynavi" class="ml-3 d-inline">
																			</nav>
															</div>
							
							<div class="col-lg-6 text-lg-right py-3"><a href="https://www.hnm.ch" target="_blank" class="text-white" rel="noopener">Webentwicklung und Webdesign HNM Winterthur</a></div>
						</div>
					</div>
				</div>
			</footer>
			<div id="hnm-search-overlay" data-search="excluded">
				<div class="container main-container">
					<div class="d-flex hnm-search-close-top-holder">
						<button class="btn al-iconed-link hnm-search-close hnm-search-close-top btn-simple btn-static">
							<span>Schliessen</span> <span class="icon"><svg class="svg svg-times-solid" aria-hidden="true"><use xlink:href="#svg-times-solid"></use></svg></span>						</button>
					</div>
					<div class="row">
						<div class="offset-xl-2 col-xl-8 offset-md-1 col-md-10" id="search">
							<h2 class="hnm-search-title h1 font-weight-normal">Suche</h2>
							<div class="hnm-search-input-holder">
								<label for="hnm-search-search">
									Suchbegriff eingeben								</label>
								<input class="search-input form-control" data-search-group-key="null" id="hnm-search-search">							</div>
							<div data-mdl-search-attribute="search" data-url="https://www.agroline.ch/search-results?nl=de" data-search-fallback="Leider finden wir keine passenden Suchresultate zu Ihrem Suchbegriff." data-search-group-key="null" class="search-result-box" data-search="excluded" id="search-results">									<div class="result-content">
										<ul class="search-result-list"></ul>									</div>
							</div>							<div class="d-flex mt-4">
								<button class="btn btn-white hnm-search-close">
									Schliessen								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<script src="/assets/n2nutil-jquery/jquery-3.7.0.min.js"></script>
<script src="/assets/search/js/search.js"></script>
<script src="/assets/bstmpl/js/animation/gsap.min.js"></script>
<script src="/assets/bstmpl/js/animation/ScrollTrigger.min.js"></script>
<script src="/assets/bstmpl/js/responsive-initializer.js"></script>
<script src="/assets/bstmpl/js/functions.js?v=1.66"></script>
<script src="/assets/slider/js/owl.carousel.js"></script>
<script src="/assets/slider/js/functions.js"></script>
<link rel="stylesheet" type="text/css" media="screen" href="/assets/slider/css/owl.carousel.css">
<link rel="stylesheet" type="text/css" media="screen" href="/assets/slider/css/owl.theme.default.css">


</body></html>';

		$result = HtmlScanner::scan($html);

		$this->assertInstanceOf(HtmlScan::class, $result);
		$this->assertEquals('AGROLINE schützt ihre Kulturen nachhaltig', $result->getTitle());
	}
}