# Sistema de gestión de reportes de impresión de plotters (PHP MVC)

Aplicación web en **PHP 8+** con arquitectura **MVC pura**, MySQL y Bootstrap 5, preparada para hosting compartido como AeonFree.

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
   - `DB_PORT` (opcional)
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
   - `DB_CHARSET`
   - `APP_DEBUG` (`1` para ver detalle técnico temporalmente)

## Instalación en local o hosting

1. Crea la base de datos y tabla ejecutando `database/script.sql` desde phpMyAdmin.
   - Si ya tenías una instalación previa, ejecuta además: `ALTER TABLE reportes ADD COLUMN cantidad_impreso INT NOT NULL DEFAULT 0 AFTER cantidad;`
   - El sistema también intenta autoagregar la columna `cantidad_impreso` al guardar por primera vez (si el usuario DB tiene permisos `ALTER`).
2. Sube todos los archivos al hosting.
3. En AeonFree y hostings similares, publica el proyecto en la raíz pública y usa `index.php` en la raíz (incluido en este repositorio).
4. Los assets ahora viven en la raíz (`css/` y `js/`) y puedes abrir el sistema con `tudominio.com/index.php`.


## Modo temporal sin base de datos

Actualmente el CRUD está configurado en **modo datos fijos/en sesión** para evitar errores 500 del hosting mientras se valida el sistema:

- No usa MySQL para listar/crear/editar/eliminar durante esta etapa.
- Carga registros demo iniciales automáticamente.
- Los cambios se guardan en `$_SESSION` (se reinician al cerrar sesión/expirar).

> Este modo es temporal para diagnóstico.
2. Sube todos los archivos al hosting.
3. En AeonFree y hostings similares, publica el proyecto en la raíz pública y usa `index.php` en la raíz (incluido en este repositorio).
4. Los assets ahora viven en la raíz (`css/` y `js/`) y puedes abrir el sistema con `tudominio.com/index.php`.
2. Sube todos los archivos al hosting.
3. En AeonFree y hostings similares, publica el proyecto en la raíz pública y usa `index.php` en la raíz (incluido en este repositorio).
4. Los assets ahora viven en la raíz (`css/` y `js/`) y puedes abrir el sistema con `tudominio.com/index.php`.
4. Mantén la carpeta `public/` para assets (`public/css` y `public/js`) y accede normalmente por `tudominio.com/index.php`.
3. Configura el dominio/document root para que apunte a la carpeta `public/`.
4. Si tu hosting no permite cambiar document root, mueve el contenido de `public/` a la raíz pública y ajusta rutas `require_once`.

## Funcionalidades implementadas

- Dashboard con métricas:
  - Total de reportes
  - Último reporte ingresado
  - Cantidad de reportes por plotter
- CRUD completo de reportes
- Validación de longitud para descripción (máx. 255 caracteres)
- Campo adicional `cantidad_impreso` en formularios, tabla
- Tabla responsive con acciones (editar/eliminar)
- Filtros por plotter y fecha (con validación estricta del formato)
- Paginación
- Confirmación de eliminación
- Control de existencia de reporte antes de actualizar/eliminar
- Mensajes diferenciados cuando una actualización no cambia datos
- Protección básica CSRF en formularios de creación/edición/eliminación
- Rotación de token CSRF tras operaciones mutables (crear/editar/eliminar)
- Cabeceras básicas de seguridad HTTP (`nosniff`, `SAMEORIGIN`, `Referrer-Policy`, `Content-Security-Policy`) y cookies de sesión endurecidas (`HttpOnly`, `SameSite=Lax`)

## Rutas principales

- `index.php?action=dashboard`
- `index.php?action=create`
- `index.php?action=store`
- `index.php?action=edit&id=1`
- `index.php?action=update&id=1`
- `index.php?action=delete` (POST con `id`)


## Nota AeonFree (raíz pública)

Este proyecto ya incluye `index.php` en la raíz para que funcione con `DirectoryIndex index.php index.html index.htm index2.html` sin cambiar DocumentRoot.


## Solución rápida error 500 al guardar

Si al registrar aparece HTTP 500, normalmente tu tabla `reportes` es antigua y no tiene el campo `cantidad_impreso`.

Ejecuta en phpMyAdmin:

```sql
ALTER TABLE reportes ADD COLUMN cantidad_impreso INT NOT NULL DEFAULT 0 AFTER cantidad;
```

Desde esta versión el sistema intenta agregar esa columna automáticamente al guardar, si el usuario MySQL tiene permisos `ALTER`.

## Solución rápida error 500 en blanco (pantalla vacía)

Si el hosting muestra HTTP 500 sin detalle:

1. Verifica credenciales y host de MySQL en `config/database.php` o variables `DB_*`.
2. Activa debug temporal en hosting con `APP_DEBUG=1` para ver el error técnico real.
3. Revisa el `error_log` del hosting (la app ahora registra excepciones críticas allí).
4. Confirma que estás ejecutando PHP 8+ y extensión `pdo_mysql` habilitada.


## Nota técnica

Si ves `SQLSTATE[HY093]: Invalid parameter number`, esta versión usa `bindValue` explícito en `create/update` para evitar desalineación de parámetros en algunos entornos de hosting compartido.
