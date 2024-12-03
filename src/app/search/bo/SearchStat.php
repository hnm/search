<?php
namespace search\bo;

use n2n\reflection\ObjectAdapter;

/**
 * Used to log searches made.
 * Thereby you can determine what people search and don't find.
 * @package search\bo
 */
class SearchStat extends ObjectAdapter {
	private $id;
	private $searchAmount;
	private $text;
	private $resultAmount;

	/**
	 * SearchStat constructor.
	 * @param string $text
	 * @param int $resultAmount
	 */
	public function __construct(?string $text = null, ?int $resultAmount = null) {
		$this->text = $text;
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
		$this->text = $text;
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
}