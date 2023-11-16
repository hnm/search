<?php
namespace search\html\model;

use search\html\bo\HtmlScan;
use search\html\bo\HtmlTag;
use n2n\util\StringUtils;
use n2n\util\type\CastUtils;
use search\model\Indexer;
use n2n\web\http\controller\impl\ValResult;

class HtmlScanner {
	const SELF_CLOSING_HTML_TAGS = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
	const EXCLUDE_HTML_TAGS = ['script'];
	private static ?int $excludedLvl = null;

	public static function scan(string $htmlStr): HtmlScan {
		$htmlScan = new HtmlScan();
		self::$excludedLvl = null;
		$curLvl = 0;
		$inStrChar = '';
		$escaped = false;
		$htmlTags = [];
		$htmlTagLvls = [];
		$htmlTagDefinitionStr = '';
		$inHtmlTagDefinition = false;
		$currentHtmlTagRawText = '';

		// Pre-process the HTML string to remove comments and certain tags
		$htmlStr = preg_replace('/<!--.*?-->/', '', $htmlStr);
		$htmlStr = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $htmlStr);
		$htmlStr = preg_replace('/<noscript\b[^>]*>(.*?)<\/noscript>/is', "", $htmlStr);
		$htmlStr = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $htmlStr);

		foreach (str_split($htmlStr) as $char) {
			$escaped = self::determineEscaped($char, $escaped);
			$inStrChar = self::determineInStrChar($escaped, $char, $inStrChar, $inHtmlTagDefinition, end($htmlTags) === false ? null : end($htmlTags));
			self::processChar($char, $curLvl, $inStrChar, $escaped, $htmlTagLvls, $htmlTagDefinitionStr, $inHtmlTagDefinition, $htmlTags, $currentHtmlTagRawText, $htmlScan);
		}

		$htmlScan->setHtmlTags($htmlTags);

		return $htmlScan;
	}

	private static function determineEscaped(string $char, bool $escaped): bool {
		return $char === '\\' && !$escaped;
	}

	private static function determineInStrChar(bool $escaped, string $char, string $inStrChar, bool $inHtmlTagDefinition, ?HtmlTag $lastHtmlTag): string {
		if ($inHtmlTagDefinition || !$lastHtmlTag || $lastHtmlTag->getName() !== 'script') {
			return $inStrChar;
		}

		if (!$escaped && ($char === "'" || $char === '"')) {
			return $inStrChar === '' ? $char : ($inStrChar === $char ? '' : $inStrChar);
		}

		return $inStrChar;
	}

	private static function processChar(string $char, int &$curLvl, string $inStrChar, bool $escaped, array &$htmlTagLvls, string &$htmlTagDefinitionStr, bool &$inHtmlTagDefinition, array &$htmlTags, string &$currentHtmlTagRawText, HtmlScan $htmlScan) {
		if ($char === '<' && !$inStrChar && !$escaped) {
			if ($inHtmlTagDefinition === false) {
				self::processTagText($curLvl, $htmlTagLvls, $currentHtmlTagRawText, $htmlScan);
				$currentHtmlTagRawText = '';
			}
			$inHtmlTagDefinition = true;
		}

		if ($char === '>' && $inHtmlTagDefinition && !$escaped && !$inStrChar) {
			self::processTagEnd($char, $curLvl, $htmlTagDefinitionStr, $htmlTagLvls, $htmlTags, $inHtmlTagDefinition, $htmlScan);
			$htmlTagDefinitionStr = '';
		}

		if ($inHtmlTagDefinition) {
			$htmlTagDefinitionStr .= $char;
		} else {
			$currentHtmlTagRawText .= $char;
		}
	}

	private static function processTagText(int $curLvl, array $htmlTagLvls, string $currentHtmlTagRawText, HtmlScan $htmlScan) {
		if (isset($htmlTagLvls[$curLvl - 1])) {
			$tag = end($htmlTagLvls[$curLvl - 1]);
			CastUtils::assertTrue($tag instanceof HtmlTag);

			$cleanedText = ltrim($currentHtmlTagRawText, '>');;
			if (!in_array($tag->getName(), self::EXCLUDE_HTML_TAGS)) {
				$tag->addText(html_entity_decode(trim($cleanedText)));
				if (self::$excludedLvl === null) {
					$htmlScan->setSearchableStr(trim($htmlScan->getSearchableStr() . ' ' . trim($cleanedText)));
				}
			}
		}
	}


	private static function processTagEnd(string $char, int &$curLvl, string &$htmlTagDefinitionStr, array &$htmlTagLvls, array &$htmlTags, bool &$inHtmlTagDefinition, HtmlScan $htmlScan) {
		$htmlTagDefinitionStr .= $char;

		$isTagOpening = StringUtils::startsWith('<', $htmlTagDefinitionStr) && !StringUtils::startsWith('</', $htmlTagDefinitionStr);
		$isTagClosing = StringUtils::startsWith('</', $htmlTagDefinitionStr);

		if (self::$excludedLvl === null) {
			if (!$isTagClosing) {
				$htmlTag = HtmlTag::create($htmlTagDefinitionStr);
				if ($htmlTag->getName() === 'meta') {
					self::processMetaTag($htmlTag, $htmlScan);
				}
				$htmlTagLvls[$curLvl][] = $htmlTag;
				$htmlTags[] = $htmlTag;

				if (!in_array($htmlTag->getName(), self::SELF_CLOSING_HTML_TAGS)) {
					$curLvl++;
				}
			}
		}

		if ($isTagOpening && self::isTagExcluded($htmlTagDefinitionStr) && self::$excludedLvl === null) {
			self::$excludedLvl = $curLvl;
		}

		if ($isTagOpening && self::isTagIncluded($htmlTagDefinitionStr) && self::$excludedLvl !== null) {
			self::$excludedLvl = null;
		}

		if ($isTagClosing) {
			$curLvl--;
			if ($curLvl === self::$excludedLvl) {
				self::$excludedLvl = null;
			}

			if (self::$excludedLvl === null) {
				$lastTag = end($htmlTags);
				if ($lastTag !== false && $lastTag->getName() === 'title') {
					$htmlScan->setTitle(end($htmlTags)->getText());
				}
			}
		}

		$inHtmlTagDefinition = false;
	}

	private static function processMetaTag(HtmlTag $htmlTag, HtmlScan $htmlScan) {
		$type = null;
		$value = null;

		foreach ($htmlTag->getAttributes() as $attribute) {
			if ($attribute->getValue() === 'keywords') {
				$type = 'keywords';
			}

			if ($attribute->getValue() === 'description') {
				$type = 'description';
			}

			if ($attribute->getName() === 'content') {
				$value = $attribute->getValue();
			}
		}

		if ($type === 'keywords') {
			$htmlScan->setKeywordsStr($value);
		}

		if ($type === 'description') {
			$htmlScan->setDescription($value);
		}
	}


	private static function isTagExcluded(string $htmlTagDefinition): bool {
		$pattern = '/data-search\s*=\s*"' . Indexer::SEARCH_EXCLUDE . '"/';
		return preg_match($pattern, $htmlTagDefinition) === 1;
	}

	private static function isTagIncluded(string $htmlTagDefinition): bool {
		$pattern = '/data-search\s*=\s*"' . Indexer::SEARCH_INCLUDED . '"/';
		return preg_match($pattern, $htmlTagDefinition) === 1;
	}
}
