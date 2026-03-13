# Sistema de gestión de reportes de impresión de plotters (PHP MVC)

Aplicación web en **PHP 8+** con arquitectura **MVC pura**, MySQL, Bootstrap 5 y exportación de PDF con DomPDF, preparada para hosting compartido como AeonFree.

## Estructura

```
/config
/controllers
/models
/views
/css
/js
/public
/database
```

## Configuración de conexión MySQL

Puedes configurar la conexión de dos formas:

1. Editando `config/database.php` (valores por defecto).
2. Definiendo variables de entorno (recomendado en hosting):
   - `DB_HOST`
   - `DB_PORT`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
   - `DB_CHARSET`
   - `DATABASE_URL` (formato: `mysql://usuario:clave@host:3306/base?charset=utf8mb4`)


## Configuración por defecto (hosting actual)

Si no defines variables de entorno, el sistema usa por defecto estos datos:

- Host: `sql302.hstn.me`
- Puerto: `3306`
- Base de datos: `mseet_41369034_plotter_reportes`
- Usuario: `mseet_41369034`
- Clave: `4016508a8b`

Si en el futuro cambias de hosting, actualiza las variables de entorno para sobrescribir estos valores.
## Configuración local rápida

Si corres el proyecto en local sin variables de entorno, se usan estos valores por defecto:

- Host: `127.0.0.1`
- Puerto: `3306`
- Base de datos: `plotter_reportes`
- Usuario: `root`
- Clave: *(vacía)*

En hosting, define variables de entorno para evitar depender de estos valores.

## Instalación en local o hosting

1. Crea la base de datos y tabla ejecutando `database/script.sql` desde phpMyAdmin.
2. Si tu instalación es previa, valida que existan estas columnas en `reportes`:
   - `cantidad_impreso` (INT, default 0)
   - `porcentaje_impresion` (INT, default 0)
3. Sube los archivos del proyecto al hosting.
4. Publica el proyecto apuntando a la raíz donde está `index.php`.
5. Abre `tudominio.com/index.php?action=dashboard`.

## DomPDF (sin comandos de consola en hosting)

Para compatibilidad con hosting compartido sin CLI:

1. Opción recomendada (Composer en local):
   - `composer require dompdf/dompdf`
   - Sube la carpeta `vendor/` al proyecto en el hosting.
2. Opción manual (sin Composer):
   - Descarga DomPDF y sube la carpeta `dompdf/` a la raíz del proyecto.

El sistema detecta automáticamente estas rutas:
- `vendor/autoload.php`
- `dompdf/autoload.inc.php`
- `dompdf/vendor/autoload.php`

> Si no encuentra ninguna, mostrará un mensaje en el dashboard indicando cómo instalar DomPDF.



## Funcionalidades implementadas

- Dashboard con métricas:
  - Total de reportes
  - Último reporte ingresado
  - Cantidad de reportes por plotter
- CRUD completo de reportes
- Validación de longitud para descripción (máx. 255 caracteres)
- Campo adicional `cantidad_impreso` en formularios, tabla y PDF
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


## Nota AeonFree (raíz pública)

Este proyecto ya incluye `index.php` en la raíz para que funcione con `DirectoryIndex index.php index.html index.htm index2.html` sin cambiar DocumentRoot.


## Solución rápida error 500 al guardar

Si al registrar aparece HTTP 500, normalmente tu tabla `reportes` es antigua y no tiene el campo `cantidad_impreso`.

Ejecuta en phpMyAdmin:

```sql
ALTER TABLE reportes ADD COLUMN cantidad_impreso INT NOT NULL DEFAULT 0 AFTER cantidad;
```

Desde esta versión el sistema intenta agregar esa columna automáticamente al guardar, si el usuario MySQL tiene permisos `ALTER`.
