-- CRM agronómico básico, aislado por cuenta (owner_id).
create table if not exists farm_plots (
    id bigint generated always as identity primary key,
    owner_id bigint not null references users(id) on delete cascade,
    name text not null check (char_length(name) between 1 and 80),
    crop text not null,
    variety text,
    area_ha numeric(8,2) check (area_ha is null or area_ha > 0),
    municipality text,
    state_code varchar(5) references states(code),
    irrigation text not null default 'temporal'
        check (irrigation in ('temporal', 'gravedad', 'goteo', 'aspersion', 'microaspersion', 'otro')),
    soil text,
    producer_name text not null,
    producer_phone text,
    producer_email citext,
    notes text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);
create index if not exists farm_plots_owner_idx on farm_plots (owner_id, name);
create index if not exists farm_plots_producer_idx on farm_plots (owner_id, producer_name);

drop trigger if exists farm_plots_updated_at on farm_plots;
create trigger farm_plots_updated_at before update on farm_plots
for each row execute function set_updated_at();

create table if not exists farm_visits (
    id bigint generated always as identity primary key,
    owner_id bigint not null references users(id) on delete cascade,
    plot_id bigint not null references farm_plots(id) on delete cascade,
    starts_at timestamptz not null,
    duration_min integer not null default 60 check (duration_min between 15 and 480),
    reason text not null,
    status text not null default 'programada' check (status in ('programada', 'confirmada', 'realizada', 'cancelada')),
    notes text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);
create index if not exists farm_visits_owner_idx on farm_visits (owner_id, starts_at);
create index if not exists farm_visits_plot_idx on farm_visits (plot_id, starts_at);

drop trigger if exists farm_visits_updated_at on farm_visits;
create trigger farm_visits_updated_at before update on farm_visits
for each row execute function set_updated_at();

create table if not exists farm_recommendations (
    id bigint generated always as identity primary key,
    owner_id bigint not null references users(id) on delete cascade,
    plot_id bigint not null references farm_plots(id) on delete cascade,
    visit_id bigint references farm_visits(id) on delete set null,
    diagnosis text not null,
    inputs_prescribed text not null,
    instructions text,
    created_at timestamptz not null default now()
);
create index if not exists farm_recommendations_owner_idx on farm_recommendations (owner_id, created_at desc);
create index if not exists farm_recommendations_plot_idx on farm_recommendations (plot_id, created_at desc);

create table if not exists farm_inputs (
    id bigint generated always as identity primary key,
    owner_id bigint not null references users(id) on delete cascade,
    sku text not null,
    name text not null,
    category text not null default 'otro'
        check (category in ('fertilizante', 'plaguicida', 'semilla', 'herramienta', 'otro')),
    unit text not null default 'pieza' check (char_length(unit) between 1 and 20),
    price numeric(10,2) not null default 0 check (price >= 0),
    stock integer not null default 0 check (stock >= 0),
    min_stock integer not null default 0 check (min_stock >= 0),
    active boolean not null default true,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now(),
    unique (owner_id, sku)
);
create index if not exists farm_inputs_owner_idx on farm_inputs (owner_id, name);

drop trigger if exists farm_inputs_updated_at on farm_inputs;
create trigger farm_inputs_updated_at before update on farm_inputs
for each row execute function set_updated_at();

create table if not exists farm_sales (
    id bigint generated always as identity primary key,
    owner_id bigint not null references users(id) on delete cascade,
    plot_id bigint references farm_plots(id) on delete set null,
    total numeric(10,2) not null default 0 check (total >= 0),
    payment_method text not null default 'efectivo' check (payment_method in ('efectivo', 'tarjeta', 'transferencia', 'otro')),
    notes text,
    created_at timestamptz not null default now()
);
create index if not exists farm_sales_owner_idx on farm_sales (owner_id, created_at desc);

create table if not exists farm_sale_items (
    id bigint generated always as identity primary key,
    sale_id bigint not null references farm_sales(id) on delete cascade,
    input_id bigint references farm_inputs(id) on delete set null,
    input_name text not null,
    quantity integer not null check (quantity > 0),
    unit_price numeric(10,2) not null check (unit_price >= 0),
    subtotal numeric(10,2) not null check (subtotal >= 0)
);
create index if not exists farm_sale_items_sale_idx on farm_sale_items (sale_id);

create or replace function farm_create_sale(
    p_owner_id bigint,
    p_plot_id bigint,
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
        raise exception 'La venta no tiene insumos.';
    end if;
    if p_payment_method not in ('efectivo', 'tarjeta', 'transferencia', 'otro') then
        raise exception 'Forma de pago no válida.';
    end if;
    if p_plot_id is not null and not exists (
        select 1 from farm_plots where id = p_plot_id and owner_id = p_owner_id
    ) then
        raise exception 'La parcela no pertenece a esta cuenta.';
    end if;

    select count(*), coalesce(sum(i.quantity * p.price), 0)
      into v_count, v_total
      from (
          select input_id, sum(quantity)::int as quantity
          from jsonb_to_recordset(p_items) as x(input_id bigint, quantity int)
          group by input_id
      ) i
      join farm_inputs p on p.id = i.input_id and p.owner_id = p_owner_id and p.active
     where i.quantity > 0 and p.stock >= i.quantity;

    select count(distinct input_id) into v_expected
      from jsonb_to_recordset(p_items) as x(input_id bigint, quantity int)
     where quantity > 0;

    if v_count <> v_expected or v_expected = 0 then
        raise exception 'Uno o más insumos no existen, están inactivos o no tienen existencias suficientes.';
    end if;

    insert into farm_sales (owner_id, plot_id, total, payment_method)
    values (p_owner_id, p_plot_id, v_total, p_payment_method)
    returning id into v_sale_id;

    insert into farm_sale_items (sale_id, input_id, input_name, quantity, unit_price, subtotal)
    select v_sale_id, p.id, p.name, i.quantity, p.price, i.quantity * p.price
      from (
          select input_id, sum(quantity)::int as quantity
          from jsonb_to_recordset(p_items) as x(input_id bigint, quantity int)
          group by input_id
      ) i
      join farm_inputs p on p.id = i.input_id and p.owner_id = p_owner_id and p.active
     where i.quantity > 0;

    update farm_inputs p
       set stock = p.stock - i.quantity
      from (
          select input_id, sum(quantity)::int as quantity
          from jsonb_to_recordset(p_items) as x(input_id bigint, quantity int)
          group by input_id
      ) i
     where p.id = i.input_id and p.owner_id = p_owner_id and i.quantity > 0;

    return v_sale_id;
end;
$$;
