# Verificación de cédula profesional

El registro puede contrastar la **cédula profesional** y el **nombre completo** contra el Registro Nacional de Profesionistas de la SEP usando [API Market](https://apimarket.mx/docs/api-mexico?activeTab=validar-cedula-profesional).

## Cómo funciona

1. El veterinario captura nombre, cédula, institución y estado.
2. Si `CEDULA_VALIDATION_ENABLED=true`, el portal llama a:

```text
POST https://apimarket.mx/api/sep/grupo/validar-cedula?cedula=1234567
Authorization: Bearer <CEDULA_API_TOKEN>
Accept: application/json
```

3. La API devuelve uno o más registros con `nombre`, `paterno`, `materno`, `titulo`, `desins` y `curp`.
4. El portal:

   - Compara los tokens del nombre capturado con el nombre del registro (sin títulos como Dr./Dra.).
   - Exige que el título contenga `VETERINAR`.
   - Da prioridad al registro cuyo `idCedula` coincide con la cédula consultada.
   - Marca la cuenta como `verificado` si nombre y título coinciden.
   - Guarda nombre, título e institución verificados, pero **no guarda el CURP**.
5. Si la cédula no existe, el nombre no coincide o el título no es veterinario, el registro se rechaza con un mensaje específico.

## Configuración

En `.env`:

```dotenv
CEDULA_VALIDATION_ENABLED=true
CEDULA_API_URL=https://apimarket.mx/api/sep/grupo/validar-cedula
CEDULA_API_TOKEN=tu-token-de-api-market
CEDULA_API_TIMEOUT=15
```

Sin `CEDULA_VALIDATION_ENABLED=true` o sin token, el registro sigue funcionando con **revisión manual** por un administrador. Esto es intencional para no bloquear el portal si el proveedor falla.

## Obtener el token

1. Crea una cuenta en [API Market](https://apimarket.mx).
2. Contrata o activa el permiso **Validar cédulas profesionales** (`use_validar_cedula`).
3. Copia la API key.
4. Configúrala en el `.env` de Hostinger.
5. Limpia la caché y prueba `/registro` con una cédula real de prueba.

Cada consulta consume crédito según la tarifa de API Market; no hay consultas gratis ilimitadas.

## Privacidad

La consulta envía **nombre y cédula** a API Market/SEP. El portal conserva:

- `license_verified_at`
- `license_verified_name`
- `license_verified_title`
- `license_verified_institution`
- `license_verification_source`

No conserva CURP ni la respuesta completa. El aviso de privacidad lo documenta en la sección de encargados.

Si el usuario cambia su nombre o cédula en el perfil, la verificación se invalida y la cuenta vuelve a `pendiente` para revisión.

## Prueba local con mock

El flujo se probó con un mock que devuelve:

```json
{
  "success": true,
  "data": [{
    "nombre": "ANA SOFIA",
    "paterno": "RAMIREZ",
    "materno": "LOPEZ",
    "titulo": "LICENCIATURA EN MEDICINA VETERINARIA Y ZOOTECNIA",
    "desins": "UNIVERSIDAD NACIONAL AUTONOMA DE MEXICO",
    "idCedula": "12345678"
  }]
}
```

Resultado: cuenta `verificado`, campos `license_verified_*` guardados y aviso de éxito. También se verificó que el usuario de prueba se elimina sin afectar datos reales.
