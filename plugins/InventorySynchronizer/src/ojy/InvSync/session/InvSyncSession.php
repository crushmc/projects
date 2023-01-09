<?php

namespace ojy\InvSync\session;

use ojy\InvSync\InventorySynchronizer;
use ojy\trait\SessionTrait;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\Server;
use SOFe\AwaitGenerator\Await;
use function count;
use function json_decode;
use function json_encode;
use function strtolower;

final class InvSyncSession{
    use SessionTrait;

    public function __construct(string $name){
        $this->name = strtolower($name);
        $this->player ??= $this->getPlayer();

        Await::f2c(function() : \Generator{
            for($i = 0; $i < 5; $i++){
                $rows = yield from InventorySynchronizer::$database->invsyncGet($this->name);
                if(count($rows) > 0){
                    if(++$this->syncBlocked <= 4){
                        yield from InventorySynchronizer::$std->sleep(20);
                        continue;
                    }

                    if(!$this->loaded){
                        $data = json_decode($rows[0]['data'], true);
                        $this->apply($data);
                    }
                }else{
                    yield from InventorySynchronizer::$database->invsyncSet($this->name, '[]');
                }
                break;
            }
            $this->load();
        });
    }

    public function getPlayer() : ?Player{
        return $this->player = Server::getInstance()->getPlayerExact($this->name);
    }

    public function apply(array $data = []) : void{
        $player = $this->getPlayer();
        if($player === null || !$player->isOnline()){
            return;
        }

        $mainInventory = [];
        foreach($data['mainInventory'] ?? [] as $index => $itemData){
            $item = Item::jsonDeserialize($itemData);
            $mainInventory[$index] = $item;
        }
        $player->getInventory()->setContents($mainInventory);
        $armorInventory = [];
        foreach($data['armorInventory'] ?? [] as $index => $itemData){
            $item = Item::jsonDeserialize($itemData);
            $armorInventory[$index] = $item;
        }
        $player->getArmorInventory()->setContents($armorInventory);
        $offHandInventory = Item::jsonDeserialize($data['offHandInventory'] ?? VanillaItems::AIR()->jsonSerialize());
        $player->getOffHandInventory()->setItem(0, $offHandInventory);
        $player->getInventory()->setHeldItemIndex($data['selectedHotbar'] ?? 0);
    }

    public function saveInventory(Player $player) : void{
        $data = [];
        $mainInventory = [];
        foreach($player->getInventory()->getContents(true) as $i => $item){
            $mainInventory[$i] = $item->jsonSerialize();
        }
        $data['mainInventory'] = $mainInventory;
        $armorInventory = [];
        foreach($player->getArmorInventory()->getContents(true) as $i => $item){
            $armorInventory[$i] = $item->jsonSerialize();
        }
        $data['armorInventory'] = $armorInventory;
        $offHandInventory = $player->getOffHandInventory()->getItem(0);
        $data['offHandInventory'] = $offHandInventory;
        $selectedHotbar = $player->getInventory()->getHeldItemIndex();
        $data['selectedHotbar'] = $selectedHotbar;

        $encoded = json_encode($data);
        Await::g2c(InventorySynchronizer::$database->invsyncUpdate($this->name, $encoded));
    }

    public function save() : void{
        $player = $this->getPlayer();
        if($player === null){
            return;
        }
        $this->saveInventory($player);
    }
}