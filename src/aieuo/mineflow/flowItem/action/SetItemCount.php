<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\flowItem\base\ItemFlowItem;
use aieuo\mineflow\flowItem\base\ItemFlowItemTrait;
use aieuo\mineflow\formAPI\Form;
use aieuo\mineflow\Main;
use aieuo\mineflow\variable\object\ItemObjectVariable;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\formAPI\element\Label;
use aieuo\mineflow\formAPI\element\Input;
use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\formAPI\element\Toggle;

class SetItemCount extends Action implements ItemFlowItem {
    use ItemFlowItemTrait;

    protected $id = self::SET_ITEM_COUNT;

    protected $name = "action.setItemCount.name";
    protected $detail = "action.setItemCount.detail";
    protected $detailDefaultReplace = ["item", "count"];

    protected $category = Category::ITEM;

    protected $targetRequired = Recipe::TARGET_REQUIRED_NONE;
    protected $returnValueType = self::RETURN_VARIABLE_NAME;

    /** @var string */
    private $count;

    public function __construct(string $name = "item", string $count = "") {
        $this->itemVariableName = $name;
        $this->count = $count;
    }

    public function setCount(string $count) {
        $this->count = $count;
    }

    public function getCount(): string {
        return $this->count;
    }

    public function isDataValid(): bool {
        return $this->itemVariableName !== "" and $this->count !== "";
    }

    public function getDetail(): string {
        if (!$this->isDataValid()) return $this->getName();
        return Language::get($this->detail, [$this->getItemVariableName(), $this->getCount()]);
    }

    public function execute(Recipe $origin): bool {
        $this->throwIfCannotExecute();

        $count = $origin->replaceVariables($this->getCount());
        $this->throwIfInvalidNumber($count, 0);

        $item = $this->getItem($origin);
        $this->throwIfInvalidItem($item);

        $item->setCount((int)$count);
        $origin->addVariable(new ItemObjectVariable($item, $this->getItemVariableName()));
        return true;
    }

    public function getEditForm(array $default = [], array $errors = []): Form {
        return (new CustomForm($this->getName()))
            ->setContents([
                new Label($this->getDescription()),
                new Input("@flowItem.target.require.item", Language::get("form.example", ["item"]), $default[1] ?? $this->getItemVariableName()),
                new Input("@action.createItemVariable.form.count", Language::get("form.example", ["64"]), $default[2] ?? $this->getCount()),
                new Toggle("@form.cancelAndBack")
            ])->addErrors($errors);
    }

    public function parseFromFormData(array $data): array {
        $errors = [];
        if ($data[1] === "") $data[1] = "item";
        $containsVariable = Main::getVariableHelper()->containsVariable($data[2]);
        if ($data[2] === "") {
            $errors[] = ["@form.insufficient", 2];
        } elseif (!$containsVariable and !is_numeric($data[2])) {
            $errors[] = ["@flowItem.error.notNumber", 2];
        } elseif (!$containsVariable and (int)$data[2] < 0) {
            $errors[] = [Language::get("flowItem.error.lessValue", [0]), 2];
        }
        return ["contents" => [$data[1], $data[2]], "cancel" => $data[3], "errors" => $errors];
    }

    public function loadSaveData(array $content): Action {
        if (!isset($content[1])) throw new \OutOfBoundsException();
        $this->setItemVariableName($content[0]);
        $this->setCount($content[1]);
        return $this;
    }

    public function serializeContents(): array {
        return [$this->getItemVariableName(), $this->getCount()];
    }

    public function getReturnValue(): string {
        return $this->getItemVariableName();
    }
}
