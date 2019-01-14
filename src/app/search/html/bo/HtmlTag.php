<?php
namespace search\html\bo;

/**
 * Class HtmlTag
 * This is a class used to manage Html-tag related data
 * @package html\bo
 */
class HtmlTag {
    /**
     * @var HtmlTag[]
     */
    private $childHtmlTags = array();
    /**
     * @var HtmlTag
     */
    private $parentHtmlTag = null;
    /**
     * @var string
     */
    private $name = null;
    /**
     * @var string
     */
    private $text = null;

    /**
     * @var HtmlTagAttribute[]
     */
    private $attributes = array();
    /**
     * Easy way to create a Html Tag by passing htmltag string
     * @param string $args
     */
    public static function create(string $args) {
        $args = trim($args, '<>');
        $args = rtrim($args, '/');

        preg_match_all('/(?:[^\s"]+|"[^"]*")+/', $args, $matches);
        $argsArr = $matches[0];

        $htmlTag = new HtmlTag();
        $htmlTag->setName(array_shift($argsArr));

        $attributes = array();

        while (null !== ($args = array_shift($argsArr))) {
            $attributes[] = HtmlTagAttribute::create($args);
        }

        $htmlTag->setAttributes($attributes);

        return $htmlTag;
    }

    public function findAllParents() {
        $parents = array();
        if (null === $this->parentHtmlTag) return $parents;

        $parents = array($this->parentHtmlTag);
        $curParent = $this->parentHtmlTag;
        while (null !== ($parent = $curParent->getParentHtmlTag())) {
            $parents[] = $curParent = $parent;
        }

        return $parents;
    }

    /**
     * @return HtmlTag[]
     */
    public function getChildHtmlTags() {
        return $this->childHtmlTags;
    }

    /**
     * @param HtmlTag[] $childHtmlTags
     */
    public function setChildHtmlTags($childHtmlTags) {
        $this->childHtmlTags = $childHtmlTags;
    }

    public function addChildHtmlTag(HtmlTag $htmlTag) {
        $this->childHtmlTags[] = $htmlTag;
    }

    /**
     * @return HtmlTag
     */
    public function getParentHtmlTag() {
        return $this->parentHtmlTag;
    }

    /**
     * @param HtmlTag $parentHtmlTag
     */
    public function setParentHtmlTag($parentHtmlTag) {
        $this->parentHtmlTag = $parentHtmlTag;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
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
    public function addText(string $text) {
        $this->text .= $text;
    }

    /**
     * @param string $text
     */
    public function setText($text) {
        $this->text = $text;
    }

    /**
     * @return HtmlTagAttribute[]
     */
    public function getAttributes() {
        return $this->attributes;
    }

    /**
     * @param HtmlTagAttribute[] $attributes
     */
    public function setAttributes($attributes) {
        $this->attributes = $attributes;
    }
}