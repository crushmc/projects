-- #!mysql
-- # { invsync
-- #   { init
create TABLE IF NOT EXISTS invsync (
    name VARCHAR(30) NOT NULL PRIMARY KEY,
    data LONGTEXT NOT NULL
)
-- #   }

-- #   { get
-- #     :name string
select * from invsync where name = :name
-- #   }

-- #   { set
-- #     :name string
-- #     :data string
insert into invsync (name, data) values (:name, :data) ON DUPLICATE KEY update data = :data
-- #   }

-- #   { update
-- #     :name string
-- #     :data string
update invsync set data = :data where name = :name
-- #   }

-- #   { get_all
select * from invsync
-- #   }
-- # }