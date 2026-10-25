# Contribuir a AgroDiagnóstico

Gracias por querer mejorar el portal. Este proyecto prioriza claridad parcela, privacidad y una operación sencilla en hostings PHP.

## Antes de empezar

- Lee [README.md](README.md), [SECURITY.md](SECURITY.md) y [CONTENIDO-LICENCIA.md](CONTENIDO-LICENCIA.md).
- Para cambios técnicos, incluye referencias verificables y evita dosis que puedan interpretarse como prescripción.
- No incluyas datos de cultivos reales, produceres o instituciones identificables en issues, capturas o pruebas.

## Entorno local

```bash
cp .env.example .env
# Configura DATABASE_URL y DB_DRIVER=pdo
php bin/migrate.php
DEMO_PASSWORD='ClaveLocalSegura2026!' php bin/seed.php --demo
php bin/serve.php
```

## Estilo

- PHP 8.2+, `declare(strict_types=1)` y tipos cuando aporten claridad.
- SQL parametrizado con `$1`, `$2`, …; nunca concatenes entrada del usuario.
- Controladores delgados; consultas en `app/Repositories`.
- Vistas con escape `e()` para todo dato dinámico.
- Textos de interfaz en español de México, claros y sin jerga innecesaria.
- CSS con las variables de `public/assets/css/app.css`; evita estilos inline salvo valores calculados.
- JavaScript sin dependencias en `public/assets/js/app.js`.

## Migraciones

- Crea un archivo nuevo en `database/migrations` con prefijo numérico.
- No edites una migración ya aplicada en producción; agrega otra.
- Prueba la migración sobre una rama de Neon o una base local antes de aplicarla.
- Actualiza los seeds si agregas catálogos.

## Pruebas

```bash
find app config routes bin -name '*.php' -print0 | xargs -0 -n1 php -l
php bin/check.php
./tests/smoke.sh
```

Si tu cambio afecta el flujo editorial, prueba: registro, verificación, creación de borrador, envío a revisión, publicación y comentario.

## Pull requests

1. Describe el problema y la solución.
2. Incluye capturas si cambia la interfaz.
3. Indica cómo probaste el cambio.
4. Mantén el alcance acotado; separa refactors de funcionalidades.
5. Acepta que el contenido técnico puede requerir revisión editorial adicional.

## Código de conducta

Sé respetuoso, específico y generoso con la revisión. La diversidad de experiencias parcelas es una fortaleza del proyecto.
