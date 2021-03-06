<?php

namespace aieuo\mineflow\ui;

use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\trigger\Trigger;
use pocketmine\Player;
use aieuo\mineflow\utils\Session;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\formAPI\element\Input;
use aieuo\mineflow\formAPI\ModalForm;
use aieuo\mineflow\formAPI\ListForm;
use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\Main;
use aieuo\mineflow\formAPI\element\Toggle;
use aieuo\mineflow\formAPI\element\Dropdown;
use aieuo\mineflow\formAPI\element\Button;

class CommandForm {

    public function sendMenu(Player $player, array $messages = []) {
        (new ListForm("@form.command.menu.title"))
            ->setContent("@form.selectButton")
            ->addButtons([
                new Button("@form.back"),
                new Button("@form.add"),
                new Button("@form.edit"),
                new Button("@form.command.menu.commandList"),
            ])->onReceive(function (Player $player, int $data) {
                switch ($data) {
                    case 0:
                        (new HomeForm)->sendMenu($player);
                        break;
                    case 1:
                        $this->sendAddCommand($player);
                        break;
                    case 2:
                        $this->sendSelectCommand($player);
                        break;
                    case 3:
                        $this->sendCommandList($player);
                        break;
                }
            })->addMessages($messages)->show($player);
    }

    public function sendAddCommand(Player $player, array $defaults = [], array $errors = []) {
        (new CustomForm("@form.command.addCommand.title"))
            ->setContents([
                new Input("@form.command.menu.title", "@trigger.command.select.placeholder", $defaults[0] ?? ""),
                new Input("@form.command.description", "", $defaults[1] ?? ""),
                new Dropdown("@form.command.permission", [
                    Language::get("form.command.addCommand.permission.op"),
                    Language::get("form.command.addCommand.permission.true"),
                    Language::get("form.command.addCommand.permission.custom"),
                ], $defaults[2] ?? 0),
                new Toggle("@form.cancelAndBack"),
            ])->onReceive(function (Player $player, array $data) {
                if ($data[3]) {
                    $this->sendMenu($player);
                    return;
                }

                $manager = Main::getCommandManager();
                $original = $manager->getOriginCommand($data[0]);
                if (!$manager->isSubcommand($data[0]) and $manager->existsCommand($original)) {
                    $this->sendAddCommand($player, $data, [["@form.command.alreadyExists", 0]]);
                    return;
                }
                if ($manager->isRegistered($original)) {
                    $this->sendAddCommand($player, $data, [["@form.command.alreadyUsed", 0]]);
                    return;
                }

                $permission = ["mineflow.customcommand.op", "mineflow.customcommand.true"][$data[2]] ?? "";

                $manager->addCommand($data[0], $permission, $data[1]);
                $command = $manager->getCommand($original);
                Session::getSession($player)->set("command_menu_prev", [$this, "sendMenu"]);

                if ($data[2] === 2) {
                    $this->sendSelectPermissionName($player, $command);
                    return;
                }
                $this->sendCommandMenu($player, $command);
            })->addErrors($errors)->show($player);
    }

    public function sendSelectCommand(Player $player, array $defaults = [], array $errors = []) {
        (new CustomForm("@form.command.select.title"))
            ->setContents([
                new Input("@form.command.name", "", $defaults[0] ?? ""),
                new Toggle("@form.cancelAndBack"),
            ])->onReceive(function (Player $player, array $data) {
                if ($data[1]) {
                    $this->sendMenu($player);
                    return;
                }

                if ($data[0] === "") {
                    $this->sendSelectCommand($player, $data, [["@form.insufficient", 0]]);
                    return;
                }

                $manager = Main::getCommandManager();
                if (!$manager->existsCommand($manager->getOriginCommand($data[0]))) {
                    $this->sendSelectCommand($player, $data, [["@form.command.notFound", 0]]);
                    return;
                }

                $command = $manager->getCommand($manager->getOriginCommand($data[0]));
                Session::getSession($player)
                    ->set("command_menu_prev", [$this, "sendSelectCommand"])
                    ->set("command_menu_prev_data", []);
                $this->sendCommandMenu($player, $command);
            })->addErrors($errors)->show($player);
    }

