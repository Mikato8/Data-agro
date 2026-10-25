-- Sustituye el enlace de VetAPI por el repositorio del proyecto.
update resources
set url = 'https://github.com/Mikato8/VetAPI_Laravel',
    organization = 'Mikato8 · VetAPI'
where url = 'https://github.com/NicolasOrrego/VetAPI_Laravel'
  and not exists (
      select 1 from resources where url = 'https://github.com/Mikato8/VetAPI_Laravel'
  );

delete from resources
where url = 'https://github.com/NicolasOrrego/VetAPI_Laravel';
