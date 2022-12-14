<?php

/*
 * Auto-generated by libasynql-fx
 * Created from mysql.sql
 */

declare(strict_types=1);

namespace ojy\InvSync;

use Generator;
use poggit\libasynql\DataConnector;
use SOFe\AwaitGenerator\Await;

final class Database{
    public function __construct(private DataConnector $conn){ }

    /**
     * <h4>Declared in:</h4>
     * - resources/mysql.sql:13
     *
     * @param string $name
     *
     * @return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<array<string, mixed>>>
     */
    public function invsyncGet(string $name,) : Generator{
        $this->conn->executeSelect("invsync.get", ["name" => $name,], yield Await::RESOLVE, yield Await::REJECT);
        return yield Await::ONCE;
    }

    /**
     * <h4>Declared in:</h4>
     * - resources/mysql.sql:29
     * @return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<array<string, mixed>>>
     */
    public function invsyncGetAll() : Generator{
        $this->conn->executeSelect("invsync.get_all", [], yield Await::RESOLVE, yield Await::REJECT);
        return yield Await::ONCE;
    }

    /**
     * <h4>Declared in:</h4>
     * - resources/mysql.sql:8
     * @return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, int>
     */
    public function invsyncInit() : Generator{
        $this->conn->executeChange("invsync.init", [], yield Await::RESOLVE, yield Await::REJECT);
        return yield Await::ONCE;
    }

    /**
     * <h4>Declared in:</h4>
     * - resources/mysql.sql:19
     *
     * @param string $name
     * @param string $data
     *
     * @return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, int>
     */
    public function invsyncSet(string $name, string $data,) : Generator{
        $this->conn->executeInsert("invsync.set", ["name" => $name, "data" => $data,], yield Await::RESOLVE, yield Await::REJECT);
        return yield Await::ONCE;
    }

    /**
     * <h4>Declared in:</h4>
     * - resources/mysql.sql:25
     *
     * @param string $name
     * @param string $data
     *
     * @return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, int>
     */
    public function invsyncUpdate(string $name, string $data,) : Generator{
        $this->conn->executeChange("invsync.update", ["name" => $name, "data" => $data,], yield Await::RESOLVE, yield Await::REJECT);
        return yield Await::ONCE;
    }
}
