# SPEC — Laravel Log Monitor

## 1. Objetivo

Construir un paquete Composer para Laravel llamado provisionalmente:

`luiscamp/laravel-log-monitor`

El paquete debe permitir visualizar, buscar, filtrar y administrar los archivos de log generados por Laravel desde una interfaz web moderna, segura y extensible.

Debe estar inspirado conceptualmente en paquetes como `rap2hpoutre/laravel-log-viewer`, pero la implementación debe ser nueva, modular y preparada para futuras integraciones con Filament, Horizon e inteligencia artificial.

No copiar código del paquete original salvo patrones genéricos propios del ecosistema Laravel.

---

# 2. Compatibilidad

El paquete debe soportar inicialmente:

- PHP >= 8.2
- Laravel 12
- Laravel 13
- Composer
- Monolog utilizado por Laravel
- Linux como entorno principal de producción

Debe utilizar estándares modernos:

- PSR-4
- strict types cuando corresponda
- dependency injection
- service container de Laravel
- configuración publicable
- vistas publicables
- tests automatizados

---

# 3. Instalación esperada

El usuario deberá poder instalar el paquete mediante:

```bash
composer require luiscamp/laravel-log-monitor
```

El paquete deberá registrar automáticamente su Service Provider mediante package discovery.

Deberá existir la posibilidad de publicar configuración:

```bash
php artisan vendor:publish \
    --provider="Luiscamp\LaravelLogMonitor\LaravelLogMonitorServiceProvider" \
    --tag="config"
```

Y opcionalmente las vistas:

```bash
php artisan vendor:publish \
    --provider="Luiscamp\LaravelLogMonitor\LaravelLogMonitorServiceProvider" \
    --tag="views"
```

---

# 4. Arquitectura

Crear una estructura similar a:

```text
src/
├── Contracts/
│   ├── LogReaderInterface.php
│   ├── LogParserInterface.php
│   └── LogRepositoryInterface.php
│
├── DTO/
│   ├── LogEntry.php
│   └── LogFile.php
│
├── Exceptions/
│
├── Http/
│   ├── Controllers/
│   │   ├── LogViewerController.php
│   │   ├── LogDownloadController.php
│   │   └── LogClearController.php
│   │
│   └── Middleware/
│       └── AuthorizeLogViewer.php
│
├── Services/
│   ├── LogFileService.php
│   ├── LogParserService.php
│   └── LogSearchService.php
│
├── Repositories/
│   └── FileLogRepository.php
│
├── Support/
│
└── LaravelLogMonitorServiceProvider.php

config/
└── log-monitor.php

resources/
└── views/
    ├── layout.blade.php
    ├── index.blade.php
    └── components/

routes/
└── web.php

tests/
├── Feature/
└── Unit/
```

No es obligatorio seguir los nombres exactamente si una estructura mejor resulta más limpia, pero debe respetarse la separación entre:

- lectura de archivos;
- parseo;
- búsqueda;
- presentación;
- autorización.

---

# 5. Configuración

Crear:

```php
config/log-monitor.php
```

Con opciones similares a:

```php
return [

    'enabled' => env('LOG_MONITOR_ENABLED', true),

    'path' => storage_path('logs'),

    'route' => [
        'prefix' => 'system/logs',

        'middleware' => [
            'web',
            'auth',
        ],
    ],

    'allowed_extensions' => [
        'log',
    ],

    'levels' => [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ],

    'pagination' => [
        'per_page' => 50,
    ],

    'allow_download' => true,

    'allow_clear' => false,

    'auto_refresh' => false,

    'auto_refresh_interval' => 10,

];
```

Toda funcionalidad sensible deberá poder activarse o desactivarse desde configuración.

---

# 6. Lectura de logs

El sistema deberá analizar por defecto:

```php
storage_path('logs')
```

Debe detectar automáticamente archivos como:

```text
laravel.log
laravel-2026-08-17.log
laravel-2026-08-16.log
worker.log
queue.log
horizon.log
```

No asumir que todos los archivos siguen el patrón `laravel-YYYY-MM-DD.log`.

Debe soportar cualquier archivo cuya extensión esté permitida en configuración.

---

# 7. Seguridad de archivos

Este punto es crítico.

Nunca permitir al navegador enviar directamente una ruta absoluta.

Por ejemplo, NO aceptar:

```text
?file=/etc/passwd
```

Trabajar siempre con identificadores o nombres sanitizados.

Resolver los archivos exclusivamente dentro del directorio permitido.

