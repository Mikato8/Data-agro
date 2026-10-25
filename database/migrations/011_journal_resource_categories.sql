alter table resources drop constraint if exists resources_category_check;
alter table resources add constraint resources_category_check
    check (category in ('dataset', 'api', 'repositorio', 'vigilancia', 'guia', 'ml', 'institucion', 'sistema', 'biblioteca', 'directorio', 'revista', 'plataforma'));
