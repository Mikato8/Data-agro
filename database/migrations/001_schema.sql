-- AgroDiagnóstico · esquema inicial
-- PostgreSQL 15+ (probado en Neon PostgreSQL 18)
-- Ejecutar con una conexión directa (sin -pooler).

create extension if not exists pg_trgm;
create extension if not exists citext;

create table if not exists schema_migrations (
    version text primary key,
    applied_at timestamptz not null default now()
);

create or replace function set_updated_at()
returns trigger
language plpgsql
as $$
begin
    new.updated_at = now();
    return new;
end;
$$;

-- ---------------------------------------------------------------------------
-- Catálogos geográficos y agronómicos
-- ---------------------------------------------------------------------------

create table if not exists states (
    code varchar(5) primary key,
    name text not null unique,
    region text not null
);

create table if not exists crops (
    id smallint generated always as identity primary key,
    code text not null unique,
    name text not null unique,
    scientific_name text,
    emoji text,
    family text,
    cycle text not null default 'anual'
        check (cycle in ('anual', 'perenne', 'bianual')),
    sort_order smallint not null default 100
);

create table if not exists varieties (
    id integer generated always as identity primary key,
    crop_id smallint not null references crops(id) on delete cascade,
    name text not null,
    unique (crop_id, name)
);

create table if not exists specialties (
    id smallint generated always as identity primary key,
    slug text not null unique,
    name text not null unique
);

-- ---------------------------------------------------------------------------
-- Cuentas y acceso
-- ---------------------------------------------------------------------------

create table if not exists users (
    id bigint generated always as identity primary key,
    public_id uuid not null default gen_random_uuid() unique,
    name text not null check (char_length(name) between 2 and 120),
    email citext not null unique check (char_length(email) <= 254),
    password_hash text not null,
    role text not null default 'agronomo'
        check (role in ('agronomo', 'editor', 'admin')),
    status text not null default 'pendiente'
        check (status in ('pendiente', 'verificado', 'suspendido')),
    license_number text unique,
    institution text,
    state_code varchar(5) references states(code),
    bio text check (bio is null or char_length(bio) <= 1200),
    avatar_url text,
    email_verified_at timestamptz,
    last_login_at timestamptz,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now(),
    deleted_at timestamptz
);

drop trigger if exists users_updated_at on users;
create trigger users_updated_at before update on users
for each row execute function set_updated_at();

create index if not exists users_role_status_idx on users (role, status);
create index if not exists users_state_idx on users (state_code);
create index if not exists users_created_idx on users (created_at desc);

create table if not exists login_attempts (
    id bigint generated always as identity primary key,
    email citext,
    ip inet,
    successful boolean not null default false,
    created_at timestamptz not null default now()
);

create index if not exists login_attempts_lookup_idx
    on login_attempts (email, ip, created_at desc);

create table if not exists password_resets (
    id bigint generated always as identity primary key,
    user_id bigint not null references users(id) on delete cascade,
    token_hash text not null unique,
    expires_at timestamptz not null,
    used_at timestamptz,
    created_at timestamptz not null default now()
);

create index if not exists password_resets_user_idx on password_resets (user_id);

-- ---------------------------------------------------------------------------
-- Conocimiento fitosanitario
-- ---------------------------------------------------------------------------

