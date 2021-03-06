<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\utils\Category;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\Main;

class SaveData extends Action {

    protected $id = self::SAVE_DATA;

    protected $name = "action.saveData.name";
    protected $detail = "action.saveData.detail";

    protected $category = Category::SCRIPT;

    protected $targetRequired = Recipe::TARGET_REQUIRED_NONE;

    public function isDataValid(): bool {
        return true;
    }

    public function execute(Recipe $origin): bool {
        Main::getRecipeManager()->saveAll();
        Main::getFormManager()->saveAll();
        Main::getVariableHelper()->saveAll();
        return true;
    }

    public function loadSaveData(array $content): Action {
        return $this;
    }

    public function serializeContents(): array {
        return [];
    }
}
