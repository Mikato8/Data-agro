-- Sustituye el enlace del repositorio original por el repositorio VetDataHub del proyecto.
update resources
set url = 'https://github.com/Mikato8/VetDataHub',
    organization = 'VetDataHub · Mikato8',
    category = 'dataset'
where url = 'https://github.com/Vetdatahub/VetDataHub'
  and not exists (
      select 1 from resources where url = 'https://github.com/Mikato8/VetDataHub'
  );

delete from resources
where url = 'https://github.com/Vetdatahub/VetDataHub';
