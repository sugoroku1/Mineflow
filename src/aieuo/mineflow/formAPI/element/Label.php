<?php

namespace aieuo\mineflow\formAPI\element;

class Label extends Element {

    /** @var string */
    protected $type = self::ELEMENT_LABEL;

    public function jsonSerialize(): array {
        return [
            "type" => $this->type,
            "text" => str_replace("\\n", "\n", $this->reflectHighlight($this->checkTranslate($this->text))),
        ];
    }
}