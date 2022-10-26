<?php
namespace search\controller;

use n2n\context\Lookupable;
use search\model\dao\SearchEntryDao;
use n2n\util\uri\Url;
use n2n\core\N2N;
use n2n\web\http\Request;
use n2n\util\ex\IllegalStateException;
use n2n\core\container\TransactionManager;

class SearchBatchJob implements Lookupable {
	private $request;
	private $tm;
	
	private function _init(Request $request, TransactionManager $tm) {
		$this->request = $request;
		$this->tm = $tm;
	}
	
	public function _onNewHour(SearchEntryDao $searchEntryDao) {
		foreach ($searchEntryDao->getSearchEntriesSortedByDate(3) as $searchEntry) {
			try {
				if ($this->isStatusOk($this->determineUrl($searchEntry->getUrlStr()))) {
					$tx = $this->tm->createTransaction();
					$searchEntry->setLastChecked(new \DateTime());
					$tx->commit();
					continue;
				}
			} catch (\InvalidArgumentException $e) {}
			
			$tx = $this->tm->createTransaction();
			$searchEntryDao->removeEntry($searchEntry);
			$tx->commit();
		}
	}
	
	private function determineUrl(string $urlStr) {
		$url = Url::create($urlStr);
		if (!$url->isRelative()) return $url;
		if (N2N::isHttpContextAvailable()) {
			return $this->request->getHostUrl()->ext($url);
		}
		
		throw new IllegalStateException('Search batch job needs http-context');
	}
	
	private function isStatusOk(Url $url) {
		$headers = @get_headers((string) $url);
		if (false === $headers) return false;
		
		return substr($headers[0], 9, 3) === '200';
	}
}
