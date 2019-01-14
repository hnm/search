<?php
namespace search\html\bo;

/**
 * Class HtmlTagAttribute
 * This is a class used to store html attribute related data
 * @package html\bo
 */
class HtmlTagAttribute {
    private $name;
    private $value;

    /**
     * @param $args
     */
    public static function create($args) {
        $argsArr = explode('=', $args);

        return new HtmlTagAttribute(array_shift($argsArr), trim(array_shift($argsArr), '"'));
    }

    /**
     * HtmlTagAttribute constructor.
     * @param string $name
     * @param string $value
     */
    public function __construct(string $name, string $value) {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value) {
        $this->value = $value;
    }
}