-- Datos devueltos por la verificación automática de cédula profesional.
alter table users
    add column if not exists license_verified_at timestamptz,
    add column if not exists license_verified_name text,
    add column if not exists license_verified_title text,
    add column if not exists license_verified_institution text,
    add column if not exists license_verification_source text;

create index if not exists users_license_verified_idx
    on users (license_verified_at)
    where license_verified_at is not null;
