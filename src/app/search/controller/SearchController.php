<?php
namespace search\controller;

use n2n\l10n\N2nLocale;
use n2n\util\StringUtils;
use n2n\web\http\controller\ControllerAdapter;
use n2n\web\http\controller\ParamGet;
use n2n\web\http\PageNotFoundException;
use search\bo\SearchStat;
use search\model\dao\SearchEntryDao;
use search\model\SearchHtmlBuilder;
/**
 * Class SearchController
 * This Controller is registered by default with the url extension search-results.
 * @package search\controller
 */
class SearchController extends ControllerAdapter {
	/**
	 * @var SearchEntryDao
	 */
	private $searchEntryDao;

	private function _init(SearchEntryDao $sed) {
		$this->searchEntryDao = $sed;
	}

	/**
	 * The {@see SearchController::index()}-method of {@see SearchController} is used to send a {@see HtmlResponse}
	 * with an ul of results found.
	 *
	 * @param ParamGet $ss short for searchString
	 * @param ParamGet $nl short for n2nLocale
	 * @param ParamGet $gk short for groupKey
	 * @param ParamGet $stat determines if this search must be in statistics
	 * @param ParamGet $as short for append searchstring (to url)
	 */
	public function index(ParamGet $ss = null, ParamGet $nl = null, ParamGet $gk = null, ParamGet $stat = null, ParamGet $as = null) {
		if ($ss === null || $nl === null) {
			throw new PageNotFoundException();
		}

		if ($gk !== null) {
			$gk = StringUtils::jsonDecode($gk, true);
		}

		$ss = $ss->__toString();
		$nl = N2nLocale::fromWebId((string) $nl);
		
		$appendSearchString = false;
		if (null !== $as) {
			$appendSearchString = $as->toBool();
		}
		
		if (trim($ss) === '') {
			$this->sendHtmlUi(SearchHtmlBuilder::getResultContent(array(), $ss, $nl, $appendSearchString));
			return;
		}

		$ss = trim($ss);

		$foundSearchEntriesSql = $this->searchEntryDao->findSearchEntriesBySearchStr($ss, $nl, $gk);

		$foundSearchEntries = array();
		foreach ($foundSearchEntriesSql as $foundSql) {
			$foundSearchEntries[] = $this->searchEntryDao->getSearchEntryById($foundSql['id']);
		}

		if ($stat !== null && $stat->toBool()) {
			$this->beginTransaction();
			if (null === $searchStat = $this->searchEntryDao->getSearchStatByText($ss)) {
				$this->searchEntryDao->addSearchStat(new SearchStat($ss, count($foundSearchEntries)));
			} else {
				$searchStat->setResultAmount(count($foundSearchEntries));
				$searchStat->setSearchAmount($searchStat->getSearchAmount() + 1);
			}
		}
		
		$this->sendHtmlUi(SearchHtmlBuilder::getResultContent($foundSearchEntries, $ss, $nl, $appendSearchString));
	}

	/**
	 * Opens all UrlStr from {@see search\bo\SearchEntry}
	 */
	/*public function doCleanup() {
		foreach ($this->searchEntryDao->getSearchEntriesSortedByDate() as $searchEntry) {
			$ch = curl_init($searchEntry->getUrlStr());
			curl_exec($ch);
			curl_close($ch);
		}
	}*/
}