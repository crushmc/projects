<?php

/**
 * @name WorldZipper
 * @main minet\worldzipper\WorldZipper
 * @author minet
 * @api 4.0.0
 * @version B1
 */

namespace ojy\wz;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\world\World;
use ssss\utils\SSSSUtils;
use ZipArchive;
use const DIRECTORY_SEPARATOR;

function md($path){
    if(!file_exists($path)){
        mkdir($path);
    }
}

class WorldZipper extends PluginBase{

    /** @var WorldZipper */
    public static self $i;

    public function onLoad() : void{
        self::$i = $this;
    }

    public static function getInstance() : self{
        return static::$i;
    }

    public function onEnable() : void{
        @mkdir($this->getDataFolder());
        //////////////
        // Beta test.
        /*foreach (Server::getInstance()->getWorldManager()->getWorlds() as $world) {
            $this->zipWorld($world->getFolderName(), $world->getFolderName());
        }*/
        $this->getServer()->getCommandMap()->register('WorldZipper', new class() extends Command{
            public function __construct(){
                parent::__construct('wz', 'World Zipper!', '/wz');
                $this->setPermission(DefaultPermissions::ROOT_OPERATOR);
            }

            public function execute(CommandSender $sender, string $commandLabel, array $args){
                if($sender->hasPermission($this->getPermission())){
                    if(!isset($args[0])){
                        $args[0] = 'x';
                    }
                    switch($args[0]){
                        case 'zip':
                            if($sender instanceof Player){
                                if(isset($args[1])){
                                    unset($args[0]);
                                    $groupName = implode(' ', $args);
                                    if(WorldZipper::$i->zipWorld($groupName, $sender->getWorld()->getFolderName())){
                                        $sender->sendMessage('?????? ??????');
                                    }else{
                                        $sender->sendMessage('?????? ??????');
                                    }
                                }else{
                                    $sender->sendMessage('/wz zip [groupName] : ??????????????? ????????? ????????? ??????');
                                }
                            }
                            break;
                        case 'extract':
                            //0 -> groupName
                            //1 -> worldName
                            //2 -> localName
                            if(isset($args[3])){
                                $groupName = $args[1];
                                $worldName = $args[2];
                                $localName = $args[3];
                                $world = WorldZipper::getInstance()->extractWorld($groupName, $worldName, $localName);
                                if($world instanceof World){
                                    SSSSUtils::message($sender, "{$localName} ?????? ????????? ??????????????????.");
                                }else{
                                    SSSSUtils::message($sender, '?????? ????????? ??????????????????.');
                                }
                            }else{
                                SSSSUtils::message($sender, '/wz extract [groupName] [worldName] [localName]');
                            }
                            break;
                        case 'list':
                            $files = array_diff(scandir(WorldZipper::$i->getDataFolder()), ['.', '..']);
                            if(count($files) > 0){
                                $sender->sendMessage('== ????????? ?????? ????????? ?????? ==');
                                foreach($files as $file){
                                    $sender->sendMessage($file);
                                }
                            }else{
                                $sender->sendMessage('???????????? ????????? ????????????.');
                            }
                            break;
                        case 'see':
                            if(isset($args[1])){
                                unset($args[0]);
                                $groupName = implode(' ', $args);
                                if(is_dir(WorldZipper::$i->getDataFolder() . $groupName)){
                                    $files = array_diff(scandir(WorldZipper::$i->getDataFolder() . $groupName), ['.', '..']);
                                    if(count($files) > 0){
                                        $sender->sendMessage("== {$groupName} ?????? ????????? ?????? ==");
                                        foreach($files as $file){
                                            $sender->sendMessage($file);
                                        }
                                    }else{
                                        $sender->sendMessage('???????????? ???????????? ????????????.');
                                    }
                                }else{
                                    $sender->sendMessage('???????????? ?????? ???????????????.');
                                }
                            }
                            break;
                        default:
                            $sender->sendMessage('/wz zip [groupName] : ??????????????? ????????? ????????? ??????');
                            $sender->sendMessage('/wz list : ?????? ????????? ??????');
                            $sender->sendMessage('/wz see [groupName] : ?????? ?????? ?????? ??????????????? ??????');
                            $sender->sendMessage('/wz extract [groupName] [worldName] [localName] : ????????????');
                            break;
                    }
                }
            }

        });
    }

    public function isExistGroup(string $groupName) : bool{
        return is_dir($this->getDataFolder() . $groupName);
    }

    public function getWorldList(string $groupName) : array{
        if(is_dir(self::$i->getDataFolder() . $groupName)){
            return array_diff(scandir(WorldZipper::$i->getDataFolder() . $groupName), ['.', '..']);
        }
        return [];
    }

