<?php
namespace search\bo;

use n2n\reflection\ObjectAdapter;

/**
 * Used to log searches made.
 * Thereby you can determine what people search and don't find.
 * @package search\bo
 */
class SearchStat extends ObjectAdapter {
	/**
	 * Maximum length for the text field to match database column limit
	 */
	const MAX_TEXT_LENGTH = 255;
	
	private $id;
	private $searchAmount;
	private $text;
	private $resultAmount;

	/**
	 * SearchStat constructor.
	 * @param string|null $text
	 * @param int|null $resultAmount
	 */
	public function __construct(?string $text = null, ?int $resultAmount = null) {
		$this->text = $this->truncateText($text);
		$this->resultAmount = $resultAmount;
		$this->searchAmount = 1;
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
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getText() {
		return $this->text;
	}

	/**
	 * @param string $text
	 */
	public function setText(string $text) {
		$this->text = $this->truncateText($text);
	}

	/**
	 * @return int
	 */
	public function getResultAmount() {
		return $this->resultAmount;
	}

	/**
	 * @param int $resultAmount
	 */
	public function setResultAmount(int $resultAmount) {
		$this->resultAmount = $resultAmount;
	}

	/**
	 * @return int
	 */
	public function getSearchAmount() {
		return $this->searchAmount;
	}

	/**
	 * @param int $searchAmount
	 */
	public function setSearchAmount(int $searchAmount) {
		$this->searchAmount = $searchAmount;
	}
	
	/**
	 * Truncates text to maximum allowed length minus space for "..." at the end of text if truncated
	 * @param string|null $text
	 * @return string|null
	 */
	private function truncateText(?string $text): ?string {
		if ($text === null) {
			return null;
		}
		
		if (strlen($text) <= self::MAX_TEXT_LENGTH) {
			return $text;
		}

		return mb_substr($text, 0, self::MAX_TEXT_LENGTH - 3) . '...';
	}
}