# Blackhound — Backend API REST para Barbería

Proyecto de grado. Backend en **Laravel 11** con **PostgreSQL 17** para la gestión integral de
**Blackhound**, una barbería única: empleados, servicios, tipos de corte, clientes, productos,
citas, comisiones, pagos, recordatorios, calificaciones y reservas públicas con verificación.

## Tecnologías

- Laravel 11 (PHP 8.2+)
- PostgreSQL 17 (pivotes `pdo_pgsql`, `pgsql`)
- Laravel Sanctum (tokens de API)
- Colas `database` + `queue:work` para recordatorios

## Requisitos

- PHP 8.2 o superior con extensiones `pdo_pgsql` y `pgsql` habilitadas en `php.ini`
- Composer 2.x
- PostgreSQL 17 corriendo como servicio

## Puesta en marcha

```bash
# 1. Instalar dependencias
composer install

# 2. Crear la base de datos y el usuario (como superusuario postgres)
psql -U postgres -c "CREATE ROLE glamstock LOGIN PASSWORD 'glamstock_secret';"
psql -U postgres -c "CREATE DATABASE glamstock OWNER glamstock;"

# 3. Configurar el entorno
cp .env.example .env
#   Editar .env:
#   APP_NAME=Blackhound
#   DB_CONNECTION=pgsql
#   DB_HOST=127.0.0.1
#   DB_PORT=5432
#   DB_DATABASE=glamstock
#   DB_USERNAME=glamstock
#   DB_PASSWORD=glamstock_secret
#   QUEUE_CONNECTION=database
#   APP_LOCALE=es

# 4. Clave de la aplicación, tablas y datos demo
php artisan key:generate
php artisan migrate --seed

# 5. Enlazar el disco público (fotos de barberos e imágenes de tipos de corte)
php artisan storage:link

# 6. Arrancar el servidor de desarrollo
php artisan serve            # http://127.0.0.1:8000

# 7. Procesar la cola y el scheduler de recordatorios (en otras terminales)
php artisan queue:work
php artisan schedule:work   # ejecuta reservas:expirar cada minuto
```

> El control de solapamiento de citas usa una restricción `EXCLUDE` con GIST. La migración habilita
> automáticamente la extensión `btree_gist`.

## Tests automáticos (PHPUnit)

Los tests corren sobre PostgreSQL en el schema `testing` de la misma base de datos `glamstock`
(la restricción `EXCLUDE`/GIST no existe en SQLite) y usan `DatabaseTransactions`, así que no
borran ni alteran los datos del schema `public`.

```bash
# 1. Crear el schema testing y su tabla de migraciones (una sola vez)
psql -U glamstock -d glamstock -c "CREATE SCHEMA IF NOT EXISTS testing AUTHORIZATION glamstock;"
psql -U glamstock -d glamstock -c "CREATE TABLE IF NOT EXISTS testing.migrations (id serial PRIMARY KEY, migration varchar(255) NOT NULL, batch integer NOT NULL);"

# 2. Migrar el schema testing
#    bash:
DB_SEARCH_PATH='testing,public' php artisan migrate --force
#    PowerShell:
$env:DB_SEARCH_PATH = 'testing,public'; php artisan migrate --force

# 3. Ejecutar los tests
php artisan test
```

## Registro y autenticación

El registro público crea un **cliente final** (rol `cliente`) junto con su ficha en `clientes`.
Los usuarios `admin` y `trabajador` se crean desde el seeder o directamente en la base de datos.

```bash
# Registrar un cliente (devuelve token)
POST /api/v1/auth/register
{
  "nombre": "Ana Perez",
  "telefono": "71110001",
  "email": "ana@test.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}

# Iniciar sesión (token + usuario)
POST /api/v1/auth/login
{
  "email": "admin@blackhound.test",
  "password": "secret123"
}

# Cerrar sesión y ver el usuario actual (protegidos)
POST /api/v1/auth/logout
GET  /api/v1/me
```

Todas las rutas salvo las públicas (`register`, `login`, `/publico/*` y
`/calificaciones/publica/*`) exigen el header:

```
Authorization: Bearer <token>
```

## Endpoints (`/api/v1`)