Implementar protección contra:

- directory traversal;
- `../`;
- rutas absolutas;
- symlinks maliciosos;
- acceso fuera de `storage/logs`;
- extensiones no autorizadas.

Antes de acceder a cualquier archivo verificar que su ruta real continúe perteneciendo al directorio configurado.

---

# 8. Parser de logs

Crear un parser capaz de convertir entradas de Laravel/Monolog en objetos estructurados.

Ejemplo de entrada:

```text
[2026-08-17 12:25:13] production.ERROR: Undefined variable $user
```

Convertir a algo conceptualmente equivalente a:

```php
LogEntry(
    timestamp: ...,
    environment: 'production',
    level: 'error',
    message: 'Undefined variable $user',
    context: [],
    stackTrace: null,
);
```

Debe soportar logs multilínea.

Ejemplo:

```text
[2026-08-17 12:25:13] production.ERROR: SQLSTATE...

Stack trace:
#0 ...
#1 ...
#2 ...
```

Todo el stack trace debe pertenecer a una única entrada.

No interpretar cada línea del stack trace como un nuevo log.

---

# 9. DTO LogEntry

Crear un objeto de datos fuertemente tipado.

Por ejemplo:

```php
final readonly class LogEntry
{
    public function __construct(
        public ?CarbonImmutable $timestamp,
        public ?string $environment,
        public string $level,
        public string $message,
        public ?array $context = null,
        public ?string $stackTrace = null,
    ) {
    }
}
```

Puede adaptarse la implementación, pero evitar devolver arrays arbitrarios por todo el sistema.

---

# 10. Listado de archivos

La interfaz deberá mostrar:

- nombre;
- tamaño;
- fecha de modificación;
- cantidad aproximada o exacta de entradas si es razonablemente eficiente.

Permitir seleccionar un archivo.

Por defecto seleccionar:

1. `laravel.log`, si existe;

o de lo contrario:

2. el archivo modificado más recientemente.

---

# 11. Vista principal

Crear una UI moderna y responsive.

Diseño aproximado:

```text
┌───────────────────────────────────────────────────────────────┐
│ Laravel Log Monitor                        production         │
├────────────────┬──────────────────────────────────────────────┤
│ ARCHIVOS       │ Buscar...                     Auto refresh   │
│                │                                              │
│ laravel.log    │ ERROR                     12:41:03           │
│ 2026-08-17     │ Undefined variable $user                     │
│ 2026-08-16     │ UserService.php:84                           │
│ queue.log      │                                              │
│ horizon.log    │ [Ver stack trace] [Copiar]                   │
│                │                                              │
├────────────────┴──────────────────────────────────────────────┤
│ ERROR 23 | WARNING 8 | INFO 125 | DEBUG 14                   │
└───────────────────────────────────────────────────────────────┘
```

No usar Bootstrap obligatoriamente.

Preferir una implementación visual ligera.

Puede usarse Tailwind si no obliga al proyecto consumidor a recompilar assets.

Idealmente evitar dependencias frontend externas en V1.

---

# 12. Colores por nivel

Diferenciar visualmente:

```text
EMERGENCY
ALERT
CRITICAL
ERROR
WARNING
NOTICE
INFO
DEBUG
```

La interfaz debe tener buena legibilidad tanto en modo claro como oscuro.

---

# 13. Filtros

Permitir filtrar por:

- nivel;
- texto;
- archivo;
- fecha si la información está disponible.

Ejemplo:

```text
[Todos]
[Error]
[Warning]
[Info]
[Debug]
```

La búsqueda deberá buscar al menos dentro de:

- mensaje;
- stack trace;
- contexto.

---

# 14. Búsqueda

Añadir campo:

```text
Buscar en logs...
```

Debe permitir consultas como:

```text
SQLSTATE
UserService
Undefined variable
production.ERROR
App\Models\User
```

Evitar cargar gigabytes completos en memoria.

Para V1 pueden establecerse límites configurables.

---

# 15. Logs grandes

Diseñar pensando en archivos grandes.

Nunca realizar directamente:

```php
file_get_contents($hugeFile);
```

sin algún mecanismo de límite.

Preferir estrategias como:

- lectura por bloques;
- iteradores;
- SplFileObject;
- lectura desde el final;
- límites configurables.

El sistema debe mantenerse razonablemente estable incluso con logs de cientos de MB.

---

# 16. Orden

Mostrar por defecto los logs más recientes primero.

Proporcionar opción:

```text
Más recientes
Más antiguos
```

