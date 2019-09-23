<?php
namespace search\model;

use n2n\impl\web\ui\view\html\HtmlBuilderMeta;
use n2n\impl\web\ui\view\html\HtmlElement;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\l10n\DynamicTextCollection;
use n2n\l10n\N2nLocale;
use n2n\util\StringUtils;
use n2n\web\ui\Raw;
use n2n\web\ui\view\ViewErrorException;
use n2nutil\jquery\JQueryLibrary;
use search\bo\SearchEntry;

/**
 * Class SearchHtmlBuilder
 * Used to build search module related HTML.
 * @package search\model
 */
class SearchHtmlBuilder {
	private $view;
	private $n2nLocale;
	private $currentResultOpenElementName;

	public function __construct(HtmlView $view, $jsTarget = HtmlBuilderMeta::TARGET_BODY_END, bool $addJQuery = true) {
		$this->view = $view;
		$this->n2nLocale = $view->getN2nLocale();

		if ($addJQuery) {
			$this->view->getHtmlBuilder()->meta()->addLibrary(new JQueryLibrary(3));
		}
		$this->view->getHtmlBuilder()->meta()->addJs('js/search.js', 'search', false, false, array(), $jsTarget);
	}

	/**
	 * Prints {@see SearchHtmlBuilder::getInput()}.
	 * @param string $buttonLabel
	 */
	public function input(array $groupKeys = null, array $attrs = null) {
		$this->view->getHtmlBuilder()->out($this->getInput($groupKeys, $attrs));
	}

	/**
	 * Creates a search-input that can be used to enter the searchString
	 * for finding {@see search\bo\SearchEntry} through a ajax call to {@see SearchController::index()}.
	 * @param string|null $groupKey
	 * @return HtmlElement
	 */
	public function getInput(array $groupKeys = null, array $attrs = null) {
		$attrs = HtmlUtils::mergeAttrs(array('class' => 'search-input',
				'data-search-group-key' => StringUtils::jsonEncode($groupKeys)), $attrs);

		return new HtmlElement('input', $attrs);
	}

	/**
	 * Prints the opening element in which found {@see SearchEntry SearchEntries} will be shown.
	 * @param string[] $groupKeys
	 * @param string $elementName
	 * @param array $attrs
	 * @param string $jqueryHideSelector
	 * @param string $fallback
	 */
	public function resultOpen(array $groupKeys = null, string $elementName = 'div', array $attrs = array(), bool $useDefaultCss = true, string $jqueryHideSelector = null, $fallback = '') {
		$this->view->getHtmlBuilder()->out($this->getResultOpen($groupKeys, $elementName, $attrs, $useDefaultCss, $jqueryHideSelector, $fallback));
	}

	/**
	 * Creates the opening element in which found {@see SearchEntry SearchEntries} will be shown.
	 * @param string[] $groupKeys
	 * @param string $elementName
	 * @param array $attrs
	 * @param string $jqueryHideSelector
	 * @param string $fallback
	 * @return Raw
	 */
	public function getResultOpen(array $groupKeys = null, string $elementName = 'div', array $attrs = array(),
			bool $useDefaultCss, string $jqueryHideSelector = null, string $fallback = null) {

		if ($useDefaultCss) {
			$this->view->getHtmlBuilder()->meta()->addCss('css/style.css', null, 'search');
		}

		$this->currentResultOpenElementName = $elementName;

		$request = $this->view->getRequest();
		$url = $request->getHostUrl()->extR($request->getContextPath());

		$url = $url->ext('search-results')->queryExt(array('nl' => $request->getN2nLocale()));

		if ($groupKeys !== null) {
			$url = $url->queryExt(array('gk' => StringUtils::jsonEncode($groupKeys)));
		}

		$attrs = HtmlUtils::mergeAttrs(array('data-jq-search-hide-selector' => $jqueryHideSelector,
				'data-mdl-search-attribute' => Indexer::DATA_ATTRIBUTE_NAME,
				'data-url' => $url,
				'data-search-fallback' => $fallback,
				'data-search-group-key' => StringUtils::jsonEncode($groupKeys),
				'class' => 'search-result-box',
				'data-' . Indexer::DATA_ATTRIBUTE_NAME => Indexer::SEARCH_EXCLUDE), $attrs);

		return new Raw('<' . htmlspecialchars($elementName) . HtmlElement::buildAttrsHtml($attrs) . '>');
	}

