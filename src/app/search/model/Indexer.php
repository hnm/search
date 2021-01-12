<?php
namespace search\model;

use n2n\core\container\TransactionManager;
use n2n\web\http\Response;
use search\bo\SearchGroup;
use search\html\model\HtmlScanner;
use search\html\bo\HtmlScan;
use n2n\context\RequestScoped;
use n2n\l10n\N2nLocale;
use n2n\util\uri\Url;
use n2n\web\ui\view\View;
use search\bo\SearchEntry;
use search\IndexerException;
use search\model\dao\SearchEntryDao;
use rocket\core\model\Rocket;

/**
 * Class Indexer is used to create and persist {@see SearchEntry}.
 * It is able to do this with the usual parameters for {@see SearchEntry} or can scan HTML and read the contents to build
 * $searchableStr, $title, $description and keywordsStr for {@see SearchEntry}
 * @package search\model
 * @author Nikolai Schmid <schmid@hnm.ch>
 */
class Indexer implements RequestScoped {
	/**
	 * The data attribute that is searched for in javascript
	 */
	const DATA_ATTRIBUTE_NAME = 'search';

	/**
	 * The content exclude keyword that is used for {@see Indexer::DATA_ATTRIBUTE}
	 * If this is set, content in the html tags will not be found.
	 */
	const SEARCH_EXCLUDE = 'excluded';

	/**
	 * The content include keyword that is used for {@see Indexer::DATA_ATTRIBUTE}
	 * If this is set, content in the html tags will be found.
	 */
	const SEARCH_INCLUDED = 'included';

	/**
	 * @var SearchEntryDao
	 */
	private $sed;
	/**
	 * @var Response
	 */
	private $response;
	/**
	 * @var TransactionManager
	 */
	private $tm;
	/**
	 * @var Rocket
	 */
	private $rocket;

	private function _init(SearchEntryDao $sed, Response $response, TransactionManager $tm, Rocket $rocket) {
		$this->sed = $sed;
		$this->response = $response;
		$this->tm = $tm;
		$this->rocket = $rocket;
	}

	/**
	 * Persists a {@see SearchEntry} to the database
	 * @param SearchEntry $searchEntry
	 */
	public function addEntry(SearchEntry $searchEntry) {
		$tx = null;
		if (!$this->tm->hasOpenTransaction()) {
			$tx = $this->tm->createTransaction();
		}
		
		$this->sed->addEntry($searchEntry);
		
		if ($tx !== null) {
			$tx->commit();
		}
	}

	/**
	 * Removes a {@see SearchEntry} from the database
	 * @param SearchEntry $searchEntry
	 */
	public function removeEntry(SearchEntry $searchEntry) {
		$tx = $this->tm->createTransaction();
		$this->sed->removeEntry($searchEntry);
		$tx->commit();
	}

	/**
	 * Creates and persists a {@see SearchEntry}, that is then persisted to the database
	 * @param Url $url
	 * @param string $title
	 * @param string|null $searchableText
	 * @param string|null $keywordsStr
	 * @param string|null $lead
	 * @param N2nLocale $n2nLocale
	 * @return SearchEntry
	 */
	public function add($url, string $title,  N2nLocale $n2nLocale, string $searchableText = null,
			string $keywordsStr = null, string $lead = null, array $allowedQueryParams = array(), string $groupKey = null) {
		Url::create($url);
		$searchEntry = $this->create($searchableText, $url, $n2nLocale, $allowedQueryParams, $groupKey, $title, $lead, $keywordsStr);
		$this->addEntry($searchEntry);
	}

	/**
	 * Creates and persists a {@see SearchEntry} by scanning Html.
	 * If $autoTitle, $autoKeywords and $autoDescription is true, the method will determine the data by the corresponding meta-tags
	 * @param string|Url $url
	 * @param string $title
	 * @param string $searchableHtml
	 * @param bool $autoTitle
	 * @param bool $autoKeywords
	 * @param bool $autoDescription
	 */
	public function addFromHtml($url, string $searchableHtml,  N2nLocale $n2nLocale,
			array $allowedQueryParams = array(), string $groupKey = null,
			bool $autoTitle = true, bool $autoKeywords = true, bool $autoDescription = true) {
		$url = Url::create($url);

		$searchEntry = $this->createFromHtml($url, $allowedQueryParams, $searchableHtml, $n2nLocale, $groupKey, $autoTitle, $autoKeywords, $autoDescription);
		$this->addEntry($searchEntry);
		return $searchEntry;
	}

	/**
	 * Creates and persists a {@see SearchEntry} by scanning a {@see HtmlView}.
	 * If $autoTitle, $autoKeywords and $autoDescription is true, the method will determine the data by the corresponding meta-tags
	 * @param Url $url
	 * @param View $view
	 * @param bool $autoTitle
	 * @param bool $autoKeywords
	 * @param bool $autoDescription
	 */
	public function addFromHtmlView(View $view, array $allowedQueryParams = array(), string $groupKey = null,
				bool $autoTitle = true, bool $autoKeywords = true, bool $autoDescription = true) {
		if ($this->rocket->isActive()) {
			return null;
		}
		
		$searchEntry = $this->createFromHtml($view->getRequest()->getUrl(), $allowedQueryParams, $view->getContents(), $view->getN2nLocale(), $groupKey, $autoTitle, $autoKeywords, $autoDescription);
		
		$this->addEntry($searchEntry);

		return $searchEntry;
	}