---

# 17. Paginación

Implementar paginación.

Valor inicial:

```text
50 entradas
```

Opciones:

```text
25
50
100
250
```

No renderizar miles de entradas simultáneamente.

---

# 18. Stack traces

Los stack traces deben mostrarse colapsados inicialmente.

Ejemplo:

```text
RuntimeException

UserService.php:84

[Ver stack trace]
```

Al expandir:

```text
#0 app/Services/UserService.php(84)
#1 app/Http/Controllers/UserController.php(32)
...
```

Añadir botón:

```text
Copiar stack trace
```

---

# 19. Copiar error

Añadir una acción que copie algo equivalente a:

```text
[2026-08-17 12:41:03]
production.ERROR

Undefined variable $user

app/Services/UserService.php:84

Stack trace:
...
```

Esto será importante para pegar posteriormente el error en herramientas de IA.

---

# 20. Descargar log

Si:

```php
'allow_download' => true
```

mostrar:

```text
Descargar
```

La descarga debe respetar todas las validaciones de seguridad del archivo.

---

# 21. Limpiar log

Si:

```php
'allow_clear' => true
```

permitir vaciar el archivo.

Debe requerir método HTTP seguro:

```text
DELETE
```

o:

```text
POST
```

Nunca usar GET para operaciones destructivas.

Solicitar confirmación visual:

```text
¿Seguro que deseas vaciar este archivo?
```

No eliminar el archivo salvo que exista una opción específica para ello.

Preferir truncarlo:

```php
file_put_contents($file, '');
```

si es seguro.

---

# 22. CSRF

Toda acción destructiva debe utilizar protección CSRF de Laravel.

---

# 23. Autorización

La aplicación nunca debe exponer `/system/logs` públicamente por defecto.

Debe soportar middleware configurable.

Default:

```php
[
    'web',
    'auth',
]
```

Crear además una extensión para autorización mediante Gate.

Por ejemplo:

```php
Gate::define('viewLaravelLogs', function ($user) {
    return $user->is_admin;
});
```

Permitir configurar:

```php
'authorization_gate' => 'viewLaravelLogs',
```

---

# 24. Producción

El paquete debe funcionar en:

```text
APP_ENV=production
```

No asumir que solo se usa en local.

Precisamente por ello la seguridad debe ser prioritaria.

No mostrar valores sensibles del `.env`.

No leer otros archivos fuera del directorio de logs.

---

# 25. Auto refresh

Crear opción:

```php
'auto_refresh' => true
```

y:

```php
'auto_refresh_interval' => 10
```

La UI deberá poder actualizar las entradas.

No recargar toda la página si puede evitarse.

Puede implementarse mediante:

- fetch;
- endpoint JSON;
- polling.

No introducir websockets en V1.

---

# 26. API interna

Separar la recuperación de datos de la interfaz para preparar futuras integraciones.

Idealmente endpoints internos similares a:

```text
GET /system/logs
GET /system/logs/files
GET /system/logs/{file}
```

No es obligatorio exponerlos como API pública.

---

# 27. Estadísticas

Mostrar contadores del archivo actual:

```text
ERROR       23
WARNING      8
INFO       125
DEBUG       14
```

No recalcular todo el archivo continuamente si ello genera problemas de rendimiento.

Puede utilizar caching de corta duración.

---

# 28. Cache

Permitir cachear:

- listado de archivos;
- metadata;
- estadísticas.

No cachear permanentemente contenido del log.

La caché debe invalidarse cuando cambie:

```text
mtime
```

del archivo.

---

# 29. Artisan command

Crear comando:

```bash
php artisan log-monitor:status
```

Debe mostrar información similar a:

```text
Laravel Log Monitor

Path: /var/www/app/storage/logs
Files: 7
Total size: 126 MB
Latest log: laravel-2026-08-17.log
```

Opcionalmente:

```bash
php artisan log-monitor:clear
```

pero cualquier comando destructivo debe solicitar confirmación.

---

# 30. Service Provider

El ServiceProvider debe:

- cargar configuración;
- registrar bindings;
- registrar rutas;
- cargar vistas;
- registrar publishes;
- registrar comandos.

Ejemplo conceptual:

```php
$this->mergeConfigFrom(...);

$this->loadViewsFrom(...);

$this->loadRoutesFrom(...);

$this->publishes(...);
```

---

# 31. Package Discovery

Configurar correctamente:

