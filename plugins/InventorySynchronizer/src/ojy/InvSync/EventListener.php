<?php

namespace ojy\InvSync;

use alvin0319\CrushStarGatePlus\event\UpdateInfoRequestEvent;
use ojy\InvSync\InventorySynchronizer as InvSync;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use function json_decode;
use function time;
use function var_dump;

final class EventListener implements Listener{

    /**
     * @param PlayerJoinEvent $event
     *
     * @priority MONITOR
     */
    public function onPlayerJoin(PlayerJoinEvent $event) : void{
        $session = InvSync::createSession($event->getPlayer());
        $session->applyTime = time() + 4;
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority LOWEST
     */
    public function onPlayerQuit(PlayerQuitEvent $event) : void{
        $session = InvSync::getSession($event->getPlayer());
        if($session !== null && $session->isLoaded()){
            $session->saveInventory($event->getPlayer());

            InvSync::saveSession($event->getPlayer());
        }
    }

    public function onUpdateInfoRequest(UpdateInfoRequestEvent $event) : void{
        if($event->getName() === InvSync::CREATE_SESSION){
            InvSync::createSession($event->getArgs());
        }elseif($event->getName() === InvSync::SAVE_SESSION){
            $data = json_decode($event->getArgs(), true);
            $session = InvSync::getSession($data['player']);
            if($session !== null){
                $session->setData($data['data']);
                var_dump('setData');
            }
        }
    }

    public function onInventoryOpen(InventoryOpenEvent $event) : void{
        $session = InvSync::getSession($event->getPlayer());
        if($session === null || !$session->isLoaded()){
            $event->cancel();
        }
    }

    public function onInventoryClose(InventoryCloseEvent $event) : void{
        $session = InvSync::getSession($event->getPlayer());
        if($session === null || !$session->isLoaded()){
            return;
        }
        $session->saveInventory($event->getPlayer());
    }
}