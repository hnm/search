<?php
namespace search\html\model;

use search\html\bo\HtmlScan;
use search\html\bo\HtmlTag;
use n2n\util\StringUtils;

/**
 * Class HtmlScanner
 * This class is able to scan through HTML-code and read searchable text.
 * @package html\model
 */
class HtmlScanner {
	const SELF_CLOSING_HTML_TAGS = array('area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr');

	/**
	 * Easy way to create a HtmlScan by htmlStr
	 * @param string $htmlStr
	 */
	public static function scan(string $htmlStr) {
		$htmlScan = new HtmlScan();
		$curLvl = 0;
		$inStrChar = '';
		$escaped = false;
		$htmlTags = array();
		$htmlTagLvls = array();
		$htmlTagDefinitionStr = '';
		$inHtmlTagDefinition = false;
		$currentHtmlTagRawText = '';
		foreach (str_split($htmlStr) as $char) {
			$escaped = self::determineEscaped($char, $escaped);
			$inStrChar = self::determineInStrChar($escaped, $char, $inStrChar, $inHtmlTagDefinition, end($htmlTags));

			if ($char === '<' && !$inStrChar && !$escaped) {
				if (!$inHtmlTagDefinition) {
					if (isset($htmlTagLvls[$curLvl - 1])) {
						end($htmlTagLvls[$curLvl - 1])->addText(html_entity_decode($currentHtmlTagRawText));
						$currentHtmlTagRawText = '';
					}
				}

				$inHtmlTagDefinition = true;
			}

			if ($char === '>' && $inHtmlTagDefinition && !$escaped && !$inStrChar) {

				$htmlTagDefinitionStr .= $char;

				if (StringUtils::startsWith('</', $htmlTagDefinitionStr)) {
					$curLvl--;
				} elseif (!StringUtils::startsWith('<!', $htmlTagDefinitionStr)) {
					$htmlTag = HtmlTag::create($htmlTagDefinitionStr);

					$htmlTagLvls[$curLvl][] = $htmlTag;
					$htmlTags[] = $htmlTag;

					if (isset($htmlTagLvls[$curLvl - 1])) {
						$parentHtmlTag = end($htmlTagLvls[$curLvl - 1]);
						$htmlTag->setParentHtmlTag($parentHtmlTag);
						$parentHtmlTag->addChildHtmlTag($htmlTag);
					}

					if (!in_array($htmlTag->getName(), self::SELF_CLOSING_HTML_TAGS)) {
						$curLvl++;
					}
				}

				$inHtmlTagDefinition = false;
				$htmlTagDefinitionStr = '';
				continue;
			}

			if ($inHtmlTagDefinition) {
				$htmlTagDefinitionStr .= $char;
				continue;
			}

			if (!$inHtmlTagDefinition) {
				$currentHtmlTagRawText .= $char;
			}
		}

		$htmlScan->setHtmlTags($htmlTags);

		self::determineMeta($htmlScan);
		return $htmlScan;
	}

	/**
	 * Determines if a character is escaped
	 * @param $char
	 * @param $escaped
	 * @return bool
	 */
	private static function determineEscaped($char, $escaped) {
		if ($char === '\\') {
			if ($escaped) {
				$escaped = false;
			} else {
				$escaped = true;
			}
		} else {
			$escaped = false;
		}

		return $escaped;
	}

	/**
	 * determines if a character is in quotes
	 * @param $escaped
	 * @param $char
	 * @param $inStrChar
	 * @return string
	 */
	private static function determineInStrChar($escaped, $char, $inStrChar, $inHtmlTagDefinition, $lastHtmlTag) {
		if ($inHtmlTagDefinition || !$lastHtmlTag || $lastHtmlTag->getName() !== 'script') {
			return $inStrChar;
		}

		if (!$escaped && $char === "'" || $char === '"') {

			if ($inStrChar === '') {
				return $char;
			} elseif ($inStrChar === $char) {
				return '';
			}
		}

		return $inStrChar;
	}

	/**
	 * Determines the name, keywords and description by html-meta tags
	 * @param HtmlScan $htmlScan
	 */
	private static function determineMeta(HtmlScan $htmlScan) {
		foreach ($htmlScan->getHtmlTags() as $htmlTag) {
			if ($htmlTag->getName() === 'head') {
				foreach ($htmlTag->getChildHtmlTags() as $headerHtmlTag) {
					if ($headerHtmlTag->getName() === 'title') {
						$htmlScan->setTitle($headerHtmlTag->getText());
					}
				}
			}

			if ($htmlTag->getName() === 'meta') {
				$type = null;

				foreach ($htmlTag->getAttributes() as $attribute) {
					if ($attribute->getValue() === 'keywords') {
						$type = 'keywords';
					}

					if ($attribute->getValue() === 'description') {
						$type = 'description';
					}

					$value = null;
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
	}
}