```json
"extra": {
    "laravel": {
        "providers": [
            "Luiscamp\\LaravelLogMonitor\\LaravelLogMonitorServiceProvider"
        ]
    }
}
```

---

# 32. Tests

Crear pruebas automatizadas.

Mínimo:

### Unit tests

```text
LogParserTest
LogFileServiceTest
LogSearchServiceTest
```

### Feature tests

```text
LogViewerAccessTest
LogDownloadTest
LogClearTest
LogTraversalProtectionTest
```

Casos indispensables:

```text
../
../../etc/passwd
archivo inexistente
archivo fuera del directorio
extensión no permitida
usuario no autorizado
log multilínea
stack trace
archivo vacío
```

---

# 33. Calidad

Ejecutar como mínimo:

```bash
composer test
```

Configurar PHPUnit.

Opcionalmente usar:

```text
Laravel Pint
PHPStan
```

Si se añaden, configurarlos correctamente.

---

# 34. README

Crear README profesional con:

```text
Laravel Log Monitor
```

Debe incluir:

- descripción;
- screenshots placeholder;
- requisitos;
- instalación;
- publicación de configuración;
- rutas;
- autorización;
- configuración;
- seguridad;
- ejemplos;
- testing;
- roadmap.

---

# 35. Licencia

Publicar bajo licencia:

```text
MIT
```

Crear:

```text
LICENSE
```

No copiar documentación, código, nombres de clases o assets del paquete `rap2hpoutre/laravel-log-viewer`.

Puede mencionarse posteriormente como inspiración si corresponde.

---

# 36. V1 — alcance obligatorio

Implementar completamente:

- paquete Composer;
- Laravel 12/13;
- PHP 8.2+;
- Service Provider;
- configuración publicable;
- detección de archivos;
- selección de archivo;
- parser Laravel/Monolog;
- logs multilínea;
- stack traces;
- búsqueda;
- filtro por nivel;
- paginación;
- estadísticas;
- copiar error;
- copiar stack trace;
- descargar;
- limpiar log configurable;
- autorización;
- protección contra directory traversal;
- modo oscuro;
- auto refresh configurable;
- tests;
- README.

---

# 37. Fuera del alcance de V1

NO implementar todavía:

- Filament;
- Horizon;
- WebSockets;
- Redis obligatorio;
- base de datos;
- almacenamiento de logs en BD;
- OpenAI;
- Claude;
- Gemini;
- análisis de errores por IA;
- notificaciones;
- Slack;
- Telegram;
- Sentry;
- múltiples servidores.

Pero diseñar el código de forma que estas integraciones puedan agregarse posteriormente.

---

# 38. Roadmap

Preparar arquitectura para:

## V2

```text
Filament Plugin
Laravel Horizon integration
Dashboard
Gráficos
Timeline de errores
```

## V3

```text
AI Error Assistant
```

Ejemplo futuro:

```text
[ Analizar con IA ]
```

Enviar:

```text
exception
message
file
line
stack trace
framework version
php version
```

y recibir:

```text
causa probable
explicación
archivo involucrado
posible solución
riesgo
```

No implementar esto todavía.

---

# 39. Filosofía del proyecto

El paquete debe ser:

```text
simple
seguro
rápido
extensible
Laravel-native
```

Evitar overengineering.

No crear interfaces o abstracciones sin utilidad real.

Sin embargo, mantener desacoplada la lectura/parsing de logs de la interfaz web.

---

# 40. Entrega esperada del agente

No limitarse a generar una explicación.

Implementar realmente los archivos.

Antes de finalizar:

1. revisar estructura;
2. ejecutar Composer;
3. ejecutar tests;
4. corregir tests fallidos;
5. revisar seguridad de rutas;
6. revisar compatibilidad Laravel;
7. revisar formato;
8. mostrar resumen final.

Al terminar informar:

```text
Archivos creados
Arquitectura
Rutas
Configuración
Tests ejecutados
Resultado de tests
Pendientes
```

---

# 41. Primera tarea

Comienza creando la estructura completa del paquete.

Después implementa en este orden:

```text
1. composer.json
2. Service Provider
3. configuración
4. DTOs
5. LogFileService
6. LogParserService
7. repositorio
8. controladores
9. rutas
10. vistas
11. búsqueda y filtros
12. acciones descargar/limpiar
13. auto refresh
14. tests
15. README
```

No avances a la interfaz compleja hasta que la lectura y parseo de logs estén correctamente cubiertos por tests.

Prioriza robustez, seguridad y mantenibilidad sobre velocidad de implementación.