| Método  | Endpoint                          | Descripción                                          |
|--------|-----------------------------------|------------------------------------------------------|
| POST   | `/auth/register`                  | Registro de cliente final (público)                  |
| POST   | `/auth/login`                     | Inicio de sesión (público)                           |
| POST   | `/auth/logout`                    | Cierre de sesión                                     |
| GET    | `/me`                             | Usuario autenticado (incluye `rol` y `rol_id`)       |
| GET    | `/roles`                          | Lista de roles (solo admin)                          |
| GET    | `/disponibilidad`                 | Empleados + turnos libres (`servicio_id`, `fecha`)   |
| CRUD   | `/empleados`                      | Empleados (+ `servicios`, `horarios`, `foto`)        |
| POST   | `/empleados/{id}/foto`            | Sube/reemplaza la foto del barbero (multipart)       |
| DELETE | `/empleados/{id}/foto`            | Elimina la foto del barbero                          |
| CRUD   | `/servicios`                      | Servicios (+ `empleados`, `productos`)               |
| CRUD   | `/tipos-corte`                    | Catálogo de tipos de corte (+ `imagen`)              |
| CRUD   | `/clientes`                       | Clientes                                             |
| CRUD   | `/productos`                      | Productos                                            |
| GET    | `/productos/stock-bajo`           | Productos con stock <= stock_minimo                  |
| CRUD   | `/proveedores`                    | Proveedores                                          |
| GET/POST/PATCH/DELETE | `/citas`            | Citas (`estado`, `completar`, `tipo_corte_id`)       |
| GET/POST | `/pagos`                          | Pagos de citas (`GET`, `POST`, `GET/{pago}`, `PATCH /estado`) |
| GET/POST | `/calificaciones`                | Calificaciones del panel (`cita_id`, `estrellas`, `comentario`) |
| GET    | `/calificaciones/publica/{token}` | Datos de la cita para calificar (público)            |
| POST   | `/calificaciones/publica/{token}` | Calificación enviada por el cliente (público)        |
| GET    | `/publico/catalogo`               | Catálogo completo de Blackhound (público)            |
| GET    | `/publico/tipos-corte`            | Tipos de corte activos (público)                     |
| GET    | `/publico/servicios`              | Servicios con promedio de estrellas (público)        |
| GET    | `/publico/barberos`               | Barberos con foto y promedio (público)               |
| GET    | `/publico/disponibilidad`         | Turnos libres para reservar sin login (público)      |
| POST   | `/publico/citas`                  | Reserva pública pendiente de verificación            |
| GET    | `/publico/citas/confirmar/{token}`| Confirma la reserva mediante enlace (público)        |
| GET    | `/mis-citas`                      | Citas del cliente autenticado                        |
| POST   | `/mis-citas/{cita}/cancelar`      | Cancela una cita propia (cliente autenticado)        |
| GET    | `/reportes/ventas`                | Ventas imputadas y cobradas por día y método                   |
| GET    | `/reportes/comisiones`            | Comisiones acumuladas por empleado (pendiente/pagado)          |
| GET    | `/reportes/servicios-mas-solicitados` | Ranking de servicios con % e ingresos                     |
| GET    | `/reportes/clientes-frecuentes`   | Clientes con más visitas, última visita y gasto acumulado      |
| GET    | `/reportes/ocupacion`             | Horas ocupadas vs. capacidad de los turnos por empleado        |
| GET    | `/reportes/stock`                 | Estado de inventario y movimientos del periodo                 |
| GET    | `/reportes/dashboard`             | KPIs: citas, ventas, comisiones, clientes e inventario         |

### Roles y permisos

Los roles viven en la tabla `roles` (`admin`, `trabajador`, `cliente`) y cada usuario apunta a uno
mediante `usuarios.rol_id`. El middleware `role` restringe las rutas por rol.

| Rol          | Acceso                                                                 |
|--------------|------------------------------------------------------------------------|
| `admin`      | Todo el sistema: gestión, reportes y catálogos                         |
| `trabajador` | Lectura, agenda, clientes, citas, pagos y calificaciones               |
| `cliente`    | Consulta y cancelación de sus propias citas (`/mis-citas`)             |

La escritura de empleados, servicios, tipos de corte, productos y proveedores, los reportes y
`GET /roles` requieren rol `admin`. Las rutas de agenda, pagos, clientes y calificaciones aceptan
`admin` y `trabajador`. El portal del cliente (`/mis-citas`) es exclusivo del rol `cliente`.

## Reglas de negocio de citas

`POST /api/v1/citas` valida, además del formato, tres reglas comprobadas contra la base de datos:

1. El empleado **está capacitado** para el servicio (`servicio_empleado`).
2. La fecha cae dentro de un **turno laboral** del empleado (`horarios_disponibilidad`).
3. El rango **no se solapa** con otra cita pendiente, pendiente_verificacion o confirmada del
   mismo empleado (restricción `EXCLUDE` GIST en `citas`).

`POST /citas/{id}/completar` genera la **comisión** del empleado (precio × % del servicio), descuenta
el **stock** de los productos del servicio registrando `movimientos_stock` y registra el **pago**
automático del total (tabla `pagos`). También genera el `calificacion_token` para la calificación
pública.

`PATCH /citas/{id}/estado` → `confirmada` programa un **recordatorio** 24 h antes (config
`recordatorio_horas_antes` en `config/blackhound.php`), procesado por `EnviarRecordatorioJob`
(WhatsApp simulado vía Log).

Los pagos se gestionan además manualmente (`POST /pagos` para una cita completada, `PATCH /pagos/{id}/estado`
para marcar pagado o reembolsado). Una cita solo admite un pago.

## Reserva pública y portal del cliente

El cliente final puede reservar sin iniciar sesión mediante un flujo de **doble opt-in**:

1. `POST /publico/citas` valida disponibilidad y crea la cita en estado `pendiente_verificacion`
   con un `verificacion_token` y una expiración de 30 minutos (`config/blackhound.php`).
2. El enlace de confirmación se registra en el **log** de Laravel y se devuelve en la respuesta
   para facilitar las pruebas.
