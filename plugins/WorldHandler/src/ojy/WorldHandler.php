<?php

namespace ojy;

use InvalidArgumentException;
use name\uimanager\CustomForm;
use name\uimanager\element\Button;
use name\uimanager\element\DropDown;
use name\uimanager\element\Input;
use name\uimanager\SimpleForm;
use o\c\c;
use ojy\generators\EmptyGenerator;
use pocketmine\command\CommandSender;
use pocketmine\event\world\WorldInitEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\player\ChunkSelector;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\world\format\io\FormatConverter;
use pocketmine\world\format\io\WritableWorldProvider;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;
use pocketmine\world\WorldException;
use ReflectionClass;
use ssss\utils\SSSSUtils;
use Webmozart\PathUtil\Path;

class WorldHandler extends PluginBase{

    public static string $worldPath = '';

    public static function isLevelGenerated(string $name) : bool{
        if(trim($name) === ''){
            return false;
        }
        $path = self::$worldPath . $name . '/';
        if(!(Server::getInstance()->getWorldManager()->getWorldByName($name) instanceof World)){
            return is_dir($path) and count(array_filter(scandir($path, SCANDIR_SORT_NONE), function(string $v) : bool{
                    return $v !== '..' and $v !== '.';
                })) > 0;
        }

        return true;
    }

    /**
     * Generates a new world if it does not exist
     *
     * @throws InvalidArgumentException
     */
    public static function generateWorld(string $name, WorldCreationOptions $options, bool $backgroundGeneration = true) : bool{
        if(trim($name) === '' or self::isLevelGenerated($name)){
            return false;
        }

        $worldManager = Server::getInstance()->getWorldManager();
        $providerEntry = $worldManager->getProviderManager()->getDefault();

        $path = self::$worldPath . $name . '/';
        $providerEntry->generate($path, $name, $options);

        $world = new World(Server::getInstance(), $name, $providerEntry->fromPath($path), Server::getInstance()->getAsyncPool());

        $worlds = (new ReflectionClass('pocketmine\world\WorldManager'))->getProperty('worlds');
        $worlds->setAccessible(true);
        $data = $worlds->getValue($worldManager);
        $data[$world->getId()] = $world;
        $worlds->setValue($worldManager, $data);

        $world->setAutoSave(true);

        (new WorldInitEvent($world))->call();

        (new WorldLoadEvent($world))->call();

        if($backgroundGeneration){
            Server::getInstance()->getLogger()->notice(
                Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_level_backgroundGeneration($name))
            );

            $spawnLocation = $world->getSpawnLocation();
            $centerX = $spawnLocation->getFloorX() >> 4;
            $centerZ = $spawnLocation->getFloorZ() >> 4;

            $selected = iterator_to_array((new ChunkSelector())->selectChunks(8, $centerX, $centerZ));
            $done = 0;
            $total = count($selected);
            foreach($selected as $index){
                World::getXZ($index, $chunkX, $chunkZ);
                $world->orderChunkPopulation($chunkX, $chunkZ, null)->onCompletion(
                    static function() use ($world, &$done, $total) : void{
                        $oldProgress = (int) floor(($done / $total) * 100);
                        $newProgress = (int) floor((++$done / $total) * 100);
                        if(intdiv($oldProgress, 10) !== intdiv($newProgress, 10) || $done === $total || $done === 1){
                            $world->getLogger()->info("Generating spawn terrain chunks: $done / $total ($newProgress%)");
                        }
                    },
                    static function() : void{
                        //NOOP: we don't care if the world was unloaded
                    });
            }
        }

