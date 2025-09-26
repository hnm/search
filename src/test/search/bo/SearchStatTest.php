<?php
namespace search\bo;

use PHPUnit\Framework\TestCase;

class SearchStatTest extends TestCase {

	public function testLatinAlphabetOnly() {
		$latinText = str_repeat('abcdefghij', 10); // 100 characters exactly
		$searchStat = new SearchStat($latinText, 5);
		
		$this->assertEquals($latinText, $searchStat->getText());
		$this->assertEquals(100, strlen($searchStat->getText()));
		$this->assertEquals(100, mb_strlen($searchStat->getText()));
		$this->assertEquals(5, $searchStat->getResultAmount());
	}

	public function testMixedContentMaxedOut() {
		// Mixed content (latin + UTF-8 special chars) that exceeds 255 characters
		$mixedText = str_repeat('abc', 60) . str_repeat('äöü', 25); // 180 + 150 = 330 characters
		$searchStat = new SearchStat($mixedText, 8);

		var_dump($searchStat->getText());
		$this->assertTrue(strlen($searchStat->getText()) <= SearchStat::MAX_TEXT_LENGTH);
		$this->assertTrue(mb_strlen($searchStat->getText()) <= SearchStat::MAX_TEXT_LENGTH);
		$this->assertTrue(str_ends_with($searchStat->getText(), '...'));
		$this->assertEquals(8, $searchStat->getResultAmount());
	}

	public function testSpecialCharsOnly() {
		$specialText = str_repeat('äöüß€', 70); // 350 characters of special chars
		$searchStat = new SearchStat($specialText, 12);

		$this->assertTrue(mb_strlen($searchStat->getText()) <= SearchStat::MAX_TEXT_LENGTH);
		$this->assertTrue(str_ends_with($searchStat->getText(), '...'));
		$this->assertEquals(12, $searchStat->getResultAmount());
	}

}