3. `GET /publico/citas/confirmar/{token}` confirma la cita y programa su recordatorio.
4. `php artisan reservas:expirar` (programado cada minuto) cancela las reservas vencidas y libera
   el cupo. El límite por defecto es de **2 reservas activas por teléfono**.

El cliente registrado puede consultar y cancelar sus citas:

```bash
GET  /api/v1/mis-citas
POST /api/v1/mis-citas/{cita}/cancelar
```

La cancelación exige al menos 2 horas de anticipación (`cancelacion_horas_minimas`).

## Reportes

Todos los reportes son `GET /api/v1/reportes/*` y están protegidos con token. Aceptan como query:

| Parámetro     | Descripción                                              | Default        |
|---------------|----------------------------------------------------------|----------------|
| `desde`       | Fecha inicial (`Y-m-d`) del rango                        | 29 días atrás  |
| `hasta`       | Fecha final del rango                                    | hoy            |
| `empleado_id` | Filtra por empleado (reportes de ventas, comisiones, servicios, clientes, ocupación) | -   |
| `servicio_id` | Filtra por servicio (ventas, comisiones, servicios, clientes)                        | -   |

Criterios clave:

- **Ventas**: muestra *ambos* enfoques: `total_imputado` (citas completadas, precio del servicio o el pago
  registrado) y `total_cobrado` (pagos en estado `pagado` según `pagado_en`), con desglose por día y método.
- **Comisiones**: acumulado por empleado (`monto_total`, `pendiente`, `pagado`) de las citas completadas.
- **Ocupación**: horas de citas vigentes / horas de turno disponibles por empleado en el rango.
- **Stock**: existencia, productos sin stock/bajo stock y entradas/salidas del periodo.

Ejemplos:

```
GET /api/v1/reportes/ventas?desde=2026-09-01&hasta=2026-09-30
GET /api/v1/reportes/dashboard
GET /api/v1/reportes/ocupacion?empleado_id=6
```

## Catálogo público y calificaciones

El cliente final puede consultar el catálogo de Blackhound **sin iniciar sesión**:

```
GET /api/v1/publico/catalogo
```

Devuelve `negocio`, `tipos_corte` activos (con `imagen`), `servicios` y `barberos` (con `foto`,
`calificacion_promedio` y `total_calificaciones`). El cliente selecciona un tipo de corte y un
servicio; el panel puede registrar la cita enviando `tipo_corte_id` en `POST /citas`.

Al completar una cita (`POST /citas/{id}/completar`) se genera un `calificacion_token` único y el
campo `enlace_calificacion` en la respuesta de la cita. Con ese enlace, el cliente califica la cita
sin login:

```
GET  /api/v1/calificaciones/publica/{token}
POST /api/v1/calificaciones/publica/{token}
{ "estrellas": 5, "comentario": "Excelente servicio" }
```

Reglas:

- Solo se califican citas en estado `completada`.
- Una cita admite **una sola** calificación, sin importar el origen (`panel` o `cliente`).
- `estrellas` es un entero entre 1 y 5.
- Los promedios (`calificacion_promedio`, `total_calificaciones`) se calculan al vuelo en
  `/empleados` y `/servicios` (y en el catálogo público); no se almacenan.

## Estructura de capas

```
app/
├── Http/
│   ├── Controllers/Api/V1/     # controladores delgados
│   ├── Requests/Api/V1/        # validación (Form Requests)
│   └── Resources/V1/           # formato JSON de respuesta
├── Models/                     # modelos Eloquent
├── Services/                   # lógica de negocio
│   ├── Auth/AuthService
│   ├── Calificaciones/CalificacionService
│   ├── Citas/CitaService, DisponibilidadService
│   ├── Stock/StockService
│   ├── Recordatorios/RecordatorioService
│   └── Reservas/ReservaPublicaService
├── Console/Commands/ExpirarReservasPendientes.php
└── Jobs/EnviarRecordatorioJob  # cola de recordatorios
```

La aplicación es **single-tenant**: no existe tabla `tenants` ni columnas `tenant_id`; todos los
datos pertenecen a Blackhound.

## Datos de prueba

El seeder (`php artisan migrate --seed`) crea los datos demo de Blackhound:

| Concepto         | Valor                        |
|------------------|------------------------------|
| Usuario admin    | `admin@blackhound.test` / `secret123`   |
| Usuario trabajador | `barbero@blackhound.test` / `secret123` |
| Usuario cliente  | `cliente@blackhound.test` / `secret123` |
| Roles            | 3 (`admin`, `trabajador`, `cliente`) |
| Negocio          | Blackhound                   |
| Barberos         | 7 (Marco Diaz = id 7)        |
| Servicios        | 4 (Corte fade premium = id 4)|
| Productos        | 5 (Shampoo profesional 1L = id 1) |
| Tipos de corte   | 4                            |

La carpeta `docs/ejemplos` contiene peticiones curl (`.bat` + cuerpos `.json`) usadas en las
capturas de la documentación (`docs/Blackhound_Documentacion.docx`).

## Código de estilo

```bash
vendor/bin/pint
```