	/**
	 * Prints {@see SearchHtmlBuilder::getResultNum()}.
	 */
	public function resultNum(array $attrs = array()) {
		$this->checkResultOpen();
		$this->view->getHtmlBuilder()->out($this->getResultNum($attrs));
	}

	/**
	 * Creates the div where the amount of found {@see search\bo\SearchEntry} will be shown.
	 * @return HtmlElement
	 */
	public function getResultNum(array $attrs = array()) {
		$dtc = new DynamicTextCollection('search', $this->view->getN2nLocale());

		$span = new HtmlElement('span', $attrs, '');
		$span->appendLn(new HtmlElement('span', array('class' => 'search-result-num'), ''));
		$span->appendContent($dtc->translate('search_result_found'));
		return $span;
	}

	/**
	 * Prints {@see SearchHtmlBuilder::getResultCloseButton()}.
	 */
	public function resultCloseButton(string $contentHtml = '<i class="fa fa-times"></i>', array $attrs = array()) {
		$this->checkResultOpen();
		$this->view->getHtmlBuilder()->out($this->getResultCloseButton($contentHtml, $attrs));
	}

	/**
	 * Creates a close button with the class "search-result-close" that hides the result-wrapper in combination with javascript.
	 * @param string $contentHtml
	 * @return HtmlElement
	 */
	public function getResultCloseButton(string $contentHtml = '<i class="fa fa-times"></i>', array $attrs = array()) {
		$attrs = HtmlUtils::mergeAttrs(array('data-' . Indexer::DATA_ATTRIBUTE_NAME => Indexer::SEARCH_EXCLUDE, 'class' => 'search-result-close'), $attrs);
		return new HtmlElement('span', $attrs, new Raw($contentHtml));
	}

	/**
	 * Prints {@see SearchHtmlBuilder::getResultHeader()}.
	 */
	public function resultHeader() {
		$this->checkResultOpen();
		$this->view->getHtmlBuilder()->out($this->getResultHeader());
	}

	/**
	 * Returns a Result header where {@see SearchHtmlBuilder::getResultNum()} and {@see SearchHtmlBuilder::getResultCloseButton}
	 * are combined in a div with the class "search-result-header".
	 * @return HtmlElement
	 */
	public function getResultHeader() {
		$resultHeader = new HtmlElement('div', array('class' => 'search-result-header'));
		$resultHeader->appendContent($this->getResultNum());
		$resultHeader->appendContent($this->getResultCloseButton());
		return $resultHeader;
	}

	/**
	 * Prints {@see SearchHtmlBuilder::getResultContent()}.
	 */
	public function resultContent() {
		$this->checkResultOpen();
		$this->view->getHtmlBuilder()->out(self::getResultContent(null, null, $this->view->getN2nLocale()));
	}

	/**
	 * Creates the div where found {@see search\bo\SearchEntry} will be shown.
	 * @param SearchEntry[] $resultEntries
	 * @param int $numFound
	 * @return HtmlElement
	 */
	public static function getResultContent($resultEntries = array(), string $highlight = null, N2nLocale $n2nLocale) {
		$ul = new HtmlElement('ul', array('class' => 'search-result-list'), '');
		if ($resultEntries === null) return $ul;
		
		$ul->setAttrs(HtmlUtils::mergeAttrs($ul->getAttrs(), array('data-search-found-amount' => count($resultEntries))));
		
		foreach ($resultEntries as $searchEntry) {
			$li = new HtmlElement('li', array('class' => 'search-entry'));
			$h2 = new HtmlElement('h2', array('class' => 'search-entry-title'));
			$h2->appendContent(new HtmlElement('a', array('href' => $searchEntry->getUrlStr()), self::highlight($highlight, $searchEntry->getTitle())));

			$li->appendLn($h2);
			if (!empty($description = self::determineDescription($searchEntry, $highlight))) {
				$li->appendLn(new HtmlElement('p', null, self::highlight($highlight, $description)));
			}

			$li->appendLn(new HtmlElement('a', array('href' => $searchEntry->getUrlStr(), 'class' => 'search-url'), $searchEntry->getUrlStr()));

			if (null !== ($searchGroup = $searchEntry->getGroup())) {
				if (null !== ($searchGroupT = $searchGroup->t($n2nLocale))) {
					$li->appendLn(new HtmlElement('a', array('href' => $searchGroupT->getUrlStr(), 'class' => 'search-group'), $searchGroupT->getLabel()));
				}
			}

			$ul->appendContent($li);
		}

		return $ul;
	}
	
