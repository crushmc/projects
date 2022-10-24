<?php

/**
 * @name IV
 * @main iv\IV
 * @author ojy
 * @api 4.0.0
 * @version B1
 */

namespace iv;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use ssss\utils\SSSSUtils;

class IV extends PluginBase
{

    public function onEnable(): void
    {
        Server::getInstance()->getCommandMap()->register('IV', new class($this) extends Command {

            public function __construct(private IV $plugin)
            {
                parent::__construct('iv', '아이템 코드를 확인합니다.', '/iv');
            }

            public function execute(CommandSender $sender, string $commandLabel, array $args)
            {
                if ($sender instanceof Player && SSSSUtils::isOp($sender)) {
                    $hand = $sender->getInventory()->getItemInHand();
                    $name = $hand->getCustomName() !== '' ? $hand->getCustomName() : $hand->getName();
                    $sender->sendMessage("§l§b[알림] §r§7아이템 코드: {$hand->getId()}:{$hand->getMeta()}, 아이템 이름: {$name}");
                }
            }
        });
        Server::getInstance()->getPluginManager()->registerEvents(new class implements Listener {
            public function onTouch(PlayerInteractEvent $event): void
            {
                $player = $event->getPlayer();
                if (SSSSUtils::isOp($player) && $player->getInventory()->getItemInHand()->getId() === ItemIds::STICK) {
                    if ($event->getAction() === $event::RIGHT_CLICK_BLOCK) {
                        $block = $event->getBlock();
                        $player->sendMessage("§l§b[알림] §r§7블럭 아이디: {$block->getId()}:{$block->getMeta()}");
                    }
                }
            }
        }, $this);
    }
}