-- CRM básico de clínica veterinaria, aislado por propietario de la cuenta.
create table if not exists clinic_patients (
    id bigint generated always as identity primary key,
    owner_id bigint not null references users(id) on delete cascade,
    name text not null check (char_length(name) between 1 and 80),
    species text not null check (species in ('canino', 'felino', 'equino', 'bovino', 'porcino', 'ovino', 'caprino', 'ave', 'exotico', 'otro')),
    breed text,
    sex text check (sex in ('macho', 'hembra', 'desconocido')),
    birth_date date,
    weight_kg numeric(6,2) check (weight_kg is null or weight_kg > 0),
    tutor_name text not null,
    tutor_phone text,
    tutor_email citext,
    notes text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);
create index if not exists clinic_patients_owner_idx on clinic_patients (owner_id, name);
create index if not exists clinic_patients_tutor_idx on clinic_patients (owner_id, tutor_name);

drop trigger if exists clinic_patients_updated_at on clinic_patients;
create trigger clinic_patients_updated_at before update on clinic_patients
for each row execute function set_updated_at();

create table if not exists clinic_appointments (
    id bigint generated always as identity primary key,
    owner_id bigint not null references users(id) on delete cascade,
    patient_id bigint not null references clinic_patients(id) on delete cascade,
    starts_at timestamptz not null,
    duration_min integer not null default 30 check (duration_min between 15 and 240),
    reason text not null,
    status text not null default 'programada' check (status in ('programada', 'confirmada', 'atendida', 'cancelada')),
    notes text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);
create index if not exists clinic_appointments_owner_idx on clinic_appointments (owner_id, starts_at);
create index if not exists clinic_appointments_patient_idx on clinic_appointments (patient_id, starts_at);

drop trigger if exists clinic_appointments_updated_at on clinic_appointments;
create trigger clinic_appointments_updated_at before update on clinic_appointments
for each row execute function set_updated_at();

create table if not exists clinic_prescriptions (
    id bigint generated always as identity primary key,
    owner_id bigint not null references users(id) on delete cascade,
    patient_id bigint not null references clinic_patients(id) on delete cascade,
    appointment_id bigint references clinic_appointments(id) on delete set null,
    diagnosis text not null,
    medications text not null,
    instructions text,
    created_at timestamptz not null default now()
);
create index if not exists clinic_prescriptions_owner_idx on clinic_prescriptions (owner_id, created_at desc);
create index if not exists clinic_prescriptions_patient_idx on clinic_prescriptions (patient_id, created_at desc);

create table if not exists clinic_products (
    id bigint generated always as identity primary key,
    owner_id bigint not null references users(id) on delete cascade,
    sku text not null,
    name text not null,
    category text,
    price numeric(10,2) not null default 0 check (price >= 0),
    stock integer not null default 0 check (stock >= 0),
    min_stock integer not null default 0 check (min_stock >= 0),
    active boolean not null default true,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now(),
    unique (owner_id, sku)
);
create index if not exists clinic_products_owner_idx on clinic_products (owner_id, name);

drop trigger if exists clinic_products_updated_at on clinic_products;
create trigger clinic_products_updated_at before update on clinic_products
for each row execute function set_updated_at();

create table if not exists clinic_sales (
    id bigint generated always as identity primary key,
    owner_id bigint not null references users(id) on delete cascade,
    patient_id bigint references clinic_patients(id) on delete set null,
    total numeric(10,2) not null default 0 check (total >= 0),
    payment_method text not null default 'efectivo' check (payment_method in ('efectivo', 'tarjeta', 'transferencia', 'otro')),
    notes text,
    created_at timestamptz not null default now()
);
create index if not exists clinic_sales_owner_idx on clinic_sales (owner_id, created_at desc);

create table if not exists clinic_sale_items (
    id bigint generated always as identity primary key,
    sale_id bigint not null references clinic_sales(id) on delete cascade,
    product_id bigint references clinic_products(id) on delete set null,
    product_name text not null,
    quantity integer not null check (quantity > 0),
    unit_price numeric(10,2) not null check (unit_price >= 0),
    subtotal numeric(10,2) not null check (subtotal >= 0)
);
create index if not exists clinic_sale_items_sale_idx on clinic_sale_items (sale_id);

create or replace function clinic_create_sale(
    p_owner_id bigint,
    p_patient_id bigint,
    p_payment_method text,
    p_items jsonb
) returns bigint
language plpgsql
as $$
declare
    v_sale_id bigint;
    v_total numeric(10,2);
    v_count integer;
    v_expected integer;
begin
    if p_items is null or jsonb_typeof(p_items) <> 'array' or jsonb_array_length(p_items) = 0 then
        raise exception 'La venta no tiene productos.';
    end if;
    if p_payment_method not in ('efectivo', 'tarjeta', 'transferencia', 'otro') then
        raise exception 'Forma de pago no válida.';
    end if;
    if p_patient_id is not null and not exists (
        select 1 from clinic_patients where id = p_patient_id and owner_id = p_owner_id
    ) then
        raise exception 'El paciente no pertenece a esta clínica.';
    end if;

    select count(*), coalesce(sum(i.quantity * p.price), 0)
      into v_count, v_total
      from (
          select product_id, sum(quantity)::int as quantity
          from jsonb_to_recordset(p_items) as x(product_id bigint, quantity int)
          group by product_id
      ) i
      join clinic_products p on p.id = i.product_id and p.owner_id = p_owner_id and p.active
     where i.quantity > 0 and p.stock >= i.quantity;

    select count(distinct product_id) into v_expected
      from jsonb_to_recordset(p_items) as x(product_id bigint, quantity int)
     where quantity > 0;

    if v_count <> v_expected or v_expected = 0 then
        raise exception 'Uno o más productos no existen, están inactivos o no tienen existencias suficientes.';
    end if;

    insert into clinic_sales (owner_id, patient_id, total, payment_method)
    values (p_owner_id, p_patient_id, v_total, p_payment_method)
    returning id into v_sale_id;

    insert into clinic_sale_items (sale_id, product_id, product_name, quantity, unit_price, subtotal)
    select v_sale_id, p.id, p.name, i.quantity, p.price, i.quantity * p.price
      from (
          select product_id, sum(quantity)::int as quantity
          from jsonb_to_recordset(p_items) as x(product_id bigint, quantity int)
          group by product_id
      ) i
      join clinic_products p on p.id = i.product_id and p.owner_id = p_owner_id and p.active
     where i.quantity > 0;

    update clinic_products p
       set stock = p.stock - i.quantity
      from (
          select product_id, sum(quantity)::int as quantity
          from jsonb_to_recordset(p_items) as x(product_id bigint, quantity int)
          group by product_id
      ) i
     where p.id = i.product_id and p.owner_id = p_owner_id and i.quantity > 0;

    return v_sale_id;
end;
$$;
