<?php
namespace search\batch;

use n2n\context\Lookupable;
use n2n\core\N2N;
use n2n\core\container\TransactionManager;
use n2n\log4php\Logger;
use n2n\util\io\IoUtils;
use n2n\util\uri\Url;
use search\bo\SearchEntry;
use search\model\dao\SearchEntryDao;
use search\model\UrlCheckStatus;
use n2n\util\io\IoResourceException;

/**
 * Checks a small batch of search URLs hourly. Only confirmed missing URLs are removed.
 */
class SearchBatchJob implements Lookupable {

	const SEARCH_ENTRY_URL_HEALTH_CHECK_LIMIT = 12;

	public function _onNewHour(SearchEntryDao $searchEntryDao, TransactionManager $tm): void {
		$lock = IoUtils::fopen($this->getLockFilePath(), 'c+');
		try {
			$wouldBlock = false;
			if (!flock($lock, LOCK_EX | LOCK_NB, $wouldBlock)) {
				if ($wouldBlock) return;
				throw new \RuntimeException('Could not lock the search URL health check.');
			}

			$hour = date('Y-m-d H');
			if (trim(stream_get_contents($lock)) === $hour) return;

			rewind($lock);
			IoUtils::ftruncate($lock, 0);
			if (IoUtils::fwrite($lock, $hour) !== strlen($hour) || !fflush($lock)) {
				throw new \RuntimeException('Could not record the search URL health check hour.');
			}

			$urlChecks = [];
			foreach ($searchEntryDao->getSearchEntriesSortedByDate(self::SEARCH_ENTRY_URL_HEALTH_CHECK_LIMIT)
					as $searchEntry) {
				$urlCheck = $this->checkUrl($searchEntry);
				$urlChecks[] = $urlCheck;

				$code = $urlCheck->getHttpStatus();
				if ($code === 0 || $code === 429 || $code >= 500) break;
			}

			if ($urlChecks) {
				$this->updateSearchEntries($urlChecks, $searchEntryDao, $tm);
			}
		} catch (IoResourceException $e) {
			throw new \RuntimeException('Could not record the search URL health check hour.');
		} finally {
			fclose($lock);
		}
	}

	protected function getLockFilePath(): string {
		return (string) N2N::getVarStore()->requestFileFsPath('tmp', 'search', null,
				'url-health-check.lock', true, false, false);
	}

	protected function checkUrl(SearchEntry $searchEntry): UrlCheckStatus {
		$urlCheck = new UrlCheckStatus($searchEntry, null);
		try {
			$url = Url::create($searchEntry->getUrlStr());
			if ($url->isRelative() || !in_array(strtolower((string) $url->getScheme()),
					['http', 'https'], true)) {
				throw new \InvalidArgumentException('Expected an absolute HTTP URL.');
			}
		} catch (\InvalidArgumentException $e) {
			$urlCheck->setValid(false);
			return $urlCheck;
		}

		$ch = curl_init();
		if ($ch === false) throw new \RuntimeException('Could not initialize the search URL health check.');
		try {
			if (!curl_setopt_array($ch, [
				CURLOPT_URL => $searchEntry->getUrlStr(),
				CURLOPT_NOBODY => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_TIMEOUT => 2,
				CURLOPT_CONNECTTIMEOUT => 2,
				CURLOPT_USERAGENT => 'MdlSearchBot'
			])) {
				throw new \RuntimeException('Could not configure the search URL health check.');
			}
			$code = curl_exec($ch) === false ? 0 : (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$urlCheck->setHttpStatus($code);
			if ($code >= 200 && $code < 400) {
				$urlCheck->setValid(true);
			} elseif (in_array($code, [404, 410], true)) {
				$urlCheck->setValid(false);
			}
		} finally {
			curl_close($ch);
		}
		return $urlCheck;
	}

	/** @param UrlCheckStatus[] $urlChecks */
	private function updateSearchEntries(array $urlChecks, SearchEntryDao $dao, TransactionManager $tm): void {
		$tx = $tm->createTransaction();
		try {
			$now = new \DateTime();
			foreach ($urlChecks as $urlCheck) {
				$entry = $urlCheck->getSearchEntry();
				if ($urlCheck->isValid() === false) {
					$dao->removeEntry($entry);
				} else {
					$entry->setLastChecked($now);
				}
				if ($urlCheck->isValid() !== true) {
					Logger::getLogger(self::class)->info('[mdl-search] URL health check '
							. ($urlCheck->isValid() === false ? 'removed' : 'deferred')
							. ': ' . $entry->getUrlStr() . '; HTTP status: ' . $urlCheck->getHttpStatus());
				}
			}
			$tx->commit();
		} finally {
			if ($tx->isOpen()) $tx->rollBack();
		}
	}
}
