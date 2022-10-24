<?php

namespace ojy\generators;

use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Generator;

class EmptyGenerator extends Generator
{

    public function __construct(int $seed, string $preset)
    {
        parent::__construct($seed, $preset);
    }

    public function getName(): string
    {
        return 'empty';
    }

    public function getSettings(): array
    {
        return [];
    }

    public function getSpawn(): Vector3
    {
        return new Vector3(125, 65, 125);
    }

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void
    {
    }

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void
    {
    }
}