    public function sendCommandList(Player $player) {
        $manager = Main::getCommandManager();
        $commands = $manager->getCommandAll();
        $buttons = [new Button("@form.back")];
        foreach ($commands as $command) {
            $buttons[] = new Button("/".$command["command"]);
        }

        (new ListForm("@form.command.commandList.title"))
            ->setContent("@form.selectButton")
            ->addButtons($buttons)
            ->onReceive(function (Player $player, int $data, array $commands) {
                if ($data === 0) {
                    $this->sendMenu($player);
                    return;
                }
                $data --;

                $command = $commands[$data];
                Session::getSession($player)
                    ->set("command_menu_prev", [$this, "sendCommandList"])
                    ->set("command_menu_prev_data", []);
                $this->sendCommandMenu($player, $command);
            })->addArgs(array_values($commands))->show($player);
    }

    public function sendCommandMenu(Player $player, array $command, array $messages = []) {
        $permissions = [
            "mineflow.customcommand.op" => "@form.command.addCommand.permission.op",
            "mineflow.customcommand.true" => "@form.command.addCommand.permission.true"
        ];
        $permission = $permissions[$command["permission"]] ?? $command["permission"];
        (new ListForm("/".$command["command"]))
            ->setContent("/".$command["command"]."\n".Language::get("form.command.permission").": ".$permission."\n".Language::get("form.command.description").": ".$command["description"])
            ->addButtons([
                new Button("@form.back"),
                new Button("@form.command.commandMenu.editDescription"),
                new Button("@form.command.commandMenu.editPermission"),
                new Button("@form.command.recipes"),
                new Button("@form.delete"),
            ])->onReceive(function (Player $player, int $data, array $command) {
                switch ($data) {
                    case 0:
                        $prev = Session::getSession($player)->get("command_menu_prev");
                        if (is_callable($prev)) call_user_func_array($prev, [$player]);
                        else $this->sendMenu($player);
                        break;
                    case 1:
                        $this->changeDescription($player, $command);
                        break;
                    case 2:
                        $this->changePermission($player, $command);
                        break;
                    case 3:
                        $this->sendRecipeList($player, $command);
                        break;
                    case 4:
                        $this->sendConfirmDelete($player, $command);
                        break;
                }
            })->addArgs($command)->addMessages($messages)->show($player);
    }

    public function changeDescription(Player $player, array $command) {
        (new CustomForm(Language::get("form.command.changeDescription.title", ["/".$command["command"]])))
            ->setContents([
                new Input("@form.command.description", "", $command["description"] ?? ""),
                new Toggle("@form.cancelAndBack"),
            ])->onReceive(function (Player $player, array $data, array $command) {
                if ($data[1]) {
                    $this->sendCommandMenu($player, $command);
                    return;
                }

                $manager = Main::getCommandManager();
                $command["description"] = $data[0];
                $manager->updateCommand($command);
                $this->sendCommandMenu($player, $command);
            })->addArgs($command)->show($player);
    }

    public function changePermission(Player $player, array $command) {
        $permissions = ["mineflow.customcommand.op" => 0, "mineflow.customcommand.true" => 1];
        (new CustomForm(Language::get("form.command.changePermission.title", ["/".$command["command"]])))
            ->setContents([
                new Dropdown("@form.command.permission", [
                    Language::get("form.command.addCommand.permission.op"),
                    Language::get("form.command.addCommand.permission.true"),
                    Language::get("form.command.addCommand.permission.custom"),
                ], $permissions[$command["permission"]] ?? 2),
                new Toggle("@form.cancelAndBack"),
            ])->onReceive(function (Player $player, array $data, array $command) {
                if ($data[1]) {
                    $this->sendCommandMenu($player, $command);
                    return;
                }

                if ($data[0] === 2) {
                    $this->sendSelectPermissionName($player, $command);
                    return;
                }

                $manager = Main::getCommandManager();
                $command["permission"] = ["mineflow.customcommand.op", "mineflow.customcommand.true"][$data[0]];
                $manager->updateCommand($command);
                $this->sendCommandMenu($player, $command);
            })->addArgs($command)->show($player);
    }

    public function sendSelectPermissionName(Player $player, array $command, array $default = [], array $errors = []) {
        (new CustomForm(Language::get("form.command.changePermission.title", ["/".$command["command"]])))
            ->setContents([
                new Input("@form.command.addCommand.permission.custom.input", "", $default[0] ?? $command["permission"]),
                new Toggle("@form.cancelAndBack"),
            ])->onReceive(function (Player $player, array $data, array $command) {
                if ($data[1]) {
                    $this->changePermission($player, $command);
                    return;
                }
                if ($data[0] === "") {
                    $this->sendSelectPermissionName($player, $command, $data, [["@form.insufficient", 0]]);
                    return;
                }

                $manager = Main::getCommandManager();
                $command["permission"] = $data[0];
                $manager->updateCommand($command);
                $this->sendCommandMenu($player, $command);
            })->addErrors($errors)->addArgs($command)->show($player);
    }