create table if not exists problems (
    id integer generated always as identity primary key,
    slug text not null unique,
    name text not null,
    scientific_name text,
    category text not null,
    summary text not null,
    etiology text,
    diagnosis text,
    management text,
    prevention text,
    prognosis text,
    reportable boolean not null default false,
    risk text not null default 'medio'
        check (risk in ('bajo', 'medio', 'alto', 'critico')),
    reference_code text,
    references_text text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

drop trigger if exists problems_updated_at on problems;
create trigger problems_updated_at before update on problems
for each row execute function set_updated_at();

create index if not exists problems_name_trgm_idx
    on problems using gin (name gin_trgm_ops);
create index if not exists problems_category_idx on problems (category);

create table if not exists problem_crops (
    problem_id integer not null references problems(id) on delete cascade,
    crop_id smallint not null references crops(id) on delete cascade,
    primary key (problem_id, crop_id)
);

create table if not exists symptoms (
    id integer generated always as identity primary key,
    slug text not null unique,
    name text not null,
    category text not null,
    description text,
    sort_order smallint not null default 100
);

create index if not exists symptoms_category_idx on symptoms (category);

create table if not exists problem_symptoms (
    problem_id integer not null references problems(id) on delete cascade,
    symptom_id integer not null references symptoms(id) on delete cascade,
    weight smallint not null default 3 check (weight between 1 and 5),
    is_key boolean not null default false,
    primary key (problem_id, symptom_id)
);

create index if not exists problem_symptoms_symptom_idx on problem_symptoms (symptom_id);

-- ---------------------------------------------------------------------------
-- Casos agronómicos
-- ---------------------------------------------------------------------------

create sequence if not exists case_folio_seq;

create table if not exists cases (
    id bigint generated always as identity primary key,
    public_id uuid not null default gen_random_uuid() unique,
    folio text not null unique,
    slug text not null unique,
    author_id bigint not null references users(id) on delete restrict,
    title text not null check (char_length(title) between 10 and 180),
    crop_id smallint not null references crops(id) on delete restrict,
    variety_id integer references varieties(id) on delete set null,
    cycle text not null default 'no_aplica'
        check (cycle in ('primavera-verano', 'otono-invierno', 'perenne', 'continuo', 'no_aplica')),
    crop_stage text not null default 'no_aplica'
        check (crop_stage in ('siembra', 'emergencia', 'vegetativo', 'floracion', 'fructificacion', 'llenado', 'madurez', 'cosecha', 'postcosecha', 'no_aplica')),
    area_ha numeric(10,2) check (area_ha is null or (area_ha > 0 and area_ha <= 100000)),
    incidence_pct smallint check (incidence_pct is null or (incidence_pct between 0 and 100)),
    state_code varchar(5) references states(code),
    municipality text check (municipality is null or char_length(municipality) <= 120),
    system text not null default 'campo_abierto'
        check (system in ('campo_abierto', 'invernadero', 'macrotunel', 'hidroponia', 'vivero', 'otro')),
    chief_complaint text not null,
    crop_history text not null,
    field_inspection text not null,
    lab_tests text not null default '',
    differentials text,
    final_diagnosis text not null default '',
    reference_code text,
    management text not null default '',
    outcome text not null default 'desconocido'
        check (outcome in ('recuperado', 'en_manejo', 'perdida_parcial', 'perdida_total', 'en_observacion', 'desconocido')),
    discussion text,
    references_text text,
    producer_consent boolean not null default false,
    anonymized boolean not null default false,
    consent_at timestamptz,
    license text not null default 'CC BY-NC-SA 4.0'
        check (license in ('CC BY-NC-SA 4.0', 'CC BY 4.0', 'CC BY-ND 4.0', 'Todos los derechos reservados')),
    status text not null default 'borrador'
        check (status in ('borrador', 'en_revision', 'cambios_solicitados', 'publicado', 'rechazado', 'archivado')),
    is_demo boolean not null default false,
    featured boolean not null default false,
    views integer not null default 0 check (views >= 0),
    published_at timestamptz,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now(),
    deleted_at timestamptz,
    search_vector tsvector generated always as (
        to_tsvector(
            'spanish',
            coalesce(title, '') || ' ' ||
            coalesce(chief_complaint, '') || ' ' ||
            coalesce(crop_history, '') || ' ' ||
            coalesce(field_inspection, '') || ' ' ||
            coalesce(lab_tests, '') || ' ' ||
            coalesce(differentials, '') || ' ' ||
            coalesce(final_diagnosis, '') || ' ' ||
            coalesce(discussion, '') || ' ' ||
            coalesce(references_text, '')
        )
    ) stored,
    constraint cases_published_ready check (
        status <> 'publicado' or (
            producer_consent
            and anonymized
            and char_length(final_diagnosis) >= 3
            and published_at is not null
        )
    )
);

drop trigger if exists cases_updated_at on cases;
create trigger cases_updated_at before update on cases
for each row execute function set_updated_at();

create index if not exists cases_public_idx
    on cases (status, published_at desc) where deleted_at is null;
create index if not exists cases_crop_idx on cases (crop_id);
create index if not exists cases_state_idx on cases (state_code);
create index if not exists cases_author_idx on cases (author_id);
create index if not exists cases_status_idx on cases (status);
create index if not exists cases_search_idx on cases using gin (search_vector);
create index if not exists cases_title_trgm_idx
    on cases using gin (title gin_trgm_ops);
create index if not exists cases_diagnosis_trgm_idx
    on cases using gin (final_diagnosis gin_trgm_ops);
create index if not exists cases_featured_idx
    on cases (featured, published_at desc) where status = 'publicado' and deleted_at is null;

create table if not exists case_images (
    id bigint generated always as identity primary key,
    case_id bigint not null references cases(id) on delete cascade,
    path text not null,
    caption text,
    alt_text text,
    mime_type text,
    size_bytes integer,
    width integer,
    height integer,
    sort_order smallint not null default 0,
    created_at timestamptz not null default now()
);

create index if not exists case_images_case_idx on case_images (case_id, sort_order);

create table if not exists case_tags (
    case_id bigint not null references cases(id) on delete cascade,
    tag text not null check (char_length(tag) between 2 and 40),
    primary key (case_id, tag)
);

create index if not exists case_tags_tag_idx on case_tags (tag);

create table if not exists case_comments (
    id bigint generated always as identity primary key,
    case_id bigint not null references cases(id) on delete cascade,
    user_id bigint not null references users(id) on delete cascade,
    body text not null check (char_length(body) between 3 and 3000),
    status text not null default 'visible' check (status in ('visible', 'oculto')),
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

drop trigger if exists case_comments_updated_at on case_comments;
create trigger case_comments_updated_at before update on case_comments
for each row execute function set_updated_at();

create index if not exists case_comments_case_idx
    on case_comments (case_id, created_at) where status = 'visible';

create table if not exists case_reviews (
    id bigint generated always as identity primary key,
    case_id bigint not null references cases(id) on delete cascade,
    reviewer_id bigint not null references users(id) on delete restrict,
    decision text not null check (decision in ('aprobado', 'rechazado', 'cambios')),
    comments text,
    created_at timestamptz not null default now()
);

create index if not exists case_reviews_case_idx on case_reviews (case_id, created_at desc);

create table if not exists case_reactions (
    case_id bigint not null references cases(id) on delete cascade,
    user_id bigint not null references users(id) on delete cascade,
    created_at timestamptz not null default now(),
    primary key (case_id, user_id)
);

-- ---------------------------------------------------------------------------
-- Recursos externos y auditoría
-- ---------------------------------------------------------------------------

create table if not exists resources (
    id integer generated always as identity primary key,
    title text not null,
    description text not null,
    url text not null unique,
    category text not null
        check (category in ('dataset', 'api', 'repositorio', 'vigilancia', 'guia', 'ml', 'institucion', 'biblioteca', 'directorio', 'revista', 'plataforma')),
    organization text,
    country text,
    access text not null default 'abierto'
        check (access in ('abierto', 'solicitud', 'pago', 'mixto')),
    license text,
    language text default 'es',
    tags text[] not null default '{}',
    featured boolean not null default false,
    sort_order smallint not null default 100,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

drop trigger if exists resources_updated_at on resources;
create trigger resources_updated_at before update on resources
for each row execute function set_updated_at();

create index if not exists resources_category_idx on resources (category, sort_order);
create index if not exists resources_tags_idx on resources using gin (tags);

create table if not exists audit_log (
    id bigint generated always as identity primary key,
    user_id bigint references users(id) on delete set null,
    action text not null,
    entity_type text,
    entity_id text,
    meta jsonb not null default '{}'::jsonb,
    ip inet,
    created_at timestamptz not null default now()
);

create index if not exists audit_log_user_idx on audit_log (user_id, created_at desc);
create index if not exists audit_log_entity_idx on audit_log (entity_type, entity_id);
