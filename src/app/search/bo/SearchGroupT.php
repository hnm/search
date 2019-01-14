<?php
namespace search\bo;

use n2n\l10n\N2nLocale;
use n2n\persistence\orm\annotation\AnnoManyToOne;
use n2n\reflection\annotation\AnnoInit;
use n2n\reflection\ObjectAdapter;
use rocket\impl\ei\component\prop\translation\Translatable;

/**
 * Translation for {@see SearchGroup}
 * @package search\bo
 */
class SearchGroupT extends ObjectAdapter implements Translatable {
	private static function _annos(AnnoInit $ai) {
		$ai->p('group', new AnnoManyToOne(SearchGroup::getClass()));
	}

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var SearchGroup
	 */
	private $group;

	/**
	 * @var string
	 */
	private $label;

	/**
	 * @var string
	 */
	private $urlStr;

	/**
	 * @var N2nLocale
	 */
	private $n2nLocale;

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
	 * @return SearchGroup
	 */
	public function getGroup() {
		return $this->group;
	}

	/**
	 * @param SearchGroup $group
	 */
	public function setGroup(SearchGroup $group) {
		$this->group = $group;
	}

	/**
	 * @return string
	 */
	public function getLabel() {
		return $this->label;
	}

	/**
	 * @param string $label
	 */
	public function setLabel(string $label) {
		$this->label = $label;
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
	public function getN2nLocale(): N2nLocale {
		return $this->n2nLocale;
	}

	/**
	 * @param N2nLocale $n2nLocale
	 */
	public function setN2nLocale(N2nLocale $n2nLocale) {
		$this->n2nLocale = $n2nLocale;
	}
}