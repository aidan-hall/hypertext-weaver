-- -*- sql-product: sqlite; -*-
PRAGMA foreign_keys = ON;

CREATE TABLE posts(
id integer not null,
created integer not null,
body string not null,
primary key(id, created)
);

-- CREATE VIEW posts_updated as
-- select id, max(created) as updated from posts group by id;

-- CREATE VIEW posts_created as
-- select id, min(created) as created from posts group by id;

CREATE TABLE tags(
id integer not null,
created integer not null,
tag string not null,
primary key (id, created, tag),
foreign key (id, created) references posts(id, created)
);
