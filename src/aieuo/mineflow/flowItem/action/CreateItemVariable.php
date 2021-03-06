<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\formAPI\element\Input;
use aieuo\mineflow\formAPI\element\Label;
use aieuo\mineflow\formAPI\element\Toggle;
use aieuo\mineflow\formAPI\Form;
use aieuo\mineflow\Main;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\variable\object\ItemObjectVariable;
use pocketmine\item\ItemFactory;

class CreateItemVariable extends Action {

    protected $id = self::CREATE_ITEM_VARIABLE;

    protected $name = "action.createItemVariable.name";
    protected $detail = "action.createItemVariable.detail";
    protected $detailDefaultReplace = ["item", "id", "count", "name"];

    protected $category = Category::ITEM;

    protected $targetRequired = Recipe::TARGET_REQUIRED_NONE;
    protected $returnValueType = self::RETURN_VARIABLE_NAME;

    /** @var string */
    private $variableName = "item";
    /** @var string */
    private $itemId;
    /** @var string */
    private $itemCount;
    /** @var string */
    private $itemName = "";

    public function __construct(string $id = "", string $count = "", string $itemName = "", string $variableName = "item") {
        $this->itemId = $id;
        $this->itemCount = $count;
        $this->itemName = $itemName;
        $this->variableName = $variableName;
    }

    public function setVariableName(string $variableName) {
        $this->variableName = $variableName;
    }

    public function getVariableName(): string {
        return $this->variableName;
    }

    public function setItemId(string $id) {
        $this->itemId = $id;
    }

    public function getItemId(): string {
        return $this->itemId;
    }

    public function setItemCount(string $count) {
        $this->itemCount = $count;
    }

    public function getItemCount(): string {
        return $this->itemCount;
    }

    public function setItemName(string $itemName): void {
        $this->itemName = $itemName;
    }

    public function getItemName(): string {
        return $this->itemName;
    }

    public function isDataValid(): bool {
        return $this->variableName !== "" and $this->itemId !== "";
    }

    public function getDetail(): string {
        if (!$this->isDataValid()) return $this->getName();
        return Language::get($this->detail, [$this->getVariableName(), $this->getItemId(), $this->getItemCount(), $this->getItemName()]);
    }

    public function execute(Recipe $origin): bool {
        $this->throwIfCannotExecute();

        $name = $origin->replaceVariables($this->getVariableName());
        $id = $origin->replaceVariables($this->getItemId());
        $count = $origin->replaceVariables($this->getItemCount());
        $itemName = $origin->replaceVariables($this->getItemName());
        try {
            $item = ItemFactory::fromString($id);
        } catch (\InvalidArgumentException $e) {
            throw new \UnexpectedValueException(Language::get("flowItem.error", [$this->getName(), ["action.createItemVariable.item.notFound"]]));
        }
        if (!empty($count)) {
            $this->throwIfInvalidNumber($count, 0);
            $item->setCount((int)$count);
        } else {
            $item->setCount($item->getMaxStackSize());
        }
        if (!empty($itemName)) {
            $item->setCustomName($itemName);
        }

        $variable = new ItemObjectVariable($item, $name);
        $origin->addVariable($variable);
        return true;
    }

    public function getEditForm(array $default = [], array $errors = []): Form {
        return (new CustomForm($this->getName()))
            ->setContents([
                new Label($this->getDescription()),
                new Input("@action.createItemVariable.form.id", Language::get("form.example", ["1:0"]), $default[1] ?? $this->getItemId()),
                new Input("@action.createItemVariable.form.count", Language::get("form.example", ["64"]), $default[2] ?? $this->getItemCount()),
                new Input("@action.createItemVariable.form.name", Language::get("form.example", ["aieuo"]), $default[3] ?? $this->getItemName()),
                new Input("@flowItem.form.resultVariableName", Language::get("form.example", ["item"]), $default[4] ?? $this->getVariableName()),
                new Toggle("@form.cancelAndBack")
            ])->addErrors($errors);
    }

    public function parseFromFormData(array $data): array {
        $errors = [];
        if ($data[1] === "") {
            $errors[] = ["@form.insufficient", 1];
        }
        $count = $data[2];
        $containsVariable = Main::getVariableHelper()->containsVariable($count);
        if ($count !== "" and !$containsVariable and !is_numeric($count)) {
            $errors[] = ["@flowItem.error.notNumber", 2];
        }
        if ($data[4] === "") $data[4] = "item";
        return ["contents" => [$data[4], $data[1], $data[2], $data[3]], "cancel" => $data[5], "errors" => $errors];
    }

    public function loadSaveData(array $content): Action {
        if (!isset($content[2])) throw new \OutOfBoundsException();
        $this->setVariableName($content[0]);
        $this->setItemId($content[1]);
        $this->setItemCount($content[2]);
        $this->setItemName($content[3] ?? "");
        return $this;
    }

    public function serializeContents(): array {
        return [$this->getVariableName(), $this->getItemId(), $this->getItemCount(), $this->getItemName()];
    }

    public function getReturnValue(): string {
        return $this->getVariableName();
    }
}