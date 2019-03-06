<?php
namespace search\model\dao;

use n2n\context\RequestScoped;
use n2n\l10n\N2nLocale;
use n2n\persistence\orm\EntityManager;
use n2n\util\uri\Url;
use search\bo\SearchEntry;
use search\bo\SearchGroup;
use search\bo\SearchStat;

/**
 * Class SearchEntryDao
 * Used to find SearchEntries
 * @package search\model\dao
 */
class SearchEntryDao implements RequestScoped {
	/**
	 * @var EntityManager
	 */
	private $em;

	private function _init(EntityManager $em) {
		$this->em = $em;
	}

	/**
	 * Adds a SearchEntry
	 * @param SearchEntry $searchEntry
	 */
	public function addEntry(SearchEntry $searchEntry) {
		$this->em->persist($searchEntry);
	}

	/**
	 * Adds a SearchStat to the database for later analyzing
	 * @param SearchStat $searchStat
	 */
	public function addSearchStat(SearchStat $searchStat) {
		$this->em->persist($searchStat);
	}

	/**
	 * Removes a SearchEntry
	 * @param SearchEntry $searchEntry
	 */
	public function removeEntry(SearchEntry $searchEntry) {
		$this->em->remove($searchEntry);
	}

	public function getSearchEntries() {
		return $this->em->createSimpleCriteria(SearchEntry::getClass())->toQuery()->fetchArray();
	}

	public function getSearchEntryById(int $id) {
		return $this->em->find(SearchEntry::getClass(), $id);
	}

	public function getSearchEntriesByGroups(array $groupKeys) {

		$params = [];
		$groupKeyClause = '';
		$lastGroupKey = end($groupKeys);
		foreach ($groupKeys as $i => $groupKey) {
			$i = (int) $i;

			$params[':groupKey' . $i] = $groupKey;
			$groupKeyClause .= 'se.groupKey = ' . ':groupKey' . $i;

			if (!$lastGroupKey) {
				$groupKeyClause . ' OR ';
			}
		}

		return $this->em->createNqlCriteria('SELECT se FROM SearchEntry se
				WHERE ' . $groupKeyClause, $params)->toQuery()->fetchArray();
	}
	
	/**
	 * Returns Search entries sorted by lastChecked date.
	 * @return SearchEntry[]
	 */
	public function getSearchEntriesSortedByDate(int $num = null) {
		return $this->em->createSimpleCriteria(SearchEntry::getClass(), null, 
				array('lastChecked' => 'ASC'), $num)->toQuery()->fetchArray();
	}

	/**
	 * @param string $urlStr
	 * @param N2nLocale $n2nLocale
	 * @return SearchEntry
	 */
	public function getSearchEntryByUrl(Url $url) {
		return $this->em->createSimpleCriteria(SearchEntry::getClass(), array('urlStr' => (string) $url))->toQuery()->fetchSingle();
	}

	/**
	 * Searches the search_entry database table for matching results.
	 *
	 * @param string $searchStr
	 * @param N2nLocale $n2nLocale
	 * @param string[] groupKeys
	 */
	public function findSearchEntriesBySearchStr(string $searchStr, N2nLocale $n2nLocale, array $groupKeys = null) {
		if ($searchStr === '') return array();

		$params = array(':n2nLocale' => $n2nLocale->getId(), ':searchStr' => $searchStr);
		$groupKeyAnd = '';
		if ($groupKeys !== null) {
			$groupKeyAnd = 'AND (';

			$lastGroupKeyAnd = end($groupKeys);
			$i = 0;
			foreach ($groupKeys as $groupKey) {
				$params[':groupKey' . $i] = $groupKey;

				$groupKeyAnd .= 'se.group_key = ' . ':groupKey' . $i;
				if (++$i != count($groupKeys)) {
					$groupKeyAnd .= ' OR ';
				}
			}


			$groupKeyAnd .= ')';
		}

		$partsStr = '';
		foreach (preg_split('/\s+/', $searchStr) as $i => $part) {
			$i = (int) $i;

			$params[':parts' . $i] = '%' . $part . '%';
			if ($part === '') continue;
			if ($i === 0) {
				$word = '';
			} else {
				$word = PHP_EOL . 'AND';
			}
			$partsStr .= $word . ' (se.keywords_str LIKE ' . ':parts' . $i
				. ' OR se.searchable_text LIKE ' . ':parts' . $i . ')';
		}

		$prepareStatement = $this->em->getPdo()->prepare('SELECT *,
				MATCH (se.searchable_text) AGAINST (:searchStr) AS text_score,
				MATCH (se.keywords_str) AGAINST (:searchStr) AS keywords_score
				FROM search_entry se 
				WHERE se.n2n_locale = :n2nLocale ' . $groupKeyAnd .'
				AND ((' . $partsStr . ')
					OR MATCH(se.searchable_text) AGAINST (:searchStr) > 0
					OR MATCH(se.keywords_str) AGAINST (:searchStr) > 0
				)
				ORDER BY keywords_score DESC, text_score DESC 
				LIMIT 10');

		$prepareStatement->execute($params);
		return $prepareStatement->fetchAll();
	}

	/**
	 * @param string|null $groupKey
	 * @return mixed|null
	 */
	public function getSearchGroupByKey(string $groupKey = null) {
		return $this->em->createSimpleCriteria(SearchGroup::getClass(), array('key' => $groupKey))->toQuery()->fetchSingle();
	}

	/**
	 * @param string $text
	 * @return mixed|null
	 */
	public function getSearchStatByText(string $text) {
		return $this->em->createSimpleCriteria(SearchStat::getClass(), array('text' => $text))->toQuery()->fetchSingle();
	}
}