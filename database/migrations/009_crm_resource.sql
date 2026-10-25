-- El CRM reemplaza a VetAPI en el directorio de recursos.
alter table resources drop constraint if exists resources_category_check;
alter table resources add constraint resources_category_check
    check (category in ('dataset', 'api', 'repositorio', 'vigilancia', 'guia', 'ml', 'institucion', 'sistema'));

delete from resources where url = 'https://github.com/Mikato8/VetAPI_Laravel';
