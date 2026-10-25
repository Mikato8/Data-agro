-- El CRM ahora vive en la navegación principal, no en el directorio de recursos.
delete from resources where url = '/panel/entrar';
alter table resources drop constraint if exists resources_category_check;
alter table resources add constraint resources_category_check
    check (category in ('dataset', 'api', 'repositorio', 'vigilancia', 'guia', 'ml', 'institucion', 'biblioteca', 'directorio', 'revista', 'plataforma'));
