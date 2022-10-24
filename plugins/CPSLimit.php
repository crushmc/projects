<?php

/**
 * @name CPSLimit
 * @author ojy
 * @main ojy\cps\CPSLimit
 * @api 4.0.0
 * @version B1
 */

namespace ojy\cps;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class CPSLimit extends PluginBase implements Listener{

    public static array $click = [];

    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public static function set(Player $player) : void{
        if(!isset(self::$click[$player->getName()])){
            self::$click[$player->getName()] = [];
        }

        self::$click[$player->getName()][] = microtime(true);
        if(count(self::$click[$player->getName()]) > 25){
            $firstValue = array_shift(self::$click[$player->getName()]);
            $lastValue = self::$click[$player->getName()][count(self::$click[$player->getName()]) - 1];
            $timeDiff = $lastValue - $firstValue;
            if($timeDiff <= 1){
                $player->kick('kick', 'kick');
            }
        }
    }

    public function recvPK(DataPacketReceiveEvent $event) : void{
        $pk = $event->getPacket();
        if($pk instanceof LevelSoundEventPacket){
            if($pk->sound === LevelSoundEvent::ATTACK_NODAMAGE){
                self::set($event->getOrigin()->getPlayer());
            }
        }
        if($pk instanceof InventoryTransactionPacket){
            if($pk->trData instanceof UseItemOnEntityTransactionData){
                self::set($event->getOrigin()->getPlayer());
            }
        }
    }
}