	/**
	 * Add from Response can only be used right after {@see ControllerAdapter::forward}
	 * @return SearchEntry
	 */
	public function addFromResponse(array $allowedParams = array(), string $groupKey = null, $autoTitle = true, $autoKeywords = true, $autoDescription = true) {
		if ($this->rocket->isActive()) {
			return null;
		}
		
		if ($this->response->getSentPayload() === null) {
			throw new IndexerException('Add by Response can only be executed right after ControllingUtilsTrait::forward()');
		}

		return $this->addFromHtmlView($this->response->getSentPayload(), $allowedParams, $groupKey, $autoTitle, $autoKeywords, $autoDescription);
	}

	/**
	 * Deletes all {@link SearchEntry SearchEntries}
	 */
	public function truncate() {
		$tx = $this->tm->createTransaction();

		foreach ($this->sed->getSearchEntries() as $searchEntry) {
			$this->sed->removeEntry($searchEntry);
		}

		$tx->commit();
	}

	/**
	 * Deletes all {@link SearchEntry SearchEntries} that belong to one o the groups.
	 * @param string[] $groupsf
	 */
	public function truncateByGroups(array $groups) {
		$tx = $this->tm->createTransaction();

		foreach ($this->sed->getSearchEntriesByGroups($groups) as $searchEntry) {
			$this->sed->removeEntry($searchEntry);
		}

		$tx->commit();
	}

	/**
	 * Scans a {@see HtmlScan} for searchable text.
	 * @param HtmlScan $htmlScan
	 * @return string
	 */
	private function scanSearchableText(HtmlScan $htmlScan) {
		$searchableText = '';

		foreach ($htmlScan->getHtmlTags() as $htmlTag) {
			$enabled = true;
			$stop = false;

			$htmlTagsToCheck = $htmlTag->findAllParents();
			array_unshift($htmlTagsToCheck, $htmlTag);
			foreach ($htmlTagsToCheck as $parent) {
				foreach ($parent->getAttributes() as $htmlAttribute) {
					if ($htmlAttribute->getName() === 'data-search') {
						$stop = true;

						if ($htmlAttribute->getValue() === Indexer::SEARCH_EXCLUDE) {
							$enabled = false;
						} elseif ($htmlAttribute->getValue() === Indexer::SEARCH_INCLUDED) {
							$enabled = true;
						}
						break;
					}
				}
				if ($stop) break;
			}

			if ($enabled) {
				$searchableText .= trim($htmlTag->getText()) . ' ';
			}
		}

		return $searchableText;
	}

	private function stripProhibitedParams(array $queryParams = array(), array $allowedParams = array()) {
		if ($queryParams === null && $allowedParams === null) {
			return array();
		}

		$allowedQueryParams = array();

		foreach ($queryParams as $queryKey => $query) {
			if (in_array($queryKey, $allowedParams)) {
				$allowedQueryParams[$queryKey] = $query;
			}
		}

		return $allowedQueryParams;
	}

	private function create($searchableText, $url, $n2nLocale, array $allowedParams = array(), $groupKey = null, $title = null, $lead = null, $keywordsStr = null) {
		$url = Url::create($url);
		$url = $url->chQuery($this->stripProhibitedParams($url->getQuery()->toArray(), $allowedParams));

		$searchEntry = $this->sed->getSearchEntryByUrl($url);

		if ($searchEntry === null) {
			$searchEntry = new SearchEntry($searchableText, $url, $n2nLocale, $this->getOrCreateGroupByKey($groupKey), $title, $lead, $keywordsStr);
		}
		$searchEntry->setSearchableText($searchableText);

		return $searchEntry;
	}

	private function getOrCreateGroupByKey(string $key = null) {
		if (null === $key) return null;
		$group = $this->sed->getSearchGroupByKey($key);
		if (null !== $group) return $group;

		return new SearchGroup($key);
	}

	private function createFromHtml($url, array $allowedQueryParams, string $searchableHtml,
			N2nLocale $n2nLocale, string $groupKey = null, bool $autoTitle = true,
			bool $autoKeywords = true, bool $autoDescription = true) {
		$url = Url::create($url);

		$htmlScan = HtmlScanner::scan($searchableHtml);
		$searchEntry = $this->create($this->scanSearchableText($htmlScan), $url, $n2nLocale, $allowedQueryParams);
		if (null !== ($group = $this->getOrCreateGroupByKey($groupKey))) {
			$group->addSearchEntry($searchEntry);
			$searchEntry->setGroup($group);
		}

		$title = null;
		if ($autoTitle) {
			$title = htmlspecialchars_decode($htmlScan->getTitle());
		}
		$searchEntry->setTitle($title);

		$desc = null;
		if ($autoDescription) {
			$desc = htmlspecialchars_decode($htmlScan->getDescription());
		}
		$searchEntry->setDescription($desc);

		$keywordStr = null;
		if ($autoKeywords) {
			$keywordStr = htmlspecialchars_decode($htmlScan->getKeywordsStr());
		}
		$searchEntry->setKeywordsStr($keywordStr);

		return $searchEntry;
	}
}
