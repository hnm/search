<?php
namespace search\html\bo;

/**
 * HtmlScan is a data-class used to manage data that is found in html views
 * @package search\model
 */
class HtmlScan {
    /**
     * @var array
     */
    private $htmlTags = array();

    /**
     * @var string[] $keywordsStr
     */
    private $keywordsStr;

    /**
     * @var string $title
     */
    private $title;

    /**
     * @var string
     */
    private $description;
    /**
     * @var string
     */
    private string $searchableStr;

    /**
     * @return array
     */
    public function getHtmlTags() {
        return $this->htmlTags;
    }

    /**
     * @param array $htmlTags
     */
    public function setHtmlTags($htmlTags) {
        $this->htmlTags = $htmlTags;
    }

    /**
     * @return string[]
     */
    public function getKeywordsStr() {
        return $this->keywordsStr;
    }

    /**
     * @param string[] $keywordsStr
     */
    public function setKeywordsStr($keywordsStr) {
        $this->keywordsStr = $keywordsStr;
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
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getSearchableStr() {
        return $this->searchableStr;
    }

    /**
     * @param string $searchableStr
     */
    public function setSearchableStr($searchableStr) {
        $this->searchableStr = $searchableStr;
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
    public function setDescription($description) {
        $this->description = $description;
    }
}