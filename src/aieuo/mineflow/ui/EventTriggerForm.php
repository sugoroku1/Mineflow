<?php

namespace aieuo\mineflow\ui;

use aieuo\mineflow\trigger\Trigger;
use aieuo\mineflow\utils\Session;
use pocketmine\Player;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\formAPI\ListForm;
use aieuo\mineflow\Main;
use aieuo\mineflow\formAPI\element\Button;

class EventTriggerForm {

    public function sendAddedTriggerMenu(Player $player, Recipe $recipe, Trigger $trigger, array $messages = []) {
        (new ListForm(Language::get("form.trigger.addedTriggerMenu.title", [$recipe->getName(), $trigger->getKey()])))
            ->setContent("type: @trigger.type.".$trigger->getType()."\n@trigger.event.".$trigger->getKey())
            ->addButtons([
                new Button("@form.back"),
                new Button("@form.delete"),
            ])->onReceive(function (Player $player, int $data, Recipe $recipe, Trigger $trigger) {
                switch ($data) {
                    case 0:
                        (new RecipeForm)->sendTriggerList($player, $recipe);
                        break;
                    case 1:
                        (new TriggerForm)->sendConfirmDelete($player, $recipe, $trigger);
                        break;
                }
            })->addArgs($recipe, $trigger)->addMessages($messages)->show($player);
    }

    public function sendEventTriggerList(Player $player, Recipe $recipe) {
        $events = array_filter(Main::getEventManager()->getEventConfig()->getAll(), function ($value) {
            return $value;
        });
        $buttons = [new Button("@form.back")];
        foreach ($events as $event => $value) {
            $buttons[] = new Button("@trigger.event.".$event);
        }
        (new ListForm(Language::get("trigger.event.list.title", [$recipe->getName()])))
            ->setContent("@form.selectButton")
            ->addButtons($buttons)
            ->onReceive(function (Player $player, int $data, Recipe $recipe, array $events) {
                if ($data === 0) {
                    (new TriggerForm)->sendSelectTriggerType($player, $recipe);
                    return;
                }
                $data --;

                $event = $events[$data];
                $this->sendSelectEventTrigger($player, $recipe, $event);
            })->addArgs($recipe, array_keys($events))->show($player);
    }

    public function sendSelectEventTrigger(Player $player, Recipe $recipe, string $eventName) {
        (new ListForm(Language::get("trigger.event.select.title", [$recipe->getName(), $eventName])))
            ->setContent($eventName."\n@trigger.event.".$eventName)
            ->addButtons([
                new Button("@form.back"),
                new Button("@form.add"),
            ])->onReceive(function (Player $player, int $data, Recipe $recipe, string $eventName) {
                if ($data === 0) {
                    $this->sendEventTriggerList($player, $recipe);
                    return;
                }

                $trigger = new Trigger(Trigger::TYPE_EVENT, $eventName);
                if ($recipe->existsTrigger($trigger)) {
                    $this->sendAddedTriggerMenu($player, $recipe, $trigger, ["@trigger.alreadyExists"]);
                    return;
                }
                $recipe->addTrigger($trigger);
                $this->sendAddedTriggerMenu($player, $recipe, $trigger, ["@trigger.add.success"]);
            })->addArgs($recipe, $eventName)->show($player);
    }

    public function sendSelectEvent(Player $player) {
        $events = array_filter(Main::getEventManager()->getEventConfig()->getAll(), function ($value) {
            return $value;
        });
        $buttons = [new Button("@form.back")];
        foreach ($events as $event => $value) {
            $buttons[] = new Button("@trigger.event.".$event);
        }
        (new ListForm("@form.event.list.title"))
            ->setContent("@form.selectButton")
            ->addButtons($buttons)
            ->onReceive(function (Player $player, int $data, array $events) {
                if ($data === 0) {
                    (new HomeForm)->sendMenu($player);
                    return;
                }
                $data --;

                $event = $events[$data];
                $this->sendRecipeList($player, $event);
            })->addArgs(array_keys($events))->show($player);
    }

    public function sendRecipeList(Player $player, string $event, array $messages = []) {
        $buttons = [new Button("@form.back"), new Button("@form.add")];

        $recipes = Main::getEventManager()->getAssignedRecipes($event);
        foreach ($recipes as $name => $events) {
            $buttons[] = new Button($name);
        }
        (new ListForm(Language::get("form.recipes.title", [Language::get("trigger.event.".$event)])))
            ->setContent("@form.selectButton")
            ->setButtons($buttons)
            ->onReceive(function (Player $player, int $data, string $event, array $recipes) {
                switch ($data) {
                    case 0:
                        $this->sendSelectEvent($player);
                        return;
                    case 1:
                        (new MineflowForm)->selectRecipe($player, Language::get("form.recipes.add", [Language::get("trigger.event.".$event)]),
                            function (Player $player, Recipe $recipe) use ($event) {
                                $trigger = new Trigger(Trigger::TYPE_EVENT, $event);
                                if ($recipe->existsTrigger($trigger)) {
                                    $this->sendRecipeList($player, $event, ["@trigger.alreadyExists"]);
                                    return;
                                }
                                $recipe->addTrigger($trigger);
                                $this->sendRecipeList($player, $event, ["@form.added"]);
                            },
                            function (Player $player) use ($event) {
                                $this->sendRecipeList($player, $event);
                            }
                        );
                        return;
                }
                $data -= 2;

                $this->sendRecipeMenu($player, $event, array_keys($recipes)[$data]);
            })->addMessages($messages)->addArgs($event, $recipes)->show($player);
    }

    public function sendRecipeMenu(Player $player, string $event, string $recipeName) {
        (new ListForm(Language::get("form.recipes.title", [Language::get("trigger.event.".$event)])))
            ->setContent(Language::get("trigger.event.".$event))
            ->setButtons([
                new Button("@form.back"),
                new Button("@form.edit")
            ])->onReceive(function (Player $player, int $data, string $event, string $recipeName) {
                if ($data === 0) {
                    $this->sendRecipeList($player, $event);
                } elseif ($data === 1) {
                    Session::getSession($player)
                        ->set("recipe_menu_prev", [$this, "sendRecipeMenu"])
                        ->set("recipe_menu_prev_data", [$event, $recipeName]);
                    [$name, $group] = Main::getRecipeManager()->parseName($recipeName);
                    $recipe = Main::getRecipeManager()->get($name, $group);
                    (new RecipeForm())->sendTriggerList($player, $recipe);
                }
            })->addArgs($event, $recipeName)->show($player);
    }
}
