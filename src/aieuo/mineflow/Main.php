<?php

namespace aieuo\mineflow;

use aieuo\mineflow\event\EventManager;
use aieuo\mineflow\flowItem\action\ActionFactory;
use aieuo\mineflow\flowItem\condition\ConditionFactory;
use aieuo\mineflow\utils\FormManager;
use aieuo\mineflow\utils\PlayerConfig;
use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use aieuo\mineflow\variable\VariableHelper;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\recipe\RecipeManager;
use aieuo\mineflow\event\ServerStartEvent;
use aieuo\mineflow\economy\Economy;
use aieuo\mineflow\command\MineflowCommand;
use aieuo\mineflow\command\CommandManager;

class Main extends PluginBase {

    /** @var Main */
    private static $instance;

    /** @var Config */
    private $config;
    /** @var PlayerConfig */
    private $playerSettings;

    /** @var bool */
    private $loaded = false;

    /** @var RecipeManager */
    private static $recipeManager;

    /** @var CommandManager */
    private static $commandManager;

    /** @var VariableHelper */
    private static $variableHelper;

    /** @var FormManager */
    private static $formManager;

    /** @var EventManager */
    private static $eventManager;

    public static function getInstance(): ?self {
        return self::$instance;
    }

    public function onEnable() {
        self::$instance = $this;

        $serverLanguage = $this->getServer()->getLanguage()->getLang();
        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML, [
            "language" => in_array($serverLanguage, Language::getAvailableLanguages()) ? $serverLanguage : "eng",
        ]);
        $this->config->save();

        Language::setLanguage($this->config->get("language", "eng"));
        if (!Language::loadMessage()) {
            foreach (Language::getLoadErrorMessage($serverLanguage) as $error) {
                $this->getLogger()->warning($error);
            }
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $this->playerSettings = new PlayerConfig($this->getDataFolder()."player.yml", Config::YAML);

        $this->getServer()->getCommandMap()->register($this->getName(), new MineflowCommand);

        (new Economy($this))->loadPlugin();

        ActionFactory::init();
        ConditionFactory::init();


        self::$commandManager = new CommandManager($this, new Config($this->getDataFolder()."commands.yml", Config::YAML));

        self::$formManager = new FormManager(new Config($this->getDataFolder()."forms.json", Config::JSON));

        $events = new Config($this->getDataFolder()."events.yml", Config::YAML);
        self::$eventManager = new EventManager($events);

        self::$variableHelper = new VariableHelper(new Config($this->getDataFolder()."variables.json", Config::JSON));

        self::$recipeManager = new RecipeManager($this->getDataFolder()."recipes/");
        self::$recipeManager->loadRecipes();

        (new EventListener($this))->registerEvents();

        if (!file_exists($this->getDataFolder()."imports/")) @mkdir($this->getDataFolder()."imports/", 0777, true);

        $this->loaded = true;
        (new ServerStartEvent($this))->call();
    }

    public function onDisable() {
        if (!$this->loaded) return;
        self::$recipeManager->saveAll();
        self::$formManager->saveAll();
        self::$variableHelper->saveAll();
    }

    public function getConfig(): Config {
        return $this->config;
    }

    public function getPlayerSettings(): PlayerConfig {
        return $this->playerSettings;
    }

    public static function getRecipeManager(): RecipeManager {
        return self::$recipeManager;
    }

    public static function getCommandManager(): CommandManager {
        return self::$commandManager;
    }

    public static function getFormManager(): FormManager {
        return self::$formManager;
    }

    public static function getEventManager(): EventManager {
        return self::$eventManager;
    }

    public static function getVariableHelper(): VariableHelper {
        return self::$variableHelper;
    }
}