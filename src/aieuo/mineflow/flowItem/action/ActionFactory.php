<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\economy\Economy;
use pocketmine\Server;

class ActionFactory {
    /** @var Action[] */
    private static $list = [];

    public static function init(): void {
        self::register(new DoNothing);
        self::register(new EventCancel);
        /* message */
        self::register(new SendMessage);
        self::register(new SendTip);
        self::register(new SendPopup);
        self::register(new BroadcastMessage);
        self::register(new SendMessageToOp);
        self::register(new SendTitle);
        /* entity */
        self::register(new SetNameTag);
        self::register(new GetEntity);
        self::register(new Teleport);
        self::register(new Motion);
        self::register(new SetYaw);
        self::register(new SetPitch);
        self::register(new AddDamage);
        self::register(new SetImmobile);
        self::register(new UnsetImmobile);
        self::register(new SetHealth);
        self::register(new SetMaxHealth);
        self::register(new SetScale);
        self::register(new AddEffect);
        /* player */
        self::register(new GetPlayerByName);
        self::register(new SetSleeping);
        self::register(new SetSitting);
        self::register(new Kick);
        self::register(new SetFood);
        self::register(new SetGamemode);
        self::register(new ShowBossbar);
        self::register(new RemoveBossbar);
        self::register(new ShowScoreboard);
        self::register(new HideScoreboard);
        self::register(new PlaySound);
        self::register(new AddPermission);
        self::register(new RemovePermission);
        self::register(new AddXpProgress);
        self::register(new AddXpLevel);
        /* item */
        self::register(new CreateItemVariable);
        self::register(new AddItem);
        self::register(new SetItemInHand);
        self::register(new RemoveItem);
        self::register(new RemoveItemAll);
        self::register(new SetItemDamage);
        self::register(new SetItemCount);
        self::register(new SetItemName);
        self::register(new SetItemLore);
        self::register(new AddEnchantment);
        self::register(new EquipArmor);
        self::register(new SetItem);
        self::register(new ClearInventory);
        self::register(new GetInventoryContents);
        /* script */
        self::register(new IFAction);
        self::register(new ElseifAction);
        self::register(new ElseAction);
        self::register(new RepeatAction);
        self::register(new WhileTaskAction);
        self::register(new Wait);
        self::register(new CallRecipe);
        self::register(new ExecuteRecipe);
        self::register(new ExecuteRecipeWithEntity);
        self::register(new SaveData);
        self::register(new CreateConfigVariable);
        self::register(new SetConfigData);
        self::register(new SaveConfigFile);
        self::register(new ExitRecipe);
        /* calculation */
        self::register(new FourArithmeticOperations);
        self::register(new Calculate);
        self::register(new Calculate2);
        self::register(new GetPi);
        self::register(new GetE);
        self::register(new GenerateRandomNumber);
        /* string */
        self::register(new EditString);
        self::register(new StringLength);
        /* variable */
        self::register(new AddVariable);
        self::register(new DeleteVariable);
        self::register(new CreateListVariable);
        self::register(new AddListVariable);
        self::register(new CreateMapVariable);
        self::register(new AddMapVariable);
        self::register(new DeleteListVariableContent);
        self::register(new CreatePositionVariable);
        self::register(new GetVariableNested);
        self::register(new CountListVariable);
        self::register(new JoinListVariableToString);
        /* form */
        self::register(new SendForm);
        self::register(new SendInputForm);
        self::register(new SendMenuForm);
        /* command */
        self::register(new Command);
        self::register(new CommandConsole);
        /* block */
        self::register(new CreateBlockVariable);
        /* level */
        self::register(new SetBlock);
        self::register(new GetBlock);
        self::register(new AddParticle);
        self::register(new PlaySoundAt);
        /* scoreboard */
        self::register(new CreateScoreboardVariable);
        self::register(new SetScoreboardScore);
        self::register(new SetScoreboardScoreName);
        self::register(new IncrementScoreboardScore);
        self::register(new DecrementScoreboardScore);
        self::register(new RemoveScoreboardScore);
        /* other plugins */
        if (Economy::isPluginLoaded()) {
            self::register(new AddMoney);
            self::register(new TakeMoney);
            self::register(new SetMoney);
            self::register(new GetMoney);
        }
        if (Server::getInstance()->getPluginManager()->getPlugin("if") !== null) {
            self::register(new ExecuteIFChain);
        }
        if (Server::getInstance()->getPluginManager()->getPlugin("ReplenishResources") !== null) {
            self::register(new ReplenishResource);
        }
    }

    /**
     * @param  string $id
     * @return Action|null
     */
    public static function get(string $id): ?Action {
        if (isset(self::$list[$id])) {
            return clone self::$list[$id];
        }
        return null;
    }

    /**
     * @param string $category
     * @param int $permission
     * @return Action[]
     */
    public static function getByFilter(string $category = null, int $permission = null): array {
        $processes = [];
        foreach (self::$list as $process) {
            if ($category !== null and $process->getCategory() !== $category) continue;
            if ($permission !== null and $process->getPermission() > $permission) continue;
            $processes[] = $process;
        }
        return $processes;
    }

    /**
     * @return array
     */
    public static function getAll(): array {
        return self::$list;
    }

    /**
     * @param  Action $process
     */
    public static function register(Action $process): void {
        self::$list[$process->getId()] = clone $process;
    }
}