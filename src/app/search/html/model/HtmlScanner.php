<?php
namespace search\html\model;

use search\html\bo\HtmlScan;
use search\html\bo\HtmlTag;
use n2n\util\StringUtils;
use n2n\util\type\CastUtils;
use search\model\Indexer;

class HtmlScanner {
	const SELF_CLOSING_HTML_TAGS = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
	const EXCLUDE_HTML_TAGS = ['script'];

	private static array $tagStack = [];

	public static function scan(string $htmlStr): HtmlScan {
		$htmlScan = new HtmlScan();
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
		self::determineMeta($htmlScan);
		return $htmlScan;
	}

	private static function determineEscaped(string $char, bool $escaped): bool {
		return $char === '\\' ? !$escaped : false;
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
				self::processTagText($curLvl, $htmlTagLvls, $currentHtmlTagRawText);
				$currentHtmlTagRawText = '';
			}
			$inHtmlTagDefinition = true;
		}

		if ($char === '>' && $inHtmlTagDefinition && !$escaped && !$inStrChar) {
			self::processTagEnd($char, $curLvl, $htmlTagDefinitionStr, $htmlTagLvls, $htmlTags, $inHtmlTagDefinition);
			$htmlTagDefinitionStr = '';
		}

		if ($inHtmlTagDefinition) {
			$htmlTagDefinitionStr .= $char;
		} else {
			$currentHtmlTagRawText .= $char;
		}
	}

	private static function processTagText(int $curLvl, array $htmlTagLvls, string $currentHtmlTagRawText) {
		if (isset($htmlTagLvls[$curLvl - 1])) {
			$tag = end($htmlTagLvls[$curLvl - 1]);
			CastUtils::assertTrue($tag instanceof HtmlTag);

			$cleanedText = ltrim($currentHtmlTagRawText, '>');

			if (!in_array($tag->getName(), self::EXCLUDE_HTML_TAGS)) {
				$tag->addText(html_entity_decode($cleanedText));
			}
		}
	}


	private static function processTagEnd(string $char, int &$curLvl, string &$htmlTagDefinitionStr, array &$htmlTagLvls, array &$htmlTags, bool &$inHtmlTagDefinition) {
		$htmlTagDefinitionStr .= $char;

		if (StringUtils::startsWith('<', $htmlTagDefinitionStr) && !StringUtils::startsWith('</', $htmlTagDefinitionStr)) {
			$tagType = self::getTagType($htmlTagDefinitionStr);
			array_push(self::$tagStack, ['type' => $tagType, 'level' => $curLvl]);
		}

		if (self::shouldProcessTag($curLvl, $htmlTagDefinitionStr)) {
			if (!StringUtils::startsWith('<!', $htmlTagDefinitionStr)) {
				$htmlTag = HtmlTag::create($htmlTagDefinitionStr);

				$htmlTagLvls[$curLvl][] = $htmlTag;
				$htmlTags[] = $htmlTag;

				if (!in_array($htmlTag->getName(), self::SELF_CLOSING_HTML_TAGS)) {
					$curLvl++;
				}
			}
		}

		if (StringUtils::startsWith('</', $htmlTagDefinitionStr)) {
			self::popTagStack($curLvl);
			$curLvl--;
		}

		$inHtmlTagDefinition = false;
	}

	private static function shouldProcessTag(int $curLvl, string $htmlTagDefinitionStr): bool {
		if (self::isTagIncluded($htmlTagDefinitionStr)) {
			return true;
		}
		if (self::isTagExcluded($htmlTagDefinitionStr)) {
			return false;
		}

		// Check the tag stack for nested include/exclude logic
		foreach (array_reverse(self::$tagStack) as $tag) {
			if ($tag['level'] < $curLvl) {
				break;
			}
			if ($tag['type'] === 'included') {
				return true;
			}
			if ($tag['type'] === 'excluded') {
				return false;
			}
		}

		return true;
	}

	private static function popTagStack(int $curLvl) {
		while (!empty(self::$tagStack) && end(self::$tagStack)['level'] >= $curLvl) {
			array_pop(self::$tagStack);
		}
	}

	private static function getTagType(string $htmlTagDefinition): string {
		if (self::isTagIncluded($htmlTagDefinition)) {
			return 'included';
		}
		if (self::isTagExcluded($htmlTagDefinition)) {
			return 'excluded';
		}
		return 'normal';
	}


	private static function determineMeta(HtmlScan $htmlScan) {
		foreach ($htmlScan->getHtmlTags() as $htmlTag) {
			if ($htmlTag->getName() === 'title') {
				$htmlScan->setTitle($htmlTag->getText());
			}

			if ($htmlTag->getName() === 'meta') {
				self::processMetaTag($htmlTag, $htmlScan);
			}
		}
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
