<?php
namespace search\controller;

use n2n\core\container\Transaction;
use n2n\core\container\TransactionManager;
use PHPUnit\Framework\TestCase;
use search\batch\SearchBatchJob;
use search\bo\SearchEntry;
use search\model\dao\SearchEntryDao;
use search\model\UrlCheckStatus;

class SearchBatchJobTest extends TestCase {
	private static $server;
	private static string $baseUrl;
	private static string $requestLog;
	private static string $serverLog;
	private string $lockPath;

	public static function setUpBeforeClass(): void {
		$socket = stream_socket_server('tcp://127.0.0.1:0');
		$address = stream_socket_get_name($socket, false);
		fclose($socket);
		self::$baseUrl = 'http://' . $address;
		self::$requestLog = tempnam(sys_get_temp_dir(), 'search-requests-');
		self::$serverLog = tempnam(sys_get_temp_dir(), 'search-server-');
		$environment = getenv();
		$environment['SEARCH_CHECK_REQUEST_LOG'] = self::$requestLog;
		unset($environment['PHP_CLI_SERVER_WORKERS']);
		self::$server = proc_open([PHP_BINARY, '-S', $address, __DIR__ . '/../fixtures/url-check.php'],
				[0 => ['pipe', 'r'], 1 => ['file', self::$serverLog, 'a'],
					2 => ['file', self::$serverLog, 'a']], $pipes, null, $environment);
		fclose($pipes[0]);
		for ($attempt = 0; $attempt < 100; $attempt++) {
			$connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.05);
			if ($connection !== false) {
				fclose($connection);
				return;
			}
			usleep(10000);
		}
		throw new \RuntimeException('Local HTTP fixture failed to start: ' . file_get_contents(self::$serverLog));
	}

	public static function tearDownAfterClass(): void {
		proc_terminate(self::$server);
		proc_close(self::$server);
		unlink(self::$requestLog);
		unlink(self::$serverLog);
	}

	protected function setUp(): void {
		$this->lockPath = tempnam(sys_get_temp_dir(), 'search-lock-');
		file_put_contents(self::$requestLog, '');
	}

	protected function tearDown(): void {
		unlink($this->lockPath);
	}

	private function job(): TestableSearchBatchJob {
		return new TestableSearchBatchJob($this->lockPath);
	}

	private function entry(string $path): SearchEntry {
		$entry = new SearchEntry('test', self::$baseUrl . $path);
		$entry->setLastChecked(new \DateTime('2000-01-01'));
		return $entry;
	}

	private function transactionManager(): TransactionManager {
		$tx = $this->createMock(Transaction::class);
		$tx->expects($this->once())->method('commit');
		$tx->expects($this->never())->method('rollBack');
		$tm = $this->createMock(TransactionManager::class);
		$tm->expects($this->once())->method('createTransaction')->willReturn($tx);
		return $tm;
	}

	public function testKeepsTemporaryFailuresAndDeletesOnlyMissingUrls(): void {
		$entries = array_map(fn ($path) => $this->entry($path), ['/200', '/301', '/404', '/410', '/403', '/405']);
		$dao = $this->createMock(SearchEntryDao::class);
		$dao->expects($this->once())->method('getSearchEntriesSortedByDate')->with(12)->willReturn($entries);
		$removed = [];
		$dao->expects($this->exactly(2))->method('removeEntry')->willReturnCallback(
				function ($entry) use (&$removed) { $removed[] = $entry; });
		$this->job()->_onNewHour($dao, $this->transactionManager());
		$this->assertSame([$entries[2], $entries[3]], $removed);
		foreach ([0, 1, 4, 5] as $index) {
			$this->assertGreaterThan(new \DateTime('2000-01-01'), $entries[$index]->getLastChecked());
		}
		$requests = array_map(fn ($line) => json_decode($line, true),
				file(self::$requestLog, FILE_IGNORE_NEW_LINES));
		$this->assertSame(['/200', '/301', '/404', '/410', '/403', '/405'], array_column($requests, 'path'));
		$this->assertSame(['HEAD'], array_values(array_unique(array_column($requests, 'method'))));
		$this->assertSame(['MdlSearchBot'], array_values(array_unique(array_column($requests, 'agent'))));
	}

	/** @dataProvider unavailableStatuses */
	public function testStopsWhenWebsiteIsUnavailable(string $path): void {
		$entries = [$this->entry($path), $this->entry('/200')];
		$dao = $this->createMock(SearchEntryDao::class);
		$dao->method('getSearchEntriesSortedByDate')->willReturn($entries);
		$dao->expects($this->never())->method('removeEntry');
		$this->job()->_onNewHour($dao, $this->transactionManager());
		$this->assertGreaterThan(new \DateTime('2000-01-01'), $entries[0]->getLastChecked());
		$this->assertEquals(new \DateTime('2000-01-01'), $entries[1]->getLastChecked());
		$this->assertCount(1, file(self::$requestLog));
	}

	public function unavailableStatuses(): array {
		return [['/500'], ['/503'], ['/429'], ['/timeout']];
	}

	public function testRemovesInvalidStoredUrlsWithoutHttpRequests(): void {
		$entries = [];
		foreach (['/relative', 'ftp://example.org/file'] as $url) {
			$entry = new SearchEntry();
			$property = new \ReflectionProperty(SearchEntry::class, 'urlStr');
			$property->setAccessible(true);
			$property->setValue($entry, $url);
			$entries[] = $entry;
		}
		$dao = $this->createMock(SearchEntryDao::class);
		$dao->method('getSearchEntriesSortedByDate')->willReturn($entries);
		$dao->expects($this->exactly(2))->method('removeEntry');
		$this->job()->_onNewHour($dao, $this->transactionManager());
		$this->assertSame('', file_get_contents(self::$requestLog));
	}

	public function testSkipsWhileAnotherRunnerHoldsTheLock(): void {
		$lock = fopen($this->lockPath, 'c+');
		flock($lock, LOCK_EX);
		try {
			$dao = $this->createMock(SearchEntryDao::class);
			$dao->expects($this->never())->method('getSearchEntriesSortedByDate');
			$tm = $this->createMock(TransactionManager::class);
			$tm->expects($this->never())->method('createTransaction');
			$this->job()->_onNewHour($dao, $tm);
		} finally {
			fclose($lock);
		}
	}

	public function testRunsAtMostOncePerHourEvenWithStaleSchedulerState(): void {
		$dao = $this->createMock(SearchEntryDao::class);
		$dao->expects($this->once())->method('getSearchEntriesSortedByDate')->willReturn([]);
		$tm = $this->createMock(TransactionManager::class);
		$tm->expects($this->never())->method('createTransaction');
		$this->job()->_onNewHour($dao, $tm);
		$this->job()->_onNewHour($dao, $tm);
		$this->assertSame(date('Y-m-d H'), file_get_contents($this->lockPath));
	}

	public function testRunsAgainInANewHour(): void {
		file_put_contents($this->lockPath, '2000-01-01 00');
		$dao = $this->createMock(SearchEntryDao::class);
		$dao->expects($this->once())->method('getSearchEntriesSortedByDate')->willReturn([]);
		$this->job()->_onNewHour($dao, $this->createMock(TransactionManager::class));
		$this->assertSame(date('Y-m-d H'), file_get_contents($this->lockPath));
	}

	public function testReleasesLockAfterAnExceptionAndDoesNotRetryThisHour(): void {
		$dao = $this->createMock(SearchEntryDao::class);
		$dao->expects($this->once())->method('getSearchEntriesSortedByDate')
				->willThrowException(new \RuntimeException('database unavailable'));
		$tm = $this->createMock(TransactionManager::class);
		try {
			$this->job()->_onNewHour($dao, $tm);
			$this->fail('Expected database failure.');
		} catch (\RuntimeException $e) {
			$this->assertSame('database unavailable', $e->getMessage());
		}
		$lock = fopen($this->lockPath, 'c+');
		try {
			$this->assertTrue(flock($lock, LOCK_EX | LOCK_NB));
		} finally {
			fclose($lock);
		}
		$this->job()->_onNewHour($dao, $tm);
	}

	public function testRollsBackWhenAnUpdateFails(): void {
		$dao = $this->createMock(SearchEntryDao::class);
		$dao->method('getSearchEntriesSortedByDate')->willReturn([$this->entry('/404')]);
		$dao->method('removeEntry')->willThrowException(new \RuntimeException('update failed'));
		$tx = $this->createMock(Transaction::class);
		$tx->method('isOpen')->willReturn(true);
		$tx->expects($this->once())->method('rollBack');
		$tx->expects($this->never())->method('commit');
		$tm = $this->createMock(TransactionManager::class);
		$tm->method('createTransaction')->willReturn($tx);
		$this->expectExceptionMessage('update failed');
		$this->job()->_onNewHour($dao, $tm);
	}
}

class TestableSearchBatchJob extends SearchBatchJob {
	private string $lockPath;

	public function __construct(string $lockPath) {
		$this->lockPath = $lockPath;
	}

	protected function getLockFilePath(): string {
		return $this->lockPath;
	}
}