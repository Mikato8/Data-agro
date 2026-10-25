-- El directorio prioriza bibliotecas, repositorios y revistas de acceso abierto.
alter table resources drop constraint if exists resources_category_check;
alter table resources add constraint resources_category_check
    check (category in ('dataset', 'api', 'repositorio', 'vigilancia', 'guia', 'ml', 'institucion', 'sistema', 'biblioteca'));

delete from resources where url in (
    'https://github.com/Mikato8/VetDataHub',
    'https://huggingface.co/buckets/Mikato8/PetEVAL-bucket',
    'https://github.com/francisco-correa/pet-cloud',
    'https://www.animalgenome.org/QTLdb/',
    'https://www.shelteranimalscount.org/',
    'https://www.gob.mx/cenaprece'
);
