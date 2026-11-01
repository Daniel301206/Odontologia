# Sonrisa Perfecta

Sistema PHP para el esquema MySQL `sonrisa_perfecta`. Las tablas definidas en `database/odontologia.sql` determinan los campos y módulos; la aplicación no crea tablas automáticamente.

## Organización MVC actual

```text
index.php
config/                 database.php, app.php
controller/             BaseController, AuthController, ModuloController
model/                   BaseModel y modelos PDO por entidad
views/                   login.php, register.php, home.php, layout.php
  auth/                  formularios de acceso independientes
  modulos/               entrada por módulo y vista compartida
assets/                  css, js e imágenes
database/                odontologia.sql
legacy/models-mysqli/    modelos antiguos archivados, fuera del flujo activo
```

`assets` conserva la ortografía correcta y el esquema sigue llamándose `odontologia.sql`, porque esos son los nombres que ya usa este proyecto.

## Base de datos revisada

El esquema declara siete tablas: `RegistroLogin` (id, nombre, correo único y contraseña), `RegistroCliente` (id, nombre, DNI único, teléfono y fecha), `adminisOdontologo` (id, nombre, teléfono y especialidad), `GestionServicios` (id, nombre, tipo y precio), `ProgramarCitas` (paciente, odontólogo, servicio, fecha, estado enum, diagnóstico y tratamiento), `TipoPago` (cita, fecha, método enum y cantidad) y `ReportesAtencion` (cita, ingreso, método y estado). Las relaciones de citas apuntan a paciente, odontólogo y servicio; pagos e informes apuntan a cita. Las claves primarias y únicas están declaradas en el SQL, y las claves foráneas protegen las referencias. El archivo no declara vistas ni procedimientos almacenados.

## Configuración

1. Crea/restaura la base ejecutando `database/odontologia.sql` en MySQL.
2. Configura `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASSWORD` en el entorno de Apache/PHP. Los valores predeterminados son `127.0.0.1`, `sonrisa_perfecta`, `root` y contraseña vacía.
3. Habilita `pdo_mysql` en la versión de PHP que sirve el sitio.
4. Abre el directorio del proyecto mediante Apache, por ejemplo `http://localhost/odontologia/`.

El proyecto no puede conectarse si el usuario/contraseña predeterminados no corresponden al MySQL local. No almacenes credenciales reales en el repositorio.

## Funcionalidades

- Registro e inicio/cierre de sesión contra `RegistroLogin`; contraseñas con `password_hash` y `password_verify`.
- CRUD de pacientes, odontólogos, servicios, citas y pagos, con consultas PDO preparadas, validación de servidor y protección CSRF.
- Citas y pagos cargan sus opciones relacionadas desde la base; las claves foráneas impiden borrar registros referenciados.
- Reportes de citas atendidas, pendientes, ingresos agrupados por método y filas guardadas en `ReportesAtencion`.
- Resumen dinámico de conteos en el inicio.

Las acciones de los módulos se procesan en `controller/ModuloController.php` y las operaciones CRUD comunes usan los modelos de `model/`. `views/modulos/app.php` se ocupa de presentar los resultados y formularios. `views/includes/header.php` conserva compatibilidad e incluye el layout compartido de `views/layout.php`.

El esquema no define roles ni permisos; las cuentas registradas comparten el mismo acceso autenticado. No se aplicó migración ni modificación a la base de datos.