    public function addFiles(ZipArchive $zip, string $dirPath, string $worldName){
        foreach(array_diff(scandir($dirPath), ['.', '..']) as $file){
            $localPath = "{$dirPath}/{$file}";
            $localPath = str_replace($this->getServer()->getDataPath() . "worlds/{$worldName}", '', $localPath);
            if(is_dir("{$dirPath}/{$file}")){
                $zip->addEmptyDir($localPath);
                $this->addFiles($zip, "{$dirPath}/{$file}", $worldName);
            }elseif(is_file("{$dirPath}/{$file}")){
                $filePath = "{$dirPath}/{$file}";
                $zip->addFile($filePath, $localPath) ? $this->getLogger()->info("success add: {$filePath}") : $this->getLogger()->info("fail add; {$filePath}");
            }
            /*$localPath = "{$dirPath}/{$file}";
            $localPath = str_replace($this->getServer()->getDataPath() . "worlds/{$worldName}", "", $localPath);
            $zip->addFile("{$dirPath}/{$file}", $localPath) ? $this->getLogger()->info("success add: {$dirPath}/{$file}") : $this->getLogger()->info("fail add: {$dirPath}/{$file}");
        */
        }
    }

    public function random(string $groupName) : ?string{
        if(is_dir($this->getDataFolder() . $groupName)){
            $files = array_values(array_diff(scandir($this->getDataFolder() . $groupName), ['.', '..']));
            return $files[random_int(0, count($files) - 1)];
        }
        return null;
    }

    public function extractWorld(string $groupName, string $worldName, string $localName, string $path = null) : ?World{
        if($path === null){
            $path = $this->getServer()->getDataPath() . 'worlds/';
        }
        if($localName === null){
            $localName = $worldName;
        }
        if(($zip = $this->getZippedWorld($groupName, $worldName)) instanceof ZipArchive){
            if(($world = $this->getServer()->getWorldManager()->getWorldByName($localName)) instanceof World){
                $players = $world->getPlayers();
                foreach($players as $player){
                    $player->sendMessage('world reloading..');
                    $player->teleport($this->getServer()->getWorldManager()->getDefaultWorld()?->getSafeSpawn());
                }
                foreach($world->getEntities() as $e){
                    $e->kill();
                }
                if($this->getServer()->getWorldManager()->isWorldLoaded($localName))
                    $this->getServer()->getWorldManager()->unloadWorld($world, true);
            }
            md("{$path}{$localName}");
            $zip->extractTo("{$path}{$localName}");
            $zip->close();
            if(!$this->getServer()->getWorldManager()->isWorldLoaded($localName)){
                $this->getServer()->getWorldManager()->loadWorld($localName);
            }

            Server::getInstance()->getLogger()->info('succeed extract world');
            return $this->getServer()->getWorldManager()->getWorldByName($localName);
        }else{
            Server::getInstance()->getLogger()->info('can not find zipped world');
        }
        return null;
    }

    public function isExistZippedWorld(string $groupName, string $worldName) : bool{
        if($this->isExistGroup($groupName)){
            if(file_exists($this->getDataFolder() . $groupName . "/{$worldName}.zip")){
                return true;
            }
        }
        return false;
    }

    public function getZippedWorld(string $groupName, string $worldName) : ?ZipArchive{
        if(!$this->isExistZippedWorld($groupName, $worldName)){
            return null;
        }

        $zip = new ZipArchive();
        return $zip->open($this->getDataFolder() . $groupName . "/{$worldName}.zip") ? $zip : null;
    }

    public function zipWorld(string $groupName, string $worldName) : bool{
        $dataPath = Server::getInstance()->getDataPath() . 'worlds' . DIRECTORY_SEPARATOR;
        if(!is_dir($dataPath . $worldName)){
            $this->getLogger()->info("{$worldName} ????????? ?????? ??? ????????????.");
            return false;
        }
        @mkdir($this->getDataFolder() . $groupName);
        $zipName = "{$this->getDataFolder()}{$groupName}/{$worldName}.zip";
        $zip = new ZipArchive();
        if($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true){
            foreach(array_diff(scandir($dataPath . $worldName), ['.', '..']) as $file){
                if(is_dir("{$dataPath}{$worldName}/{$file}")){
                    $zip->addEmptyDir($file);
                    $this->addFiles($zip, "{$dataPath}{$worldName}/{$file}", $worldName);
                }elseif(is_file("{$dataPath}{$worldName}/{$file}")){
                    $filePath = "{$dataPath}{$worldName}/{$file}";
                    $zip->addFile($filePath, $file) ? $this->getLogger()->info("success add: {$filePath}") : $this->getLogger()->info("fail add; {$filePath}");
                }
            }
            $zip->close();
            $this->getLogger()->info("{$worldName} ????????? ??????????????????.");
            return true;
        }
        return false;
    }
}