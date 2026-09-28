# Despliegue en SiteGround

## Zona horaria

Configurar la siguiente variable en el archivo `.env` de producción:

```dotenv
APP_TIMEZONE=America/La_Paz
```

Después de cada cambio de configuración o despliegue, regenerar la caché de Laravel:

```bash
php artisan optimize:clear
php artisan config:cache
```

No modificar manualmente timestamps históricos de la base de datos al aplicar esta configuración.
