<?php
namespace search\controller;

use n2n\context\Lookupable;
use search\model\dao\SearchEntryDao;
use n2n\util\uri\Url;
use n2n\core\N2N;
use n2n\web\http\Request;
use n2n\util\ex\IllegalStateException;
use n2n\core\container\TransactionManager;
use http\Exception\InvalidArgumentException;

class SearchBatchJob implements Lookupable {
	private $request;
	private $tm;
	
	private function _init(Request $request, TransactionManager $tm) {
		$this->request = $request;
		$this->tm = $tm;
	}
	
	public function _onNewHour(SearchEntryDao $searchEntryDao) {
		$count = ceil($searchEntryDao->getNumSearchEntries() / 20);
		foreach ($searchEntryDao->getSearchEntriesSortedByDate($count) as $searchEntry) {
			try {
				if ($this->isStatusOk(Url::create($searchEntry->getUrlStr()))) {
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
	
	private function isStatusOk(Url $url) {
		$headers = get_headers((string) $url);
		if (false === $headers) return false;
		
		return substr($headers[0], 9, 3) === '200';
	}
}
