<?php

namespace aieuo\mineflow\ui;

use aieuo\mineflow\exception\FlowItemLoadException;
use aieuo\mineflow\flowItem\condition\Condition;
use aieuo\mineflow\flowItem\condition\ConditionContainer;
use aieuo\mineflow\flowItem\condition\ConditionFactory;
use aieuo\mineflow\flowItem\FlowItem;
use aieuo\mineflow\recipe\Recipe;
use pocketmine\Player;
use aieuo\mineflow\utils\Session;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\formAPI\ListForm;
use aieuo\mineflow\Main;
use aieuo\mineflow\formAPI\element\Button;

class ConditionForm {

    public function sendAddedConditionMenu(Player $player, ConditionContainer $container, Condition $condition, array $messages = []) {
        if ($condition->hasCustomMenu()) {
            $condition->sendCustomMenu($player);
            return;
        }
        /** @var Recipe|FlowItem $container */
        (new ListForm(Language::get("form.condition.addedConditionMenu.title", [$container->getContainerName(), $condition->getName()])))
            ->setContent(trim($condition->getDetail()))
            ->addButtons([
                new Button("@form.back"),
                new Button("@form.edit"),
                new Button("@form.move"),
                new Button("@form.delete"),
            ])->onReceive(function (Player $player, int $data, ConditionContainer $container, Condition $condition) {
                switch ($data) {
                    case 0:
                        Session::getSession($player)->pop("parents");
                        (new ConditionContainerForm)->sendConditionList($player, $container);
                        break;
                    case 1:
                        $condition->getEditForm()
                            ->addArgs($container, $condition, function ($result) use ($player, $container, $condition) {
                                $this->sendAddedConditionMenu($player, $container, $condition, [$result ? "@form.changed" : "@form.cancelled"]);
                            })->onReceive([$this, "onUpdateCondition"])->show($player);
                        break;
                    case 2:
                        (new ConditionContainerForm)->sendMoveCondition($player, $container, array_search($condition, $container->getConditions(), true));
                        break;
                    case 3:
                        $this->sendConfirmDelete($player, $condition, $container);
                        break;
                }
            })->addArgs($container, $condition)->addMessages($messages)->show($player);
    }

    public function onUpdateCondition(Player $player, ?array $formData, ConditionContainer $container, Condition $condition, callable $callback) {
        if ($formData === null) return;

        $data = $condition->parseFromFormData($formData);
        if ($data["cancel"]) {
            call_user_func_array($callback, [false]);
            return;
        }

        if (!empty($data["errors"])) {
            $condition->getEditForm($formData, $data["errors"])
                ->addArgs($container, $condition, $callback)
                ->onReceive([$this, "onUpdateCondition"])
                ->show($player);
            return;
        }
        try {
            $condition->loadSaveData($data["contents"]);
        } catch (FlowItemLoadException|\OutOfBoundsException $e) {
            $player->sendMessage(Language::get("action.error.recipe"));
            Main::getInstance()->getLogger()->logException($e);
            return;
        }
        call_user_func_array($callback, [true]);
    }

    public function selectConditionCategory(Player $player, ConditionContainer $container) {
        $buttons = [new Button("@form.back"), new Button("@form.items.category.favorite")];
        $categories = Category::getCategories();
        foreach ($categories as $category) {
            $buttons[] = new Button("@category.".$category);
        }
        /** @var Recipe|FlowItem $container */
        (new ListForm(Language::get("form.condition.category.title", [$container->getContainerName()])))
            ->setContent("@form.selectButton")
            ->addButtons($buttons)
            ->onReceive(function (Player $player, int $data, ConditionContainer $container, array $categories) {
                if ($data === 0) {
                    (new ConditionContainerForm)->sendConditionList($player, $container);
                    return;
                }
                if ($data === 1) {
                    $favorites = Main::getInstance()->getPlayerSettings()->getFavorites($player->getName(), "condition");
                    $conditions = [];
                    foreach ($favorites as $favorite) {
                        $condition = ConditionFactory::get($favorite);
                        if ($condition === null) continue;

                        $conditions[] = $condition;
                    }
                    Session::getSession($player)->set("flowItem_category", Language::get("form.items.category.favorite"));
                    $this->sendSelectCondition($player, $container, $conditions);
                    return;
                }
                $data -= 2;

                $category = $categories[$data];
                $conditions = ConditionFactory::getByFilter($category, Main::getInstance()->getPlayerSettings()->getNested($player->getName().".permission", 0));

                Session::getSession($player)->set("flowItem_category", Language::get("category.".$category));
                $this->sendSelectCondition($player, $container, $conditions);
            })->addArgs($container, $categories)->show($player);
    }

