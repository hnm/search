<?php

namespace search\controller;

use PHPUnit\Framework\TestCase;
use search\model\dao\SearchEntryDao;
use search\bo\SearchEntry;
use n2n\l10n\N2nLocale;
use n2n\core\container\TransactionManager;
use search\batch\SearchBatchJob;

class SearchBatchJobTest extends TestCase {

	public function setUp() : void {
	}

	/**
	 * Creates valid URLs for testing (these should return HTTP 200 in real scenarios)
	 */
	private function createValidUrls(int $count): array {
		$urls = [];
		for ($i = 0; $i < $count; $i++) {
			$urls[] = 'https://httpbin.io/cache';
		}
		return $urls;
	}

	/**
	 * Creates invalid URLs for testing (these should fail to resolve)
	 */
	private function createInvalidUrls(int $count): array {
		$urls = [];
		for ($i = 0; $i < $count; $i++) {
			$urls[] = 'https://httpbin.io/status/404?id=' . uniqid();
		}
		return $urls;
	}

	/**
	 * Creates SearchEntry objects with given URLs
	 */
	private function createSearchEntries(array $urls, N2nLocale $locale): array {
		$entries = [];

		foreach ($urls as $index => $url) {
			$isValid = strpos($url, 'google.ch') !== false;
			$title = $isValid ? "Valid Entry $index" : "Invalid Entry $index";
			$entries[] = new SearchEntry($title, $url, $locale, 'test content', 'test', 'test');
		}

		return $entries;
	}

	public function testSearchBatchJobMaxEntries() {
		$this->markTestSkipped('httpbin.io can be flaky.');
		$searchEntryDaoMock = $this->createMock(SearchEntryDao::class);
		$transactionManagerMock = $this->createMock(TransactionManager::class);
		$locale = new N2nLocale('de_CH');

		// Create maximum entries (half valid, half invalid)
		$maxEntries = SearchBatchJob::SEARCH_ENTRY_URL_HEALTH_CHECK_LIMIT;
		$validCount = intval($maxEntries / 2);
		$invalidCount = $maxEntries - $validCount;

		$validUrls = $this->createValidUrls($validCount);
		$invalidUrls = $this->createInvalidUrls($invalidCount);
		$allUrls = array_merge($validUrls, $invalidUrls);

		$searchEntries = $this->createSearchEntries($allUrls, $locale);
		$validEntries = array_slice($searchEntries, 0, $validCount);
		$invalidEntries = array_slice($searchEntries, $validCount);

		// Mock the DAO to return our test entries
		$searchEntryDaoMock->expects($this->once())
				->method('getSearchEntriesSortedByDate')
				->with(SearchBatchJob::SEARCH_ENTRY_URL_HEALTH_CHECK_LIMIT)
				->willReturn($searchEntries);

		// Expect removeEntry to be called for each invalid entry
		$searchEntryDaoMock->expects($this->exactly($invalidCount))
				->method('removeEntry');

		// Mock transaction creation
		$transactionManagerMock->expects($this->exactly(1))
				->method('createTransaction')
				->willReturn($this->createMock(\n2n\core\container\Transaction::class));

		$batchJob = new SearchBatchJob();
		$batchJob->_onNewHour($searchEntryDaoMock, $transactionManagerMock);
	}

	public function testSearchBatchJobSmallAmount() {
		$this->markTestSkipped('httpbin.io can be flaky.');

		$searchEntryDaoMock = $this->createMock(SearchEntryDao::class);
		$transactionManagerMock = $this->createMock(TransactionManager::class);
		$locale = new N2nLocale('de_CH');

		// Create only 5 entries (3 valid, 2 invalid)
		$validUrls = $this->createValidUrls(3);
		$invalidUrls = $this->createInvalidUrls(2);
		$allUrls = array_merge($validUrls, $invalidUrls);

		$searchEntries = $this->createSearchEntries($allUrls, $locale);
		$validEntries = array_slice($searchEntries, 0, 3);
		$invalidEntries = array_slice($searchEntries, 3);

		error_log("=== Small Amount Test ===");
		error_log("Total entries: " . count($searchEntries));
		error_log("Valid entries: " . count($validEntries));
		error_log("Invalid entries: " . count($invalidEntries));

		// Mock the DAO to return our test entries
		$searchEntryDaoMock->expects($this->once())
				->method('getSearchEntriesSortedByDate')
				->with(SearchBatchJob::SEARCH_ENTRY_URL_HEALTH_CHECK_LIMIT)
				->willReturn($searchEntries);

		// Expect removeEntry to be called for each invalid entry
		$searchEntryDaoMock->expects($this->exactly(2))
				->method('removeEntry')
				->willReturnCallback(function($entry) {
					error_log("removeEntry called for: " . $entry->getUrlStr());
				});

		// Mock transaction creation
		$transactionManagerMock->expects($this->exactly(1))
				->method('createTransaction')
				->willReturn($this->createMock(\n2n\core\container\Transaction::class));

		$batchJob = new SearchBatchJob();
		$batchJob->_onNewHour($searchEntryDaoMock, $transactionManagerMock);
		error_log("=== Small Amount Test Complete ===");
	}
}