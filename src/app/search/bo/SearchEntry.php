<?php
namespace search\bo;

use n2n\l10n\N2nLocale;
use n2n\persistence\orm\annotation\AnnoManyToOne;
use n2n\reflection\annotation\AnnoInit;
use n2n\reflection\ObjectAdapter;

/**
 * Class SearchEntry
 * Search Entry is an Entity used to store search texts in connection with the url, title, description and keywords.
 * @package search\bo
 */
class SearchEntry extends ObjectAdapter {
	private static function _annos(AnnoInit $ai) {
		$ai->p('group', new AnnoManyToOne(SearchGroup::getClass()));
	}

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string
	 */
	private $title;

	/**
	 * @var string
	 */
	private $description;

    /**
     * @var string
     */
	private $searchableText;

	/**
	 * @var string
	 */
	private $keywordsStr;

	/**
	 * @var string
	 */
	private $urlStr;

	/**
	 * @var string
	 */
	private $group;

	/**
	 * @var N2nLocale
	 */
	private $n2nLocale;

	/**
	 * @var \DateTime
	 */
	private $lastChecked;

	/**
	 * SearchEntry constructor.
	 * @param string $title
	 * @param string $description
	 * @param string $keywordsStr
	 * @param string $urlStr
	 */
	public function __construct(string $searchableText = null, string $urlStr = null, N2nLocale $n2nLocale = null,
								string $title = null, string $description = null,
								string $keywordsStr = null) {

        $this->searchableText = $searchableText;
	    $this->urlStr = $urlStr;
        $this->n2nLocale = $n2nLocale;
	    $this->title = $title;
		$this->description = $description;
		$this->keywordsStr = $keywordsStr;
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function setId(int $id) {
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param string $title
	 */
	public function setTitle(string $title = null) {
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param string $description
	 */
	public function setDescription(string $description = null) {
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getKeywordsStr() {
		return $this->keywordsStr;
	}

	/**
	 * @param string $keywordsStr
	 */
	public function setKeywordsStr(string $keywordsStr = null) {
		$this->keywordsStr = $keywordsStr;
	}

    /**
     * @return string
     */
    public function getSearchableText() {
        return $this->searchableText;
    }

    /**
     * @param string $searchableText
     */
    public function setSearchableText($searchableText = null) {
        $this->searchableText = $searchableText;
    }

	/**
	 * @return string
	 */
	public function getUrlStr() {
		return $this->urlStr;
	}

	/**
	 * @param string $urlStr
	 */
	public function setUrlStr(string $urlStr) {
		$this->urlStr = $urlStr;
	}

	/**
	 * @return N2nLocale
	 */
	public function getN2nLocale() {
		return $this->n2nLocale;
	}

	/**
	 * @param N2nLocale $n2nLocale
	 */
	public function setN2nLocale(N2nLocale $n2nLocale) {
		$this->n2nLocale = $n2nLocale;
	}

	/**
	 * @return SearchGroup
	 */
	public function getGroup() {
		return $this->group;
	}

	/**
	 * @param string $group
	 */
	public function setGroup(SearchGroup $group = null) {
		$this->group = $group;
	}

	/**
	 * @return \DateTime
	 */
	public function getLastChecked() {
		return $this->lastChecked;
	}

	/**
	 * @param \DateTime $lastChecked
	 */
	public function setLastChecked($lastChecked) {
		$this->lastChecked = $lastChecked;
	}
}