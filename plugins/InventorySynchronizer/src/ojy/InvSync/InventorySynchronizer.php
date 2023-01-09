<?php

namespace ojy\InvSync;

use ojy\InvSync\session\InvSyncSession;
use ojy\SQLConnector\Connector;
use ojy\TransferEvent\event\PlayerTransferServerEvent;
use ojy\TransferEvent\PreventTransfer;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDataSaveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use poggit\libasynql\DataConnector;
use SOFe\AwaitGenerator\Await;
use SOFe\AwaitStd\AwaitStd;
use function strtolower;

final class InventorySynchronizer extends PluginBase implements Listener{

    public static DataConnector $connector;

    public static Database $database;

    public static AwaitStd $std;

    public function onEnable() : void{
        self::$connector = Connector::getConnector($this, 5);
        self::$database = new Database(self::$connector);
        Await::g2c(self::$database->invsyncInit());
        self::$connector->waitAll();
        self::$std = AwaitStd::init($this);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /** @var InvSyncSession[] */
    public static array $sessions = [];

    public static function createSession(Player|string $player) : InvSyncSession{
        $player = strtolower($player instanceof Player ? $player->getName() : $player);
        if(isset(self::$sessions[$player])){
            return self::$sessions[$player];
        }
        return self::$sessions[$player] = new InvSyncSession($player);
    }

    public static function removeSession(Player|string $player) : void{
        $player = strtolower($player instanceof Player ? $player->getName() : $player);
        if(isset(self::$sessions[$player])){
            if(self::$sessions[$player]->isLoaded()){
                self::$sessions[$player]->save();
            }
            unset(self::$sessions[$player]);
        }
    }

    public static function getSession(Player|string $player) : ?InvSyncSession{
        $player = strtolower($player instanceof Player ? $player->getName() : $player);
        return self::$sessions[$player] ?? null;
    }

    /**
     * @param PlayerJoinEvent $event
     *
     * @priority MONITOR
     */
    public function onPlayerJoin(PlayerJoinEvent $event) : void{
        self::createSession($event->getPlayer());
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority LOWEST
     */
    public function onPlayerQuit(PlayerQuitEvent $event) : void{
        self::removeSession($event->getPlayer());
    }

    public function onPlayerTransfer(PlayerTransferServerEvent $event) : void{
        if(!$event->isCancelled()){
            ($session = self::getSession($event->getPlayer()))?->addCallback(function() use ($session) : void{
                $session->save();
            });
        }
    }

    public function onPlayerDataSave(PlayerDataSaveEvent $event) : void{
        $player = $event->getPlayer();
        if($player !== null){
            $session = self::getSession($event->getPlayer());
            if($session === null){
                return;
            }
            $session->addCallback(function() use ($session, $player) : void{
                $session->saveInventory($player);
            });
        }
    }

    public function onItemPickup(EntityItemPickupEvent $event) : void{
        $entity = $event->getEntity();
        if(!$entity instanceof Player){
            return;
        }
        $session = self::getSession($entity);
        if($session === null || !$session->isLoaded()){
            $event->cancel();
        }

        PreventTransfer::prevent($entity, 3);
    }

    public function onInventoryOpen(InventoryOpenEvent $event) : void{
        $session = self::getSession($event->getPlayer());
        if($session === null || !$session->isLoaded()){
            $event->cancel();
        }

        PreventTransfer::prevent($event->getPlayer(), 3);
    }

    public function onInventoryClose(InventoryCloseEvent $event) : void{
        $session = self::getSession($event->getPlayer());
        if($session === null || !$session->isLoaded()){
            return;
        }
        $session->saveInventory($event->getPlayer());

        PreventTransfer::prevent($event->getPlayer(), 3);
    }

    public function onDisable() : void{
        foreach(self::$sessions as $session){
            if($session->isLoaded()){
                $session->save();
            }
        }
        self::$connector->waitAll();
        self::$connector->close();
    }
}