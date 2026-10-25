# Política de seguridad

## Versiones soportadas

La rama `main` recibe correcciones de seguridad. Si despliegas una copia, mantén el código y las dependencias del sistema actualizadas.

## Cómo reportar una vulnerabilidad

No abras un issue público para vulnerabilidades. Escribe a **seguridad@diagnosticoveterinario.mx** con:

- Descripción del problema y pasos para reproducirlo.
- Impacto estimado.
- Versión, navegador o entorno afectado.
- Si lo deseas, una propuesta de corrección.

Responderemos en un plazo razonable y coordinaremos la divulgación responsable. No realizamos pruebas de penetración sobre infraestructura de terceros sin autorización.

## Alcance

Se consideran relevantes, entre otros:

- Ejecución remota de código o inclusión de archivos.
- Inyección SQL, XSS almacenado o CSRF.
- Escalada de privilegios o acceso a casos no publicados.
- Exposición de datos personales o de credenciales.
- Upload de archivos maliciosos.
- Denegación de servicio derivada de la aplicación.

Quedan fuera de alcance los ataques de fuerza bruta masivos, el spam, la ingeniería social y las vulnerabilidades de proveedores externos (Hostinger, Neon) que deben reportarse a cada proveedor.

## Medidas implementadas

- Contraseñas con `password_hash` y rehash automático.
- CSRF en formularios y acciones `POST`.
- Cookies `HttpOnly`, `SameSite=Lax` y `Secure` en HTTPS.
- Límite de intentos de acceso.
- Consultas parametrizadas y escape de salida.
- Validación de MIME, tamaño y dimensiones de imágenes; re-codificación con GD.
- Bloqueo de ejecución de PHP en el directorio de uploads.
- CSP y cabeceras de seguridad.
- Autorización por rol y ownership.
- Registro de auditoría editorial.

## Buenas prácticas para despliegues

- `APP_DEBUG=false` y `APP_ENV=production`.
- `.env` fuera del dominio público y con permisos restrictivos.
- HTTPS obligatorio.
- Credenciales de base de datos rotadas y distintas por entorno.
- Copias de seguridad y monitoreo de Neon.
- Revisar los logs de `storage/logs` sin exponerlos públicamente.
