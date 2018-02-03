<?php

namespace EDI\X12;

class Document
{
    public $segments = array();

    public function __construct($segments)
    {
        $this->segments = $segments;
    }

    public function __toString()
    {
        $str = '';
        foreach ($this->segments as $segment) {
            foreach ($segment as &$element) {
                if (is_array($element)) {
                    $element = implode('>', $element);
                }
            }
            $str .= implode('*', $segment);
            $str .= "\n";
        }
        return $str;
    }
}
