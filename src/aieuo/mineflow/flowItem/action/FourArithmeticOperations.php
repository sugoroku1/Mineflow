<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\formAPI\Form;
use aieuo\mineflow\variable\NumberVariable;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\formAPI\element\Label;
use aieuo\mineflow\formAPI\element\Input;
use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\formAPI\element\Toggle;
use aieuo\mineflow\formAPI\element\Dropdown;

class FourArithmeticOperations extends Action {

    protected $id = self::FOUR_ARITHMETIC_OPERATIONS;

    protected $name = "action.fourArithmeticOperations.name";
    protected $detail = "action.fourArithmeticOperations.detail";
    protected $detailDefaultReplace = ["value1", "value2", "operator", "result"];

    protected $category = Category::MATH;

    protected $targetRequired = Recipe::TARGET_REQUIRED_NONE;
    protected $returnValueType = self::RETURN_VARIABLE_VALUE;

    const ADDITION = 0;
    const SUBTRACTION = 1;
    const MULTIPLICATION = 2;
    const DIVISION = 3;
    const MODULO = 4;

    /** @var string */
    private $value1;
    /** @var int */
    private $operator = self::ADDITION;
    /** @var string */
    private $value2;
    /** @var string */
    private $resultName = "result";

    private $operatorSymbols = ["+", "-", "*", "/", "％"];

    /* @var string */
    private $lastResult;

    public function __construct(string $value1 = "", int $operator = self::ADDITION, string $value2 = "", string $resultName = "result") {
        $this->value1 = $value1;
        $this->operator = $operator;
        $this->value2 = $value2;
        $this->resultName = $resultName;
    }

    public function setValues(string $value1, string $value2): self {
        $this->value1 = $value1;
        $this->value2 = $value2;
        return $this;
    }

    public function getValue1(): string {
        return $this->value1;
    }

    public function getValue2(): string {
        return $this->value2;
    }

    public function setOperator(int $operator): self {
        $this->operator = $operator;
        return $this;
    }

    public function getOperator(): int {
        return $this->operator;
    }

    public function setResultName(string $name): self {
        $this->resultName = $name;
        return $this;
    }

    public function getResultName(): string {
        return $this->resultName;
    }

    public function isDataValid(): bool {
        return $this->getValue1() !== "" and $this->getValue2() !== "";
    }

    public function getDetail(): string {
        if (!$this->isDataValid()) return $this->getName();
        return Language::get($this->detail, [$this->getValue1(), $this->operatorSymbols[$this->getOperator()] ?? "?", $this->getValue2(), $this->getResultName()]);
    }

    public function execute(Recipe $origin): bool {
        $this->throwIfCannotExecute();

        $value1 = $origin->replaceVariables($this->getValue1());
        $value2 = $origin->replaceVariables($this->getValue2());
        $resultName = $origin->replaceVariables($this->getResultName());
        $operator = $this->getOperator();

        if (!is_numeric($value1) or !is_numeric($value2)) {
            throw new \UnexpectedValueException(Language::get("flowItem.error", [$this->getName(), ["flowItem.error.notNumber"]]));
        }

        switch ($operator) {
            case self::ADDITION:
                $result = (float)$value1 + (float)$value2;
                break;
            case self::SUBTRACTION:
                $result = (float)$value1 - (float)$value2;
                break;
            case self::MULTIPLICATION:
                $result = (float)$value1 * (float)$value2;
                break;
            case self::DIVISION:
                if ((float)$value2 == 0) {
                    throw new \UnexpectedValueException(Language::get("flowItem.error", [$this->getName(), Language::get("variable.number.div.0")]));
                }
                $result = (float)$value1 / (float)$value2;
                break;
            case self::MODULO:
                if ((float)$value2 == 0) {
                    throw new \UnexpectedValueException(Language::get("flowItem.error", [$this->getName(), Language::get("variable.number.div.0")]));
                }
                $result = (float)$value1 % (float)$value2;
                break;
            default:
                throw new \UnexpectedValueException(Language::get("flowItem.error", [$this->getName(), ["action.calculate.operator.unknown", [$operator]]]));
        }

        $this->lastResult = (string)$result;
        $origin->addVariable(new NumberVariable($result, $resultName));
        return true;
    }

    public function getEditForm(array $default = [], array $errors = []): Form {
        return (new CustomForm($this->getName()))
            ->setContents([
                new Label($this->getDescription()),
                new Input("@action.fourArithmeticOperations.form.value1", Language::get("form.example", ["10"]), $default[1] ?? $this->getValue1()),
                new Dropdown("@action.fourArithmeticOperations.form.operator", $this->operatorSymbols, $default[2] ?? $this->getOperator()),
                new Input("@action.fourArithmeticOperations.form.value2", Language::get("form.example", ["50"]), $default[3] ?? $this->getValue2()),
                new Input("@flowItem.form.resultVariableName", Language::get("form.example", ["result"]), $default[4] ?? $this->getResultName()),
                new Toggle("@form.cancelAndBack")
            ])->addErrors($errors);
    }

    public function parseFromFormData(array $data): array {
        $errors = [];
        if ($data[1] === "") {
            $errors[] = ["@form.insufficient", 1];
        }
        if ($data[3] === "") {
            $errors[] = ["@form.insufficient", 3];
        }
        if ($data[4] === "") $data[4] = "result";
        return ["contents" => [$data[1], $data[2], $data[3], $data[4]], "cancel" => $data[5], "errors" => $errors];
    }

    public function loadSaveData(array $content): Action {
        if (!isset($content[3])) throw new \OutOfBoundsException();
        $this->setValues($content[0], $content[2]);
        $this->setOperator($content[1]);
        $this->setResultName($content[3]);
        return $this;
    }

    public function serializeContents(): array {
        return [$this->getValue1(), $this->getOperator(), $this->getValue2(), $this->getResultName()];
    }

    public function getReturnValue(): string {
        return $this->lastResult;
    }
}