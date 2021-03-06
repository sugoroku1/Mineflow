<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\formAPI\Form;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\formAPI\element\Label;
use aieuo\mineflow\formAPI\element\Input;
use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\Main;
use aieuo\mineflow\formAPI\element\Toggle;

class CallRecipe extends ExecuteRecipe {

    protected $id = self::CALL_RECIPE;

    protected $name = "action.callRecipe.name";
    protected $detail = "action.callRecipe.detail";

    public function __construct(string $name = "", string $args = "") {
        parent::__construct($name, $args);
    }

    public function execute(Recipe $origin): bool {
        $this->throwIfCannotExecute();

        $name = $origin->replaceVariables($this->getRecipeName());

        $recipeManager = Main::getRecipeManager();
        [$recipeName, $group] = $recipeManager->parseName($name);
        if (empty($group)) $group = $origin->getGroup();

        $recipe = $recipeManager->get($recipeName, $group) ?? $recipeManager->get($recipeName, "");
        if ($recipe === null) {
            throw new \UnexpectedValueException(Language::get("flowItem.error", [$this->getName(), Language::get("action.executeRecipe.notFound")]));
        }

        $recipe = clone $recipe;
        $helper = Main::getVariableHelper();
        $args = [];
        foreach ($this->getArgs() as $arg) {
            if (!$helper->isVariableString($arg)) {
                $args[] = $helper->replaceVariables($arg, $origin->getVariables());
                continue;
            }
            $arg = $origin->getVariable(substr($arg, 1, -1)) ?? $helper->get(substr($arg, 1, -1)) ?? $arg;
            $args[] = $arg;
        }
        $this->getParent()->wait();
        $recipe->setSourceRecipe($origin)->setSourceContainer($this->getParent());
        $recipe->executeAllTargets($origin->getTarget(), [], null, $args);
        return true;
    }

    public function getEditForm(array $default = [], array $errors = []): Form {
        return (new CustomForm($this->getName()))
            ->setContents([
                new Label($this->getDescription()),
                new Input("@action.executeRecipe.form.name", Language::get("form.example", ["aieuo"]), $default[1] ?? $this->getRecipeName()),
                new Input("@action.callRecipe.form.args", Language::get("form.example", ["{target}, 1, aieuo"]), $default[1] ?? implode(", ", $this->getArgs())),
                new Toggle("@form.cancelAndBack")
            ])->addErrors($errors);
    }

    public function parseFromFormData(array $data): array {
        $errors = [];
        if ($data[1] === "") {
            $errors[] = ["@form.insufficient", 1];
        }
        return ["contents" => [$data[1], array_map("trim", explode(",", $data[2]))], "cancel" => $data[3], "errors" => $errors];
    }

    public function loadSaveData(array $content): Action {
        if (!isset($content[1])) throw new \OutOfBoundsException();
        $this->setRecipeName($content[0]);
        $this->setArgs($content[1]);
        return $this;
    }

    public function serializeContents(): array {
        return [$this->getRecipeName(), $this->getArgs()];
    }
}