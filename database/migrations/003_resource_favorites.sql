-- Recursos guardados por cada agrónomo.
create table if not exists resource_favorites (
    user_id bigint not null references users(id) on delete cascade,
    resource_id integer not null references resources(id) on delete cascade,
    created_at timestamptz not null default now(),
    primary key (user_id, resource_id)
);

create index if not exists resource_favorites_user_idx
    on resource_favorites (user_id, created_at desc);

create index if not exists resource_favorites_resource_idx
    on resource_favorites (resource_id);
