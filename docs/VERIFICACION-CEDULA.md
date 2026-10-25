# Verificación de cédula profesional

AgroDiagnóstico puede contrastar la **cédula profesional** y el **nombre completo** de una cuenta contra el Registro Nacional de Profesionistas de la SEP usando la API de [API Market](https://apimarket.mx/docs/api-mexico?activeTab=validar-cedula-profesional). La verificación es opcional: si no está configurada, el registro sigue un flujo manual.

> **Alcance.** La verificación acredita que existe una cédula con un nombre y un título compatibles con la agronomía. No sustituye la revisión de documentos, no comprueba la vigencia de la cédula ni la calidad del ejercicio profesional, y no es un requisito legal para publicar contenido en el portal.

## 1. Qué valida y qué no

**Valida**

- Que la cédula exista en el Registro Nacional de Profesionistas.
- Que el nombre capturado coincida con el nombre registrado, ignorando títulos como «Ing.» o «Dr.» y, si `intl` está disponible, tildes y mayúsculas.
- Que el título contenga alguna de las palabras clave que el código considera agronómicas.
- Que la cédula no esté ya registrada por otra cuenta.

**No valida**

- La vigencia, suspensión o inhabilitación de la cédula.
- La institución, el plan de estudios o la calidad del programa.
- Que la persona sea la titular legítima de la cédula más allá de la coincidencia de nombre.
- La identidad oficial con documento de identidad.
- La especialidad real del agrónomo.

## 2. Flujo de registro

1. La persona captura nombre completo, correo, cédula, institución, estado, semblanza y contraseña en `/registro`.
2. El controlador normaliza el correo y valida los campos. La cédula debe tener entre 4 y 40 caracteres en el formulario; la verificación automática exige además entre 6 y 12 dígitos.
3. Se comprueba que el correo y la cédula no existan en otra cuenta.
4. Si `CEDULA_VALIDATION_ENABLED=false`, la cuenta se crea con estado `pendiente` y un administrador la revisa en `/admin/usuarios`.
5. Si `CEDULA_VALIDATION_ENABLED=true` y la API está configurada, `App\Services\CedulaVerifier` consulta el registro antes de crear la cuenta.
6. Si la cédula no existe, el nombre no coincide o el título no es agronómico, **no se crea la cuenta** y se muestra un mensaje específico.
7. Si la verificación es exitosa, la cuenta se crea con estado `verificado`, se guardan los campos `license_verified_*` y se inicia sesión.
8. Si la cuenta se crea en modo manual, el administrador puede verificarla después desde `/admin/usuarios`.

## 3. Llamada a API Market

El código hace una petición `POST` con cuerpo vacío y la cédula en la query string:

```http
POST https://apimarket.mx/api/sep/grupo/validar-cedula?cedula=12345678
Authorization: Bearer <CEDULA_API_TOKEN>
Accept: application/json
User-Agent: DiagnosticoAgrónomo/1.0
```

Parámetros relevantes:

| Variable | Uso |
| --- | --- |
| `CEDULA_VALIDATION_ENABLED` | Activa o desactiva la verificación automática. |
| `CEDULA_API_URL` | URL base del proveedor; por defecto `https://apimarket.mx/api/sep/grupo/validar-cedula`. |
| `CEDULA_API_TOKEN` | Token Bearer del permiso contratado. Nunca se guarda en la base ni se muestra en la interfaz. |
| `CEDULA_API_TIMEOUT` | Tiempo total máximo de la petición; por defecto 15 segundos. |

El cliente usa 6 segundos de conexión, no sigue redirecciones y valida TLS. La cédula se limpia con `preg_replace('/\D+/', '', ...)`: se descartan guiones, espacios y letras. Debe quedar entre 6 y 12 dígitos; si no, se rechaza antes de llamar al proveedor.

La respuesta esperada es:

```json
{
  "success": true,
  "data": [
    {
      "nombre": "ANA SOFIA",
      "paterno": "RAMIREZ",
      "materno": "LOPEZ",
      "titulo": "INGENIERÍA AGRONÓMICA",
      "desins": "UNIVERSIDAD AUTÓNOMA CHAPINGO",
      "idCedula": "12345678",
      "curp": null
    }
  ]
}
```

Ejemplo ficticio. Los campos que el portal lee son `nombre`, `paterno`, `materno`, `titulo`, `desins`, `idCedula` y `curp`; el CURP no se almacena.

## 4. Selección del registro y comparación del nombre

La API puede devolver varios registros. El verificador:

1. Convierte el nombre en tokens: minúsculas, transliteración a ASCII si `intl` está disponible, sustitución de caracteres no alfanuméricos por espacios, separación por espacios, eliminación de títulos (`dr`, `dra`, `doctor`, `doctora`, `ing`, `ingeniero`, `ingeniera`, `agronomo`, `agronoma`) y deduplicación.
2. Hace lo mismo con cada registro devuelto.
3. Calcula una puntuación por registro:

```text
+4 si el idCedula coincide con la cédula consultada (ignorando ceros a la izquierda)
+2 si todos los tokens del nombre capturado aparecen en el registro
+1 si el título parece agronómico
```

4. Selecciona el registro con mayor puntuación; en empate, el primero.
5. Exige que el registro seleccionado tenga coincidencia de nombre y título agronómico.

La coincidencia de nombre es **de subconjunto**: todos los tokens capturados deben aparecer en el registro, pero el registro puede tener tokens adicionales. Así, «Ana Sofía Ramírez» coincide con «ANA SOFIA RAMIREZ LOPEZ», pero «Ana Sofía Ramírez Gómez» no coincide si «Gómez» no está en el registro.

Consecuencias prácticas:

- La persona debe capturar su nombre legal completo, no un alias.
- Sin la extensión `intl`, los nombres con tildes pueden no normalizarse igual y producir falsos negativos. `intl` es parte de los requisitos de despliegue.
- El orden de los nombres no importa; el conjunto de tokens sí.
- El código no exige que `idCedula` coincida para aceptar; sólo lo prioriza. Si el proveedor devuelve un registro con nombre y título compatibles pero sin `idCedula`, puede ser seleccionado.

## 5. Validación del título

`CedulaVerifier::isAgronomyTitle()` convierte el título a mayúsculas y comprueba si contiene alguna de estas cadenas:

```text
AGRONOM
INGENIER
FITOTECN
AGRO
```

| Título | `agronomy` | Comentario |
| --- | --- | --- |
| `INGENIERÍA AGRONÓMICA` | `true` | Coincide por `INGENIER`, `AGRO` y `AGRONOM` tras normalizar tildes. |
| `INGENIERO AGRÓNOMO` | `true` | Coincide por `INGENIER` y por `AGRONOM` tras normalizar tildes. |
| `INGENIERÍA EN FITOTECNIA` | `true` | Coincide por `FITOTECN`. |
| `INGENIERÍA AGROINDUSTRIAL` | `true` | Coincide por `INGENIER` y `AGRO`. |
| `INGENIERÍA AGRÍCOLA` | `true` | Coincide por `INGENIER`; pasa por la palabra «ingeniería», no por «agro». |
| `LICENCIATURA EN AGRICULTURA` | `false` | No contiene `AGRO`, `AGRONOM`, `FITOTECN` ni `INGENIER`; es un falso negativo posible. |
| `INGENIERÍA CIVIL` | `true` | Coincide por `INGENIER`; es un falso positivo posible. |

La comparación normaliza tildes antes de buscar las palabras clave, de modo que `AGRÓNOMO`, `AGRONOMÍA` y `AGRONOMO` se tratan igual. Aun así, muchos títulos pasan por `INGENIER` o por el prefijo `AGRO`, y un título con una redacción distinta puede quedar fuera.

Por eso la comprobación es una **heurística de palabras clave**, no un catálogo cerrado de planes de estudio. Un título de otra ingeniería puede pasar si contiene `INGENIER`, y un título agronómico con una redacción distinta puede quedar fuera. El comité editorial debe revisar el campo `license_verified_title` cuando el resultado sea dudoso; el panel de usuarios muestra la información de la cuenta.

## 6. Resultado, estado y campos guardados

El verificador devuelve:

```text
found       ¿se encontró al menos un registro?
name_match  ¿coincide el nombre del registro seleccionado?
agronomy    ¿el título del registro seleccionado parece agronómico?
name        nombre completo del registro
title       título del registro
institution institución (`desins`)
curp        CURP recibido, no se guarda
source      "apimarket"
```

En el registro se guardan:

| Campo | Origen |
| --- | --- |
| `license_number` | Cédula capturada por la persona. |
| `license_verified_at` | Fecha y hora de la verificación exitosa. |
| `license_verified_name` | Nombre del registro seleccionado. |
| `license_verified_title` | Título del registro seleccionado. |
| `license_verified_institution` | Institución del registro seleccionado. |
| `license_verification_source` | `apimarket`. |
| `status` | `verificado` si la verificación fue exitosa; `pendiente` en modo manual. |

No se guarda el CURP, la respuesta completa de la API ni el token. La cédula no se muestra públicamente; el perfil propio y el panel de administración la usan para la revisión.

Si la verificación automática está activa y falla por cédula inexistente, nombre distinto o título no agronómico, el registro se rechaza con un mensaje específico y no se crea la cuenta. Si el proveedor falla por red, credenciales o límite de consultas, también se rechaza el registro en ese intento; no hay reintento automático.

## 7. Activación

### 7.1 Contratar el acceso

1. Crea una cuenta en [API Market](https://apimarket.mx).
2. Contrata o activa el permiso de **validación de cédulas profesionales**.
3. Copia el token del permiso.
4. Verifica la URL y el método en la documentación del proveedor.

### 7.2 Configurar el portal

En `.env`:

```dotenv
CEDULA_VALIDATION_ENABLED=true
CEDULA_API_URL=https://apimarket.mx/api/sep/grupo/validar-cedula
CEDULA_API_TOKEN=tu-token-de-api-market
CEDULA_API_TIMEOUT=15
```

Después:

```bash
php bin/check.php
```

El chequeo debe reportar `Verificación de cédula: automática (API Market)`. Si reporta `activada sin token`, el registro quedará bloqueado para todas las personas: corrige la configuración antes de abrir el sitio.

### 7.3 Probar

- Usa una cédula de prueba o un mock local; no publiques cédulas reales en capturas, tickets o repositorios.
- Prueba un caso exitoso, un nombre que no coincida, una cédula inexistente y un título no agronómico.
- Revisa `/admin/usuarios` para confirmar el estado y los campos `license_verified_*`.
- Si el proveedor devuelve 401/403, revisa el token; si devuelve 429, espera o revisa el plan.

## 8. Qué pasa si no hay token o la API no está disponible

| Configuración | Comportamiento |
| --- | --- |
| `CEDULA_VALIDATION_ENABLED=false` | Registro manual: la cuenta se crea en `pendiente` y un administrador la verifica. Es el modo de la demo. |
| `CEDULA_VALIDATION_ENABLED=true` y `CEDULA_API_TOKEN` vacío | El registro se **bloquea** con el aviso «La verificación automática de cédula no está disponible en este momento». No se crea la cuenta. |
| `CEDULA_VALIDATION_ENABLED=true` y `CEDULA_API_URL` vacía | Igual que el caso anterior: falta configuración. |
| Proveedor con 401/403 | Se rechaza el registro y se registra un warning; el mensaje pide reintentar o contactar. |
| Proveedor con 429 | Se rechaza el registro con aviso de límite de consultas. |
| Timeout o error de red | Se rechaza el registro con aviso de reintento. |
| `AUTO_VERIFY_USERS=true` | Todas las cuentas nuevas se marcan `verificado` sin consultar la API. **Sólo para desarrollo.** |

Si quieres que el portal siga aceptando registros mientras el proveedor está caído, la única palanca actual es poner `CEDULA_VALIDATION_ENABLED=false` y verificar manualmente. No hay *fallback* automático de automático a manual con la bandera encendida.

## 9. Límites conocidos

- **Sólo se ejecuta al registrar.** No hay revalidación periódica ni consulta al iniciar sesión.
- **La cédula debe tener 6 a 12 dígitos** para la API, aunque el formulario permita 4 a 40 caracteres. Una cédula de 4 o 5 dígitos sólo puede verificarse en modo manual.
- **El título se evalúa por palabras clave**, con los falsos positivos y negativos descritos en la sección 5.
- **La coincidencia de nombre depende de `intl`** y de que la persona escriba su nombre legal completo.
- **El proveedor puede limitar consultas** por plan o por minuto; no hay cola ni reintento.
- **La API puede devolver varios registros**; la selección por puntuación no es una decisión legal, sólo una ayuda.
- **No se guarda evidencia documental** de la consulta; sólo el resultado resumido.
- **El cambio de nombre o cédula en el perfil** borra los campos `license_verified_*` y devuelve la cuenta con rol `agronomo` a `pendiente` para una nueva revisión. Revisa el estado en `/admin/usuarios` después de un cambio.

## 10. Privacidad y buenas prácticas

### Qué se envía y qué se guarda

- La petición HTTP envía **sólo la cédula** al proveedor; el nombre completo se usa localmente para comparar la respuesta. El aviso de privacidad debe describir exactamente este flujo.
- El proveedor devuelve nombre, título, institución y, si existe, CURP. El portal **no almacena el CURP ni la respuesta completa**.
- Se guardan la cédula, los campos `license_verified_*`, la fuente y el estado de la cuenta.
- La cédula no se muestra en el perfil público ni en la ficha pública del caso; aparece en el panel editorial y en la hoja de recomendación que se entrega al productor.

### Recomendaciones

- Trata el token como un secreto: vive en `.env`, no en el repositorio, capturas ni mensajes.
- Rota el token si se compartió o si cambia el proveedor.
- No registres cédulas, nombres ni CURP en logs de aplicación; el código actual no lo hace en los mensajes de error.
- Usa una cédula de prueba o un mock en desarrollo; evita datos personales reales en pruebas y capturas.
- Informa en `/privacidad` quién es el responsable, para qué se usa la verificación, qué proveedor interviene y cómo ejercer derechos ARCO.
- Conserva la cédula sólo mientras la cuenta exista; al eliminarla, `anonymizeAndDelete()` la borra y anonimiza los campos verificados.
- Si el comité necesita revisar un caso dudoso, que sea una persona quien consulte el título y la institución, no sólo el resultado automático.
- No uses `AUTO_VERIFY_USERS=true` en producción.

## 11. Prueba local con un mock

Puedes apuntar `CEDULA_API_URL` a un endpoint local que devuelva la estructura de la API y probar el flujo sin consumir créditos ni usar datos reales. El mock debe aceptar `POST` con la cédula en la query y devolver `success` y `data`.

Respuesta mínima:

```json
{
  "success": true,
  "data": [
    {
      "nombre": "ANA SOFIA",
      "paterno": "RAMIREZ",
      "materno": "LOPEZ",
      "titulo": "INGENIERÍA AGRONÓMICA",
      "desins": "UNIVERSIDAD AUTÓNOMA CHAPINGO",
      "idCedula": "12345678"
    }
  ]
}
```

Configuración:

```dotenv
CEDULA_VALIDATION_ENABLED=true
CEDULA_API_URL=http://127.0.0.1:8099/validar-cedula
CEDULA_API_TOKEN=token-de-prueba
```

Prueba en `/registro` con el nombre «Ana Sofía Ramírez López» y la cédula `12345678`. Debes obtener una cuenta `verificado` y los campos `license_verified_*` llenos. Repite con un título `INGENIERÍA CIVIL` para observar el falso positivo de la heurística y con `LICENCIATURA EN AGRICULTURA` para observar el falso negativo.

## 12. Solución de problemas

| Mensaje o síntoma | Causa probable | Qué hacer |
| --- | --- | --- |
| «La verificación automática de cédula no está disponible» | `CEDULA_VALIDATION_ENABLED=true` sin URL o token | Completa `CEDULA_API_URL` y `CEDULA_API_TOKEN`, o desactiva la bandera para operar en manual. |
| «No encontramos esa cédula en el Registro Nacional de Profesionistas» | Cédula inexistente, mal capturada o respuesta vacía | Revisa el número; confirma con la persona; prueba en modo manual si el proveedor no devuelve datos. |
| «La cédula existe, pero el nombre no coincide» | Nombre incompleto, alias, tildes sin `intl` o tokens distintos | Pide el nombre legal completo; activa `intl`; revisa el orden y los apellidos. |
| «no corresponde a una cédula de ingeniería agronómica» | El título no contiene las palabras clave | Revisa el título devuelto; si es agronómico con otra redacción, verifica manualmente y ajusta la heurística si corresponde. |
| 401 o 403 | Token inválido, vencido o sin permiso | Rota o corrige el token; confirma el permiso contratado. |
| 429 | Límite de consultas | Espera; revisa el plan; evita pruebas repetidas. |
| Timeout | Red del hosting o del proveedor | Reintenta; revisa `CEDULA_API_TIMEOUT`; considera verificación manual. |
| La cuenta quedó `verificado` sin campos `license_verified_*` | Datos antiguos o cambio de perfil | Revisa `/perfil` y `/admin/usuarios`; vuelve a verificar si hace falta. |
| Cambió el nombre o la cédula y sigue `verificado` | Limitación del reseteo automático | Cambia el estado manualmente en `/admin/usuarios` si corresponde. |

## 13. Checklist de activación

- [ ] Permiso de validación de cédulas contratado en API Market.
- [ ] Token copiado a `.env`, nunca al repositorio.
- [ ] `CEDULA_VALIDATION_ENABLED=true`.
- [ ] `CEDULA_API_URL` correcta y con HTTPS.
- [ ] `CEDULA_API_TIMEOUT` razonable (15 s por defecto).
- [ ] `intl` habilitado en PHP.
- [ ] `php bin/check.php` reporta verificación automática.
- [ ] Caso exitoso, nombre distinto, cédula inexistente y título no agronómico probados.
- [ ] `/admin/usuarios` muestra estado y campos verificados.
- [ ] Aviso de privacidad alineado con el flujo real.
- [ ] Plan de contingencia si el proveedor falla (desactivar y verificar manualmente).
- [ ] `AUTO_VERIFY_USERS=false` en producción.

## 14. Enlaces relacionados

- [README del proyecto](../README.md)
- [Arquitectura](ARQUITECTURA.md)
- [Despliegue en Hostinger](DEPLOY-HOSTINGER.md)
- [Implementación del verificador](../app/Services/CedulaVerifier.php)
- [Registro de usuarios](../app/Controllers/AuthController.php)
- [Repositorio de usuarios](../app/Repositories/UserRepository.php)
- [Aviso de privacidad (vista)](../app/Views/pages/privacy.php)
