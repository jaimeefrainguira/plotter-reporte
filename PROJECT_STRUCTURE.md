# Estructura del proyecto (snapshot)

Este archivo documenta la estructura versionada en Git para facilitar la restauración completa del repositorio.

## Árbol de archivos versionados

```text
.github/workflows/deploy.yml
README.md
config/database.php
controllers/ReporteController.php
css/styles.css
database/script.sql
dompdf
index.php
js/app.js
models/Reporte.php
public/css/styles.css
public/index.php
public/js/app.js
views/dashboard.php
views/editar_reporte.php
views/formulario_reporte.php
```

## Nota

Para subir todo de nuevo a tu remoto:

1. Verifica rama: `git branch --show-current`
2. Verifica estado: `git status`
3. Sube rama: `git push origin <rama>`

Si tu remoto está vacío, este snapshot deja el proyecto listo para publicarse nuevamente.
