<?php
namespace search\html\model;

use search\html\bo\HtmlScan;
use search\html\bo\HtmlTag;
use n2n\util\StringUtils;
use n2n\util\type\CastUtils;

class HtmlScanner {
	const SELF_CLOSING_HTML_TAGS = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
	const EXCLUDE_HTML_TAGS = ['script'];

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

		if (StringUtils::startsWith('</', $htmlTagDefinitionStr)) {
			$curLvl--;
		} elseif (!StringUtils::startsWith('<!', $htmlTagDefinitionStr)) {
			$htmlTag = HtmlTag::create($htmlTagDefinitionStr);

			$htmlTagLvls[$curLvl][] = $htmlTag;
			$htmlTags[] = $htmlTag;
			self::linkWithParent($htmlTag, $htmlTagLvls, $curLvl);

			if (!in_array($htmlTag->getName(), self::SELF_CLOSING_HTML_TAGS)) {
				$curLvl++;
			}
		}

		$inHtmlTagDefinition = false;
	}

	private static function linkWithParent(HtmlTag $htmlTag, array &$htmlTagLvls, int $curLvl) {
		if (isset($htmlTagLvls[$curLvl - 1])) {
			$parentHtmlTag = end($htmlTagLvls[$curLvl - 1]);
			$htmlTag->setParentHtmlTag($parentHtmlTag);
			$parentHtmlTag->addChildHtmlTag($htmlTag);
		}
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
}
