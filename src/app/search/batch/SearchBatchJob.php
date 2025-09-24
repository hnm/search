<?php
namespace search\batch;

use n2n\context\Lookupable;
use search\model\dao\SearchEntryDao;
use n2n\util\uri\Url;
use n2n\core\container\TransactionManager;
use search\bo\SearchEntry;
use search\model\UrlCheckStatus;

/**
 * Scheduled background job for health monitoring of {@see SearchEntry::$urlStr}'s.
 *
 * {@link SearchEntry}'s with urlStrs that, on lookup don't resolve or have errors must be removed.
 * Designed for hourly execution via cron/scheduler.
 */
class SearchBatchJob implements Lookupable {

	/**
	 * Amount of checks performed when triggered.
	 */
	const SEARCH_ENTRY_URL_HEALTH_CHECK_LIMIT = 200;

	/**
	 * @param SearchEntryDao $searchEntryDao
	 * @param TransactionManager $tm
	 * @return void
	 */
	public function _onNewHour(SearchEntryDao $searchEntryDao, TransactionManager $tm) {
		$searchEntries = $searchEntryDao->getSearchEntriesSortedByDate(self::SEARCH_ENTRY_URL_HEALTH_CHECK_LIMIT);
		if (empty($searchEntries)) return;

		$urlChecks = [];
		foreach ($searchEntries as $searchEntry) {
			try {
				$url = Url::create($searchEntry->getUrlStr());
				if ($url->isRelative()) {
					throw new \InvalidArgumentException();
				}
				$urlChecks[$searchEntry->getUrlStr()] = new UrlCheckStatus($searchEntry, null);
			} catch (\InvalidArgumentException $e) {
				$urlChecks[$searchEntry->getUrlStr()] = new UrlCheckStatus($searchEntry, false);
			}
		}

		$this->checkUrls($urlChecks);

		$now = new \DateTime();
		$this->updateSearchEntries($urlChecks, $searchEntryDao, $tm, $now);
	}

	protected function checkUrls(array $urlChecks): void {
		if (empty($urlChecks)) {
			return;
		}

		$multi = curl_multi_init();
		curl_multi_setopt($multi, CURLMOPT_MAX_TOTAL_CONNECTIONS, 15);
		$handles = [];

		foreach ($urlChecks as $urlCheck) {
			if ($urlCheck->isValid() === false) {
				continue;
			}
			$url = $urlCheck->getSearchEntry()->getUrlStr();
			$ch = curl_init();
			curl_setopt_array($ch, [
					CURLOPT_URL => $url,
					CURLOPT_NOBODY => true,
					CURLOPT_FOLLOWLOCATION => false,
					CURLOPT_TIMEOUT => 2,
					CURLOPT_CONNECTTIMEOUT => 2,
					CURLOPT_USERAGENT => 'MdlSearchBot',
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_SSL_VERIFYHOST => false,
					CURLOPT_DNS_CACHE_TIMEOUT => 300,
					CURLOPT_TCP_NODELAY => true
			]);
			curl_multi_add_handle($multi, $ch);
			$handles[$url] = $ch;
		}

		do {
			curl_multi_exec($multi, $running);
			curl_multi_select($multi);
		} while ($running > 0);

		foreach ($handles as $url => $ch) {
			$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$urlChecks[$url]->setValid($code >= 200 && $code < 400);
			curl_multi_remove_handle($multi, $ch);
			curl_close($ch);
		}

		curl_multi_close($multi);
	}

	/**
	 * @param UrlCheckStatus[] $urlChecks
	 * @param SearchEntryDao $dao
	 * @param TransactionManager $tm
	 * @param \DateTime $now
	 * @return void
	 */
	private function updateSearchEntries(array $urlChecks, SearchEntryDao $dao,
			TransactionManager $tm, \DateTime $now) {
		$tx = $tm->createTransaction();
		foreach ($urlChecks as $urlCheck) {
			if ($urlCheck->isValid()) {
				$urlCheck->getSearchEntry()->setLastChecked($now);
			} else {
				$dao->removeEntry($urlCheck->getSearchEntry());
			}
		}
		$tx->commit();
	}
}