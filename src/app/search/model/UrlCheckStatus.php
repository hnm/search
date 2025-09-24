<?php
namespace search\model;


use search\bo\SearchEntry;

class UrlCheckStatus {
	private SearchEntry $searchEntry;
	private ?bool $valid;

	/**
	 * @param SearchEntry $searchEntry
	 * @param bool $valid
	 */
	public function __construct(SearchEntry $searchEntry, ?bool $valid) {
		$this->searchEntry = $searchEntry;
		$this->valid = $valid;
	}

	public function getSearchEntry(): SearchEntry {
		return $this->searchEntry;
	}

	public function isValid(): ?bool {
		return $this->valid;
	}

	public function setValid(?bool $valid): void {
		$this->valid = $valid;
	}
}