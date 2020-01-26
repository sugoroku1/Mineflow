<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\utils\Language;
use aieuo\mineflow\utils\Logger;
use pocketmine\event\Event;
use pocketmine\event\Cancellable;
use pocketmine\entity\Entity;
use aieuo\mineflow\utils\Categories;
use aieuo\mineflow\recipe\Recipe;

class EventCancel extends Action {

    protected $id = self::EVENT_CANCEL;

    protected $name = "action.eventCancel.name";
    protected $detail = "action.eventCancel.detail";

    protected $category = Categories::CATEGORY_ACTION_COMMON;

    protected $targetRequired = Recipe::TARGET_REQUIRED_NONE;

    /** @var Event */
    private $event;

    public function getEvent(): ?Event {
        return $this->event;
    }

    public function setEvent(?Event $event) {
        $this->event = $event;
    }

    public function execute(?Entity $target, Recipe $origin): ?bool {
        if (!$this->canExecute($target)) return null;

        $event = $this->getEvent();
        if (!($event instanceof Cancellable)) {
            Logger::warning(Language::get("flowItem.error", [$this->getName(), ["action.eventCancel.notCancelable"]]), $target);
            return false;
        }
        $event->setCancelled();
        return true;
    }

    public function isDataValid(): bool {
        return true;    }

    public function loadSaveData(array $content): ?Action {
        return $this;
    }

    public function serializeContents(): array {
        return [];
    }
}