	public static function determineDescription(SearchEntry $searchEntry, string $highlight = null) {
		$description = $searchEntry->getDescription();
		if (!empty($description)) return $description;
		if (null === $highlight) return null;
		
		$numWordsToSafe = 10;
		$wordsBefore = [];
		$wordsAfter = [];
		$foundParts = [];
		$checkNextWord = true;
		
		foreach (explode(' ', trim($searchEntry->getSearchableText())) as $word) {
			if (empty($word)) {
				if (!empty($foundParts)) {
					break;
				}
				$wordsBefore = [];
				continue;
			}
			
			if (empty($foundParts) || $checkNextWord) {
				$checkNextWord = false;
				foreach (explode(' ', trim($highlight)) as $needleWord) {
					if (empty($needleWord) || mb_strpos(strtolower($word), strtolower($needleWord)) === false) continue;
					$foundParts[] = $word;
					$checkNextWord = true;
					break;
				}
				
				if (empty($foundParts)) {
					$wordsBefore[] = $word;
					//include current word
					if (count($wordsBefore) > $numWordsToSafe + 1) {
						array_shift($wordsBefore);
					}
				} elseif (!$checkNextWord) {
					$wordsAfter[] = $word;
				}
				continue;
			}
			
			if (!empty($foundParts)) {
				$wordsAfter[] = $word;
				if (count($wordsAfter) >= $numWordsToSafe) break;
			} 
			
		}
		
		if (empty($foundParts)) return null;
		
		return '...' . implode(' ', array_merge($wordsBefore, $foundParts, $wordsAfter)) . '...';
	}

	/**
	 * Makes text bold that is in the same as needle.
	 * @param $needle
	 * @param $text
	 * @return Raw
	 */
	public static function highlight($needle, $text) {
		$highlightedText = '';

		foreach (explode(' ', trim($text)) as $word) {
			$replaced = false;

			foreach (explode(' ', trim($needle)) as $needleWord) {
				if ($needleWord === '') continue;
				$position = strpos(strtolower($word), strtolower($needleWord));
				if ($position > -1) {
					$startStrPart = '';
					if ($position !== 0) {
						$startStrPart = substr($word, 0, $position);
					}

					$strongStrPart = '<strong>' . substr($word, $position, strlen($needleWord)) . '</strong>';

					$lastStrPart = '';
					if (strlen($startStrPart) + strlen($needleWord) < strlen($word)) {
						$lastStrPart = substr($word, strlen($needleWord) + strlen($startStrPart),
									strlen($word) - strlen($needleWord) - strlen($startStrPart));
					}

					$highlightedText .= $startStrPart
							. $strongStrPart
							. $lastStrPart  . ' ';
					$replaced = true;
					break;
				}
			}
			if (!$replaced) {
				$highlightedText .= $word . ' ';
			}
		}

		return new Raw($highlightedText);
	}

	/**
	 * closes the result wrapper opened with {@see SearchHtmlBuilder::getResultClose()}
	 * @return Raw
	 */
	public function getResultClose() {
		return new Raw('</' . htmlspecialchars($this->currentResultOpenElementName) . '>');
		$this->currentResultOpenElementName = '';
	}

	/**
	 * Prints {@see SearchHtmlBuilder::getResultClose()}.
	 */
	public function resultClose() {
		$this->view->getHtmlBuilder()->out($this->getResultClose());
	}

	private function checkResultOpen() {
		if ($this->currentResultOpenElementName === null) {
			throw new ViewErrorException('SearchContainer is not open');
		}
	}
}