    public function sendSelectCondition(Player $player, ConditionContainer $container, array $conditions) {
        $buttons = [new Button("@form.back")];
        foreach ($conditions as $condition) {
            $buttons[] = new Button($condition->getName());
        }
        /** @var Recipe|FlowItem $container */
        (new ListForm(Language::get("form.condition.select.title", [$container->getContainerName(), Session::getSession($player)->get("flowItem_category", "")])))
            ->setContent(count($buttons) === 1 ? "@form.flowItem.empty" : "@form.selectButton")
            ->addButtons($buttons)
            ->onReceive(function (Player $player, int $data, ConditionContainer $container, array $conditions) {
                if ($data === 0) {
                    $this->selectConditionCategory($player, $container);
                    return;
                }
                $data -= 1;

                Session::getSession($player)->set("conditions", $conditions);
                $condition = clone $conditions[$data];
                $this->sendConditionMenu($player, $container, $condition);
            })->addArgs($container, $conditions)->show($player);
    }

    public function sendConditionMenu(Player $player, ConditionContainer $container, Condition $condition, array $messages = []) {
        $favorites = Main::getInstance()->getPlayerSettings()->getFavorites($player->getName(), "condition");
        /** @var Recipe|FlowItem $container */
        (new ListForm(Language::get("form.condition.menu.title", [$container->getContainerName(), $condition->getId()])))
            ->setContent($condition->getDescription())
            ->addButtons([
                new Button("@form.back"),
                new Button("@form.add"),
                new Button(in_array($condition->getId(), $favorites) ? "@form.items.removeFavorite" : "@form.items.addFavorite"),
            ])->onReceive(function (Player $player, int $data, ConditionContainer $container, Condition $condition) {
                switch ($data) {
                    case 0:
                        $conditions = Session::getSession($player)->get("conditions");
                        $this->sendSelectCondition($player, $container, $conditions);
                        break;
                    case 1:
                        if ($condition->hasCustomMenu()) {
                            Session::getSession($player)->push("parents", $container);
                            $container->addCondition($condition);
                            $condition->sendCustomMenu($player);
                            return;
                        }
                        $condition->getEditForm()
                            ->addArgs($container, $condition, function ($result) use ($player, $container, $condition) {
                                if ($result) {
                                    $container->addCondition($condition);
                                    (new ConditionContainerForm)->sendConditionList($player, $container, ["@form.added"]);
                                } else {
                                    $this->sendConditionMenu($player, $container, $condition, ["@form.cancelled"]);
                                }
                            })->onReceive([$this, "onUpdateCondition"])->show($player);
                        break;
                    case 2:
                        $config = Main::getInstance()->getPlayerSettings();
                        $config->toggleFavorite($player->getName(), "condition", $condition->getId());
                        $config->save();
                        $this->sendConditionMenu($player, $container, $condition, ["@form.changed"]);
                        break;
                }
            })->addArgs($container, $condition)->addMessages($messages)->show($player);
    }

    /**
     * @param Player $player
     * @param Condition $condition
     * @param ConditionContainer $container
     * @uses \aieuo\mineflow\flowItem\condition\ConditionContainerTrait::removeCondition()
     */
    public function sendConfirmDelete(Player $player, Condition $condition, ConditionContainer $container) {
        (new MineflowForm)->confirmDelete($player,
            Language::get("form.items.delete.title", [$container->getContainerName(), $condition->getName()]), trim($condition->getDetail()),
            function (Player $player) use ($condition, $container) {
                $index = array_search($condition, $container->getConditions(), true);
                $container->removeCondition($index);
                Session::getSession($player)->pop("parents");
                (new ConditionContainerForm)->sendConditionList($player, $container, ["@form.delete.success"]);
            },
            function (Player $player) use ($condition, $container) {
                if ($container instanceof FlowItem and $container->hasCustomMenu()) {
                    $container->sendCustomMenu($player, ["@form.cancelled"]);
                } else {
                    $this->sendAddedConditionMenu($player, $container, $condition, ["@form.cancelled"]);
                }
            });
    }
}