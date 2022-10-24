<?php

/**
 * @name c
 * @author ojy
 * @version B1
 * @api 4.0.0
 * @main o\c\c
 */

namespace o\c;

use Closure;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class c extends PluginBase implements Listener{

    public function onLoad() : void{
    }

    public function onEnable() : void{
    }

    public static function command(string $name, string $description, string $usage, array $aliases, Closure $f, bool $op = false) : void{
        Server::getInstance()->getCommandMap()->register($name, new class($name, $description, $usage, $aliases, $f, $op) extends Command{


            public function __construct(string $n, string $d, string $u, array $aliases, protected Closure $f, protected bool $op){
                parent::__construct($n, $d, $u, $aliases);

                if($op){
                    $this->setPermission(DefaultPermissions::ROOT_OPERATOR);
                }
            }

            public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
                if(!$this->op || $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
                    $f = $this->f;
                    $f($sender, $commandLabel, $args);
                }
            }
        });
    }
}