    public function sendConfirmDelete(Player $player, array $command) {
        (new ModalForm(Language::get("form.command.delete.title", ["/".$command["command"]])))
            ->setContent(Language::get("form.delete.confirm", ["/".$command["command"]]))
            ->setButton1("@form.yes")
            ->setButton2("@form.no")
            ->onReceive(function (Player $player, ?bool $data, array $command) {
                if ($data) {
                    $commandManager = Main::getCommandManager();
                    $recipeManager = Main::getRecipeManager();

                    $recipes = Main::getCommandManager()->getAssignedRecipes($command["command"]);
                    foreach ($recipes as $recipe => $commands) {
                        [$name, $group] = $recipeManager->parseName($recipe);

                        $recipe = $recipeManager->get($name, $group);
                        if ($recipe === null) continue;

                        foreach ($commands as $cmd) {
                            $recipe->removeTrigger(new Trigger(Trigger::TYPE_COMMAND, explode(" ", $cmd)[0], $cmd));
                        }
                    }
                    $commandManager->removeCommand($command["command"]);
                    $this->sendMenu($player, ["@form.delete.success"]);
                } else {
                    $this->sendCommandMenu($player, $command, ["@form.cancelled"]);
                }
            })->addArgs($command)->show($player);
    }

    public function sendRecipeList(Player $player, array $command, array $messages = []) {
        $buttons = [new Button("@form.back"), new Button("@form.add")];

        $recipes = Main::getCommandManager()->getAssignedRecipes($command["command"]);
        foreach ($recipes as $name => $commands) {
            $buttons[] = new Button($name." | ".count($commands));
        }
        (new ListForm(Language::get("form.recipes.title", ["/".$command["command"]])))
            ->setContent("@form.selectButton")
            ->setButtons($buttons)
            ->onReceive(function (Player $player, int $data, array $command, array $recipes) {
                switch ($data) {
                    case 0:
                        $this->sendCommandMenu($player, $command);
                        return;
                    case 1:
                        (new MineflowForm)->selectRecipe($player, Language::get("form.recipes.add", [$command["command"]]),
                            function (Player $player, Recipe $recipe) use ($command) {
                                $trigger = new Trigger(Trigger::TYPE_COMMAND, $command["command"], $command["command"]);
                                if ($recipe->existsTrigger($trigger)) {
                                    $this->sendRecipeList($player, $command, ["@trigger.alreadyExists"]);
                                    return;
                                }
                                $recipe->addTrigger($trigger);
                                $this->sendRecipeList($player, $command, ["@form.added"]);
                            },
                            function (Player $player) use ($command) {
                                $this->sendRecipeList($player, $command);
                            }
                        );
                        return;
                }
                $data -= 2;

                $this->sendRecipeMenu($player, $command, $data, $recipes);
            })->addMessages($messages)->addArgs($command, $recipes)->show($player);
    }

    public function sendRecipeMenu(Player $player, array $command, int $index, array $recipes) {
        $command = Main::getCommandManager()->getCommand($command["command"]);
        $triggers = array_values($recipes)[$index];
        $content = implode("\n", array_map(function (String $cmd) {
            return "/".$cmd;
        }, $triggers));
        (new ListForm(Language::get("form.recipes.title", ["/".$command["command"]])))
            ->setContent($content)
            ->setButtons([
                new Button("@form.back"),
                new Button("@form.edit")
            ])->onReceive(function (Player $player, int $data, array $command, int $index, array $recipes) {
                if ($data === 0) {
                    $this->sendRecipeList($player, $command);
                } elseif ($data === 1) {
                    Session::getSession($player)
                        ->set("recipe_menu_prev", [$this, "sendRecipeMenu"])
                        ->set("recipe_menu_prev_data", [$command, $index, $recipes]);
                    $recipeName = array_keys($recipes)[$index];
                    [$name, $group] = Main::getRecipeManager()->parseName($recipeName);
                    $recipe = Main::getRecipeManager()->get($name, $group);
                    (new RecipeForm())->sendTriggerList($player, $recipe);
                }
            })->addArgs($command, $index, $recipes)->show($player);
    }
}