<?php

namespace aieuo\mineflow\ui;

use aieuo\mineflow\formAPI\element\Toggle;
use aieuo\mineflow\trigger\Trigger;
use pocketmine\Player;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\formAPI\element\Input;
use aieuo\mineflow\formAPI\ModalForm;
use aieuo\mineflow\formAPI\ListForm;
use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\Main;
use aieuo\mineflow\formAPI\element\Button;

class CommandTriggerForm {

    public function sendAddedTriggerMenu(Player $player, Recipe $recipe, Trigger $trigger, array $messages = []) {
        (new ListForm(Language::get("form.trigger.addedTriggerMenu.title", [$recipe->getName(), $trigger->getKey()])))
            ->setContent("type: ".$trigger->getType()."\n/".$trigger->getKey()."\n/".$trigger->getSubKey())
            ->addButtons([
                new Button("@form.back"),
                new Button("@form.delete"),
                new Button("@trigger.command.edit.title"),
            ])->onReceive(function (Player $player, int $data, Recipe $recipe, Trigger $trigger) {
                switch ($data) {
                    case 0:
                        (new RecipeForm)->sendTriggerList($player, $recipe);
                        break;
                    case 1:
                        (new TriggerForm)->sendConfirmDelete($player, $recipe, $trigger);
                        break;
                    case 2:
                        $manager = Main::getCommandManager();
                        $command = $manager->getCommand($manager->getOriginCommand($trigger->getKey()));
                        (new CommandForm)->sendCommandMenu($player, $command);
                        break;
                }
            })->addArgs($recipe, $trigger)->addMessages($messages)->show($player);
    }

    public function sendSelectCommand(Player $player, Recipe $recipe, array $default = [], array $errors = []) {
        (new CustomForm(Language::get("trigger.command.select.title", [$recipe->getName()])))
            ->setContents([
                new Input("@trigger.command.select.input", "@trigger.command.select.placeholder", $default[0] ?? ""),
                new Toggle("@form.back"),
            ])->onReceive(function (Player $player, array $data, Recipe $recipe) {
                if ($data[1]) {
                    (new TriggerForm)->sendSelectTriggerType($player, $recipe);
                    return;
                }

                if (empty($data[0])) {
                    $this->sendSelectCommand($player, $recipe, $data, [["@form.insufficient", 0]]);
                    return;
                }

                $manager = Main::getCommandManager();
                $original = $manager->getOriginCommand($data[0]);
                if (!$manager->existsCommand($original)) {
                    $this->sendConfirmCreate($player, $original, function (bool $result) use ($player, $recipe, $data) {
                        if ($result) {
                            (new CommandForm)->sendAddCommand($player, [$data[0]]);
                        } else {
                            $this->sendSelectCommand($player, $recipe, $data, [["@trigger.command.select.notFound", 0]]);
                        }
                    });
                    return;
                }

                $trigger = new Trigger(Trigger::TYPE_COMMAND, explode(" ", $data[0])[0], $data[0]);
                if ($recipe->existsTrigger($trigger)) {
                    $this->sendAddedTriggerMenu($player, $recipe, $trigger, ["@trigger.alreadyExists"]);
                    return;
                }
                $recipe->addTrigger($trigger);
                $this->sendAddedTriggerMenu($player, $recipe, $trigger, ["@trigger.add.success"]);
            })->addArgs($recipe)->addErrors($errors)->show($player);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function sendConfirmCreate(Player $player, string $name, callable $callback) {
        (new ModalForm("@trigger.command.confirmCreate.title"))
            ->setContent(Language::get("trigger.command.confirmCreate.content", [$name]))
            ->setButton1("@form.yes")
            ->setButton2("@form.no")
            ->onReceive(function (Player $player, ?bool $data, callable $callback) {
                call_user_func_array($callback, [$data]);
            })->addArgs($callback)->show($player);
    }
}