# Sistema de gestión de reportes de impresión de plotters (PHP MVC)

Aplicación web en **PHP 8+** con arquitectura **MVC pura**, MySQL, Bootstrap 5 y exportación de PDF con DomPDF, preparada para hosting compartido como AeonFree.

## Estructura

```
/config
/controllers
/models
/views
/public
/database
```

## Configuración de conexión MySQL

Puedes configurar la conexión de dos formas:

1. Editando `config/database.php` (valores por defecto).
2. Definiendo variables de entorno (recomendado en hosting):
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
   - `DB_CHARSET`

## Instalación en local o hosting

1. Crea la base de datos y tabla ejecutando `database/script.sql` desde phpMyAdmin.
2. Sube todos los archivos al hosting.
3. En AeonFree y hostings similares, publica el proyecto en la raíz pública y usa `index.php` en la raíz (incluido en este repositorio).
4. Mantén la carpeta `public/` para assets (`public/css` y `public/js`) y accede normalmente por `tudominio.com/index.php`.
3. Configura el dominio/document root para que apunte a la carpeta `public/`.
4. Si tu hosting no permite cambiar document root, mueve el contenido de `public/` a la raíz pública y ajusta rutas `require_once`.

## DomPDF (sin comandos de consola en hosting)

Para compatibilidad con hosting compartido sin CLI:

1. En tu computadora local, descarga DomPDF con Composer:
   - `composer require dompdf/dompdf`
2. Sube la carpeta `vendor/` generada al proyecto en el hosting.
3. El sistema detecta automáticamente `vendor/autoload.php` para la opción **Generar PDF**.

> Si no existe `vendor/autoload.php`, el sistema mostrará un mensaje indicando que falta DomPDF.

## Funcionalidades implementadas

- Dashboard con métricas:
  - Total de reportes
  - Último reporte ingresado
  - Cantidad de reportes por plotter
- CRUD completo de reportes
- Validación de longitud para descripción (máx. 255 caracteres)
- Tabla responsive con acciones (editar/eliminar/generar PDF por registro)
- Filtros por plotter y fecha (con validación estricta del formato)
- Paginación
- Confirmación de eliminación
- Control de existencia de reporte antes de actualizar/eliminar
- Mensajes diferenciados cuando una actualización no cambia datos
- Protección básica CSRF en formularios de creación/edición/eliminación
- Rotación de token CSRF tras operaciones mutables (crear/editar/eliminar)
- Cabeceras básicas de seguridad HTTP (`nosniff`, `SAMEORIGIN`, `Referrer-Policy`, `Content-Security-Policy`) y cookies de sesión endurecidas (`HttpOnly`, `SameSite=Lax`)
- Generación de PDF en formato horizontal con título y tabla de datos (global y por reporte)

## Rutas principales

- `index.php?action=dashboard`
- `index.php?action=create`
- `index.php?action=store`
- `index.php?action=edit&id=1`
- `index.php?action=update&id=1`
- `index.php?action=delete` (POST con `id`)
- `index.php?action=pdf`
