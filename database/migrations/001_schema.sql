-- Diagnóstico Veterinario · esquema inicial
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
-- Catálogos geográficos y biológicos
-- ---------------------------------------------------------------------------

create table if not exists states (
    code varchar(5) primary key,
    name text not null unique,
    region text not null
);

create table if not exists species (
    id smallint generated always as identity primary key,
    code text not null unique,
    name text not null unique,
    scientific_name text,
    emoji text,
    sort_order smallint not null default 100
);

create table if not exists breeds (
    id integer generated always as identity primary key,
    species_id smallint not null references species(id) on delete cascade,
    name text not null,
    unique (species_id, name)
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
    role text not null default 'veterinario'
        check (role in ('veterinario', 'editor', 'admin')),
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
-- Conocimiento clínico
-- ---------------------------------------------------------------------------

create table if not exists diseases (
    id integer generated always as identity primary key,
    slug text not null unique,
    name text not null,
    scientific_name text,
    category text not null,
    summary text not null,
    etiology text,
    diagnosis text,
    treatment text,
    prevention text,
    prognosis text,
    zoonotic boolean not null default false,
    urgency text not null default 'media'
        check (urgency in ('baja', 'media', 'alta', 'critica')),
    icd11_code text,
    references_text text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

drop trigger if exists diseases_updated_at on diseases;
create trigger diseases_updated_at before update on diseases
for each row execute function set_updated_at();

create index if not exists diseases_name_trgm_idx
    on diseases using gin (name gin_trgm_ops);
create index if not exists diseases_category_idx on diseases (category);

create table if not exists disease_species (
    disease_id integer not null references diseases(id) on delete cascade,
    species_id smallint not null references species(id) on delete cascade,
    primary key (disease_id, species_id)
);

create table if not exists clinical_signs (
    id integer generated always as identity primary key,
    slug text not null unique,
    name text not null,
    category text not null,
    description text,
    sort_order smallint not null default 100
);

create index if not exists clinical_signs_category_idx on clinical_signs (category);

create table if not exists disease_signs (
    disease_id integer not null references diseases(id) on delete cascade,
    sign_id integer not null references clinical_signs(id) on delete cascade,
    weight smallint not null default 3 check (weight between 1 and 5),
    is_key boolean not null default false,
    primary key (disease_id, sign_id)
);

create index if not exists disease_signs_sign_idx on disease_signs (sign_id);

-- ---------------------------------------------------------------------------
-- Casos clínicos
-- ---------------------------------------------------------------------------

create sequence if not exists case_folio_seq;

create table if not exists cases (
    id bigint generated always as identity primary key,
    public_id uuid not null default gen_random_uuid() unique,
    folio text not null unique,
    slug text not null unique,
    author_id bigint not null references users(id) on delete restrict,
    title text not null check (char_length(title) between 10 and 180),
    species_id smallint not null references species(id) on delete restrict,
    breed_id integer references breeds(id) on delete set null,
    sex text not null default 'desconocido'
        check (sex in ('macho', 'hembra', 'desconocido')),
    age_value integer check (age_value is null or age_value between 0 and 100),
    age_unit text check (age_unit is null or age_unit in ('dias', 'semanas', 'meses', 'anios')),
    weight_kg numeric(7,2) check (weight_kg is null or (weight_kg > 0 and weight_kg <= 5000)),
    state_code varchar(5) references states(code),
    setting text not null default 'clinica'
        check (setting in ('clinica', 'hospital', 'campo', 'laboratorio', 'otro')),
    chief_complaint text not null,
    anamnesis text not null,
    physical_exam text not null,
    diagnostics text not null default '',
    differentials text,
    final_diagnosis text not null default '',
    icd11_code text,
    treatment text not null default '',
    outcome text not null default 'desconocido'
        check (outcome in ('curado', 'en_tratamiento', 'secuela', 'fallecido', 'eutanasia', 'desconocido')),
    discussion text,
    references_text text,
    owner_consent boolean not null default false,
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
            coalesce(anamnesis, '') || ' ' ||
            coalesce(physical_exam, '') || ' ' ||
            coalesce(diagnostics, '') || ' ' ||
            coalesce(differentials, '') || ' ' ||
            coalesce(final_diagnosis, '') || ' ' ||
            coalesce(discussion, '') || ' ' ||
            coalesce(references_text, '')
        )
    ) stored,
    constraint cases_published_ready check (
        status <> 'publicado' or (
            owner_consent
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
create index if not exists cases_species_idx on cases (species_id);
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
        check (category in ('dataset', 'api', 'repositorio', 'vigilancia', 'guia', 'ml', 'institucion')),
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
