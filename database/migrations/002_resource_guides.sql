-- Fichas internas para que los recursos sean utilizables sin conocimientos técnicos.
-- Cada recurso conserva su enlace externo, pero el portal explica para qué sirve,
-- cómo empezar y qué requisitos tiene.

alter table resources add column if not exists slug text;
alter table resources add column if not exists what_for text;
alter table resources add column if not exists how_to jsonb not null default '[]'::jsonb;
alter table resources add column if not exists requirements text;
alter table resources add column if not exists guide text;
alter table resources add column if not exists action_label text;
alter table resources add column if not exists reviewed_at date;

create unique index if not exists resources_slug_unique
    on resources (slug) where slug is not null;

create index if not exists resources_what_for_idx
    on resources using gin (to_tsvector('spanish', coalesce(what_for, '') || ' ' || coalesce(guide, '')));
