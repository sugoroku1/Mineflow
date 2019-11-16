<?php

namespace aieuo\mineflow\action\process;

use pocketmine\entity\Entity;
use aieuo\mineflow\utils\Categories;
use aieuo\mineflow\recipe\Recipe;

class DoNothing extends Process {

    protected $id = self::DO_NOTHINIG;

    protected $name = "@action.doNothing.name";
    protected $description = "@action.doNothing.description";
    protected $detail = "@action.doNothing.detail";

    protected $category = Categories::CATEGORY_ACTION_OTHER;

    protected $targetRequired = Recipe::TARGET_NONE;

    public function execute(?Entity $target, ?Recipe $origin = null): ?bool {
        return true;
    }

    public function isDataValid(): bool {
        return true;
    }

    public function parseFromSaveData(array $content): ?Process {
        return $this;
    }

    public function serializeContents(): array {
        return [];
    }
}