        return true;
    }

    public static function loadLevel(string $name) : bool{
        if(trim($name) === ''){
            throw new WorldException('Invalid empty world name');
        }
        if(Server::getInstance()->getWorldManager()->isWorldLoaded($name)){
            return true;
        }

        if(!self::isLevelGenerated($name)){
            Server::getInstance()->getLogger()->notice(Server::getInstance()->getLanguage()->translateString('pocketmine.level.notFound', [$name]));

            return false;
        }

        $path = self::$worldPath . $name . '/';

        $providers = Server::getInstance()->getWorldManager()->getProviderManager()->getMatchingProviders($path);
        if(count($providers) !== 1){
            return false;
        }
        $providerClass = array_shift($providers);

        $provider = $providerClass->fromPath($path);

        if($providerClass === null){
            Server::getInstance()->getLogger()->error(Server::getInstance()->getLanguage()->translateString('pocketmine.level.loadError', [$name, 'Cannot identify format of world']));

            return false;
        }

        $worldManager = Server::getInstance()->getWorldManager();

        if(!($provider instanceof WritableWorldProvider)){
            Server::getInstance()->getLogger()->notice("Upgrading world \"$name\" to new format. This may take a while.");

            $converter = new FormatConverter(
                $provider,
                $worldManager->getProviderManager()->getDefault(),
                Path::join(Server::getInstance()->getDataPath(), 'backups', 'worlds'), Server::getInstance()->getLogger());
            $provider = $converter->execute();

            Server::getInstance()->getLogger()->notice("Upgraded world \"$name\" to new format successfully. Backed up pre-conversion world at " . $converter->getBackupPath());
        }

        $level = new World(Server::getInstance(), $name, $provider, Server::getInstance()->getAsyncPool());

        $levels = (new ReflectionClass('pocketmine\world\WorldManager'))->getProperty('worlds');
        $levels->setAccessible(true);
        //$this->levels[$level->getId()] = $level;
        $data = $levels->getValue($worldManager);
        $data[$level->getId()] = $level;
        $levels->setValue($worldManager, $data);

        (new WorldLoadEvent($level))->call();

        return true;
    }

    public function onLoad() : void{
        GeneratorManager::getInstance()->addGenerator(EmptyGenerator::class, 'empty', fn() => null);
        self::$worldPath = getcwd() . '/worlds/';
    }

    public function onEnable() : void{
        $files = array_diff(scandir(self::$worldPath), ['.', '..']);
        foreach($files as $fileName){
            if(self::loadLevel($fileName)){
                $this->getLogger()->info('§e월드를 로드했습니다: ' . $fileName);
            }
        }
        echo getcwd();

        c::command('월드로드', '해당 월드를 로드합니다.', '/월드로드', [],
            function(CommandSender $sender, string $a, array $args) : void{
                if($sender instanceof Player){
                    if(!isset($args[0])){
                        SSSSUtils::message($sender, '/월드로드 [월드이름]');
                        return;
                    }
                    if(self::loadLevel($worldName = implode(' ', $args))){
                        SSSSUtils::message($sender, '월드를 로드했습니다: ' . $worldName);
                    }else{
                        SSSSUtils::message($sender, '월드를 로드할 수 없습니다: ' . $worldName);
                    }
                }
            },
            true);
        c::command('월드이동', '해당 월드로 이동합니다.', '/월드이동', [],
            function(CommandSender $sender, string $a, array $args) : void{
                if($sender instanceof Player){
                    if(!isset($args[0])){
                        $form = new SimpleForm('월드이동', '이동할 월드를 선택하세요.');
                        $worlds = Server::getInstance()->getWorldManager()->getWorlds();
                        $ws = [];
                        foreach($worlds as $world){
                            if($world instanceof World){
                                $ws[] = $world;
                            }
                        }
                        foreach($ws as $world){
                            $form->addButton(new Button($world->getFolderName()));
                        }
                        $form->setHandler(function($data) use ($sender, $ws){
                            if($data !== null){
                                $world = $ws[$data];
                                if($world instanceof World){
                                    $sender->teleport($world->getSafeSpawn());
                                }else{
                                    SSSSUtils::message($sender, '잘못된 실행입니다.');
                                }
                            }
                        });
                        $sender->sendForm($form);
                    }else{
                        $worldName = implode(' ', $args);
                        if(($world = Server::getInstance()->getWorldManager()->getWorldByName($worldName)) instanceof World){
                            $sender->teleport($world->getSafeSpawn());
                        }else{
                            SSSSUtils::message($sender, '월드를 찾을 수 없습니다.');
                        }
                    }
                }
            }, true);

        c::command('월드생성', '월드를 생성합니다.', '/월드생성', [],
            function(CommandSender $sender, string $a, array $b) : void{
                if($sender instanceof Player){
                    $form = new CustomForm('월드생성', function($data) use ($sender){
                        if($data !== null){
                            $generatorName = GeneratorManager::getInstance()->getGeneratorList()[$data[0]] ?? null;
                            if($generatorName !== null){
                                $worldName = $data[1];
                                if($data[1] !== ''){
                                    self::generateWorld(
                                        $worldName,
                                        WorldCreationOptions::create()
                                            ->setSeed(404)
                                            ->setDifficulty(1)
                                            ->setGeneratorClass(GeneratorManager::getInstance()->getGenerator($generatorName)?->getGeneratorClass())
                                    );
                                    SSSSUtils::message($sender, '월드를 생성했습니다: ' . $worldName);
                                }
                            }
                        }
                    });
                    $form->addElement(new DropDown('월드 생성자', GeneratorManager::getInstance()->getGeneratorList()));
                    $form->addElement(new Input('월드 이름', '월드 이름을 입력하세요.', ''));
                    $sender->sendForm($form);
                }
            }, true);
    }

}