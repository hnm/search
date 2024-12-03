<?php
namespace search\bo;

use n2n\l10n\N2nLocale;
use n2n\persistence\orm\annotation\AnnoId;
use n2n\persistence\orm\annotation\AnnoOneToMany;
use n2n\persistence\orm\CascadeType;
use n2n\persistence\orm\FetchType;
use n2n\reflection\annotation\AnnoInit;
use n2n\reflection\ObjectAdapter;
use rocket\impl\ei\component\prop\translation\Translator;

/**
 * This class gives you the possibility to group {@see search\bo\SearchEntry SearchEntries}.
 * @package search\bo
 */
class SearchGroup extends ObjectAdapter {
	private static function _annos(AnnoInit  $ai) {
		$ai->p('key', new AnnoId(false));
		$ai->p('searchGroupTs', new AnnoOneToMany(SearchGroupT::getClass(), 'group',
				CascadeType::ALL, FetchType::EAGER, true));
		$ai->p('searchEntries', new AnnoOneToMany(SearchEntry::getClass(), 'group', CascadeType::ALL));
	}

	private $key;
	private $searchGroupTs;
	private $searchEntries;

	public function __construct(?string $key = null) {
		$this->key = $key;
		$this->searchGroupTs = new \ArrayObject();
		$this->searchEntries = new \ArrayObject();
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @param string $key
	 */
	public function setKey(string $key) {
		$this->key = $key;
	}

	/**
	 * @return array
	 */
	public function getSearchGroupTs() {
		return $this->searchGroupTs;
	}

	/**
	 * @param SearchEntry $searchEntry
	 */
	public function addSearchEntry(SearchEntry $searchEntry) {
		$this->searchEntries[] = $searchEntry;
	}

	/**
	 * @param mixed $searchGroupTs
	 */
	public function setSearchGroupTs($searchGroupTs) {
		$this->searchGroupTs = $searchGroupTs;
	}

	/**
	 * @return \ArrayObject
	 */
	public function getSearchEntries() {
		return $this->searchEntries;
	}

	/**
	 * @param \ArrayObject $searchEntries
	 */
	public function setSearchEntries(\ArrayObject $searchEntries) {
		$this->searchEntries = $searchEntries;
	}

	/**
	 * @param N2nLocale $n2nLocale
	 * @return SearchGroupT
	 */
	public function t(N2nLocale $n2nLocale) {
		return Translator::find($this->searchGroupTs, $n2nLocale);
	}
}