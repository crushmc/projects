<?php
/**
 * @name SSSSUtils
 * @main ssss\utils\SSSSUtils
 * @author ssss
 * @api 4.0.0
 * @version x
 */

namespace ssss\utils;

use alvin0319\CrushStarGatePlus\Loader;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Tag;
use pocketmine\network\mcpe\protocol\OnScreenTextureAnimationPacket;
use pocketmine\network\mcpe\protocol\ToastRequestPacket;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\world\Position;

class SSSSUtils extends PluginBase{

    public function onEnable() : void{
    }

    public static function isOp(CommandSender $player) : bool{
        return $player->hasPermission(DefaultPermissions::ROOT_OPERATOR) || !$player instanceof Player;
    }

    public static function equalsVector(Vector3 $v1, Vector3 $v2) : bool{
        return $v1->equals($v2);
    }

    public static function itemName(Item $item) : string{
        return $item->getCustomName() !== '' ? $item->getCustomName() : $item->getName();
    }

    public const ENCHANTMENT_NAME = [
        9 => '날카로움',
        15 => '효율성',
        17 => '견고',
        18 => '행운'
    ];

    public static function getEnchantmentsString(Item $item) : array{
        $res = [];
        foreach($item->getEnchantments() as $enchantmentInstance){
            $enchantmentName = self::ENCHANTMENT_NAME[self::ENCHANTMENT_NAME[EnchantmentIdMap::getInstance()->toId($enchantmentInstance->getType())]] ?? null;
            if($enchantmentName !== null){
                $enchantmentLevel = $enchantmentInstance->getLevel();
                $res[] = [
                    'enchantmentName' => $enchantmentName,
                    'enchantmentLevel' => $enchantmentLevel
                ];
            }
        }
        return $res;

    }

    public static function osta(Player $player, int $code) : void{
        $pk = new OnScreenTextureAnimationPacket();

        $pk->effectId = $code;
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public static function title(Player $player, string $title, string $subTitle = '') : void{
        if(!$player->isConnected()){
            return;
        }
        $player->sendTitle($title, $subTitle);
    }

    public static function broadcastMessage(string $message, string $prefix = '', ?array $players = null) : void{
        if($players === null){
            Loader::getInstance()->requestChatSend(Loader::PLAYER_ALL, $prefix . $message);
        }else{
            foreach($players as $playerName){
                Loader::getInstance()->requestChatSend($playerName, $prefix . $message);
            }
        }
    }

    public static function message(CommandSender $sender, string $message, string $prefix = '') : void{
        if($prefix === ''){
            $prefix = '§l§e알림 | §r§f';
        }
        if($sender instanceof Player && !$sender->isConnected()){
            return;
        }
        $sender->sendMessage($prefix . $message);
    }

    public static function popup(Player $sender, string $message) : void{
        if(!$sender->isConnected()){
            return;
        }
        $sender->sendPopup('§l§6[!] §r§f' . $message);
    }

    public static function tip(Player $sender, string $message, string $prefix = '') : void{
        if($prefix === ''){
            $prefix = '§l§e알림 | §r§f';
        }
        if(!$sender->isConnected()){
            return;
        }
        $sender->sendTip($prefix . $message);
    }

    public static function caution(CommandSender $sender, string $message) : void{
        if($sender instanceof Player && !$sender->isConnected()){
            return;
        }
        $sender->sendMessage("§l§6주의 | §r§f{$message}");
    }

    public static function info(CommandSender $sender, string $message) : void{
        if($sender instanceof Player && !$sender->isConnected()){
            return;
        }
        $sender->sendMessage("§l§e알림 | §r§f{$message}");
    }

    public static function prevent(CommandSender $sender, string $message) : void{
        if($sender instanceof Player && !$sender->isConnected()){
            return;
        }
        $sender->sendMessage("§l§c금지 | §r§f{$message}");
    }

    public static function setNamedTagEntry(Item $item, string $tagName, Tag $newTag) : void{
        $tag = $item->getNamedTag();
        $tag->setTag($tagName, $newTag);
        $item->setNamedTag($tag);
    }

    public static function getNamedTagEntry(Item $item, string $name) : ?Tag{
        return $item->getNamedTag()->getTag($name);
    }

    public static function posToString(Position $pos) : string{
        return implode(':', [
            $pos->x,
            $pos->y,
            $pos->z,
            $pos->getWorld()->getFolderName()
        ]);
    }

    public static function strToPosition(string $pos) : ?Position{
        $p = explode(':', $pos);

        $load = Server::getInstance()->getWorldManager()->loadWorld($p[3]);
        if($load){
            return new Position((int) $p[0], (int) $p[1], (int) $p[2], Server::getInstance()->getWorldManager()->getWorldByName($p[3]));
        }
        return null;
    }

    public static function sendToast(Player $player, string $title, string $body) : void{
        if($player->isConnected()){
            $player->getNetworkSession()->sendDataPacket(ToastRequestPacket::create($title, $body));
        }
    }

    public static function posToAABB(Vector3 $pos, int $radius, bool $includeY = false) : AxisAlignedBB{
        return new AxisAlignedBB(
            $pos->getFloorX() - $radius,
            $includeY ? $pos->getFloorY() - $radius : 0,
            $pos->getFloorZ() - $radius,
            $pos->getFloorX() + $radius,
            $includeY ? $pos->getFloorY() + $radius : 255,
            $pos->getFloorZ() + $radius
        );
    }
}