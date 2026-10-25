-- Sustituye el enlace del dataset original por el bucket PetEVAL del proyecto.
update resources
set url = 'https://huggingface.co/buckets/Mikato8/PetEVAL-bucket',
    organization = 'PetEVAL · Mikato8',
    access = 'abierto',
    license = 'Apache-2.0'
where url = 'https://huggingface.co/datasets/SAVSNET/PetEVAL'
  and not exists (
      select 1 from resources where url = 'https://huggingface.co/buckets/Mikato8/PetEVAL-bucket'
  );

delete from resources
where url = 'https://huggingface.co/datasets/SAVSNET/PetEVAL';
