<?php
/**
 * Prueba temporal de la capa de modelos. Crea registros con nombres de prueba,
 * verifica CRUD y consultas, y los elimina al finalizar, incluso ante errores.
 * Eliminar este archivo cuando se termine la verificación.
 */
require_once __DIR__ . '/Usuario.php';
require_once __DIR__ . '/Paciente.php';
require_once __DIR__ . '/Odontologo.php';
require_once __DIR__ . '/Servicio.php';
require_once __DIR__ . '/Cita.php';
require_once __DIR__ . '/Pago.php';
require_once __DIR__ . '/Reporte.php';

$resultados = [];
$ids = ['usuario' => null, 'paciente' => null, 'odontologo' => null, 'servicio' => null, 'cita' => null, 'pago' => null];
$errorGeneral = null;
$modelos = [];

class PruebaFallida extends RuntimeException {}

function registrarPrueba(string $nombre, bool $ok, string $detalle = ''): void
{
    global $resultados;
    $resultados[] = ['nombre' => $nombre, 'ok' => $ok, 'detalle' => $detalle];
}

function exigir(bool $condicion, string $mensaje): void
{
    if (!$condicion) throw new PruebaFallida($mensaje);
}

function idCreado(array $resultado, string $entidad): int
{
    exigir(($resultado['ok'] ?? false) === true && isset($resultado['id']), 'No se pudo crear el registro de prueba de ' . $entidad . '.');
    return (int)$resultado['id'];
}

try {
    // Las muestras llevan un identificador único para no confundirse con datos reales.
    $sufijo = date('YmdHis') . random_int(1000, 9999);
    $modelos['usuario'] = new Usuario();
    $modelos['paciente'] = new Paciente();
    $modelos['odontologo'] = new Odontologo();
    $modelos['servicio'] = new Servicio();
    $modelos['cita'] = new Cita();
    $modelos['pago'] = new Pago();
    $modelos['reporte'] = new Reporte();

    $correo = 'prueba.' . $sufijo . '@example.test';
    $creado = $modelos['usuario']->registrar(['nombre' => 'Usuario Prueba ' . $sufijo, 'Correo' => $correo, 'Contrasena' => 'Temporal-' . $sufijo]);
    $ids['usuario'] = idCreado($creado, 'usuario');
    exigir(count($modelos['usuario']->listar()) > 0, 'El listado de usuarios está vacío.');
    exigir($modelos['usuario']->buscarPorCorreo($correo) !== [], 'No se encontró el usuario por correo.');
    exigir($modelos['usuario']->verificarLogin($correo, 'Temporal-' . $sufijo), 'No se verificó la contraseña de prueba.');
    exigir(!array_key_exists('Contrasena', $modelos['usuario']->obtenerPorId($ids['usuario'])), 'El modelo expuso la contraseña.');
    exigir($modelos['usuario']->actualizar($ids['usuario'], ['nombre' => 'Usuario Actualizado ' . $sufijo, 'Correo' => $correo]) === true, 'No se actualizó el usuario.');
    registrarPrueba('Usuario: registrar, listar, buscar, verificar contraseña y actualizar', true);

    $dni = (string)random_int(10000000, 99999999);
    $creado = $modelos['paciente']->crear(['nombreCompleto' => 'Paciente Prueba ' . $sufijo, 'DNI' => $dni, 'Telefono' => '999123456']);
    $ids['paciente'] = idCreado($creado, 'paciente');
    exigir(count($modelos['paciente']->listar()) > 0, 'El listado de pacientes está vacío.');
    exigir($modelos['paciente']->buscarPorDNI($dni) !== [], 'No se encontró el paciente por DNI.');
    exigir($modelos['paciente']->actualizar($ids['paciente'], ['nombreCompleto' => 'Paciente Actualizado ' . $sufijo, 'DNI' => $dni, 'Telefono' => '999123456']) === true, 'No se actualizó el paciente.');
    registrarPrueba('Paciente: crear, listar, buscar por DNI y actualizar', true);

    $creado = $modelos['odontologo']->crear(['nombreCompleto' => 'Odontólogo Prueba ' . $sufijo, 'Telefono' => '988123456', 'especialidad' => 'Odontología general']);
    $ids['odontologo'] = idCreado($creado, 'odontólogo');
    exigir(count($modelos['odontologo']->listar()) > 0, 'El listado de odontólogos está vacío.');
    exigir($modelos['odontologo']->actualizar($ids['odontologo'], ['nombreCompleto' => 'Odontólogo Actualizado ' . $sufijo, 'Telefono' => '988123456', 'especialidad' => 'Ortodoncia']) === true, 'No se actualizó el odontólogo.');
    registrarPrueba('Odontólogo: crear, listar y actualizar', true);

    $creado = $modelos['servicio']->crear(['nombre' => 'Servicio ' . $sufijo, 'tipoServicio' => 'Consulta', 'precio' => 10.50]);
    $ids['servicio'] = idCreado($creado, 'servicio');
    exigir(count($modelos['servicio']->listar()) > 0, 'El listado de servicios está vacío.');
    exigir($modelos['servicio']->actualizar($ids['servicio'], ['nombre' => 'Servicio Actualizado ' . $sufijo, 'tipoServicio' => 'Consulta', 'precio' => 11.50]) === true, 'No se actualizó el servicio.');
    registrarPrueba('Servicio: crear, listar y actualizar', true);

    $fechaCita = (new DateTime('+2 days'))->setTime(11, 23, random_int(0, 59))->format('Y-m-d H:i:s');
    $creado = $modelos['cita']->crear(['id_registroCliente' => $ids['paciente'], 'id_odontologo' => $ids['odontologo'], 'id_GestionServicios' => $ids['servicio'], 'fecha' => $fechaCita, 'estado' => 'Pendiente', 'Diagnostico' => 'Revisión de prueba', 'Tratamiento' => 'Control']);
    $ids['cita'] = idCreado($creado, 'cita');
    exigir(count($modelos['cita']->listar()) > 0, 'El listado de citas está vacío.');
    exigir($modelos['cita']->obtenerPorId($ids['cita'])['paciente'] !== '', 'El JOIN de cita no trajo el nombre del paciente.');
    exigir(count($modelos['cita']->listarPendientes()) > 0, 'No se listaron citas pendientes.');
    exigir(count($modelos['cita']->listarPorOdontologo($ids['odontologo'])) > 0, 'No se listaron citas del odontólogo.');
    exigir($modelos['cita']->actualizar($ids['cita'], ['id_registroCliente' => $ids['paciente'], 'id_odontologo' => $ids['odontologo'], 'id_GestionServicios' => $ids['servicio'], 'fecha' => $fechaCita, 'estado' => 'Atendida', 'Diagnostico' => 'Diagnóstico actualizado', 'Tratamiento' => 'Control actualizado']) === true, 'No se actualizó la cita.');
    registrarPrueba('Cita: crear, listar con JOIN, filtrar y actualizar', true);

    $creado = $modelos['pago']->crear(['id_Programar' => $ids['cita'], 'fecha' => date('Y-m-d H:i:s'), 'tipoPago' => 'Yape', 'cantidad' => 10.50]);
    $ids['pago'] = idCreado($creado, 'pago');
    exigir(count($modelos['pago']->listar()) > 0, 'El listado de pagos está vacío.');
    exigir($modelos['pago']->obtenerPorId($ids['pago'])['paciente'] !== '', 'El JOIN de pago no trajo el nombre del paciente.');
    exigir($modelos['pago']->actualizar($ids['pago'], ['id_Programar' => $ids['cita'], 'fecha' => date('Y-m-d H:i:s'), 'tipoPago' => 'Efectivo', 'cantidad' => 12.50]) === true, 'No se actualizó el pago.');
    registrarPrueba('Pago: crear, listar con JOIN y actualizar', true);

    exigir(count($modelos['reporte']->atencionesRealizadas()) > 0, 'No se consultaron atenciones realizadas.');
    exigir(array_key_exists('total_general', $modelos['reporte']->ingresosObtenidos()), 'No se consultó el total de ingresos.');
    exigir(is_array($modelos['reporte']->citasPendientes()), 'No se consultaron las citas pendientes.');
    registrarPrueba('Reporte: consultas de atenciones, ingresos y citas pendientes (solo lectura)', true);

    // Eliminar en orden inverso para respetar las claves foráneas.
    exigir($modelos['pago']->eliminar($ids['pago']) === true, 'No se eliminó el pago de prueba.'); $ids['pago'] = null;
    exigir($modelos['cita']->eliminar($ids['cita']) === true, 'No se eliminó la cita de prueba.'); $ids['cita'] = null;
    exigir($modelos['servicio']->eliminar($ids['servicio']) === true, 'No se eliminó el servicio de prueba.'); $ids['servicio'] = null;
    exigir($modelos['odontologo']->eliminar($ids['odontologo']) === true, 'No se eliminó el odontólogo de prueba.'); $ids['odontologo'] = null;
    exigir($modelos['paciente']->eliminar($ids['paciente']) === true, 'No se eliminó el paciente de prueba.'); $ids['paciente'] = null;
    exigir($modelos['usuario']->eliminar($ids['usuario']) === true, 'No se eliminó el usuario de prueba.'); $ids['usuario'] = null;
    registrarPrueba('CRUD: eliminar todos los registros temporales', true);
} catch (PruebaFallida $e) {
    $errorGeneral = 'Prueba fallida: ' . $e->getMessage();
    error_log('prueba_modelos: ' . $e->getMessage());
} catch (Throwable $e) {
    $errorGeneral = 'La prueba se interrumpió. Revisa que Apache y MySQL estén activos y que la base de datos y tablas estén creadas.';
    error_log('prueba_modelos: ' . $e->getMessage());
} finally {
    // Limpieza de respaldo si alguna prueba falló a mitad de camino.
    foreach (['pago', 'cita', 'servicio', 'odontologo', 'paciente', 'usuario'] as $tipo) {
        if (!empty($ids[$tipo]) && isset($modelos[$tipo])) {
            try { $modelos[$tipo]->eliminar($ids[$tipo]); } catch (Throwable $e) { error_log('No se pudo limpiar un registro temporal de prueba.'); }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prueba temporal de modelos</title>
    <style>
        body{font:16px/1.5 Arial,sans-serif;background:#f4f8fa;color:#173342;margin:0;padding:24px}.wrap{max-width:900px;margin:auto;background:white;border:1px solid #d9e6eb;border-radius:12px;padding:24px}h1{margin-top:0}.ok{color:#14784f}.fail{color:#ad3e48}.result{padding:10px 12px;border-bottom:1px solid #e9eff2}.warning{background:#fff4db;padding:12px;border-radius:8px}
    </style>
</head>
<body><main class="wrap">
    <h1>Verificación de modelos</h1>
    <p>Se usan datos temporales identificados como pruebas y se intenta eliminarlos automáticamente al terminar.</p>
    <?php if ($errorGeneral !== null): ?><p class="warning"><?= htmlspecialchars($errorGeneral, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php foreach ($resultados as $r): ?>
        <div class="result <?= $r['ok'] ? 'ok' : 'fail' ?>"><strong><?= $r['ok'] ? 'Correcto' : 'Error' ?>:</strong> <?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?><?= $r['detalle'] !== '' ? ' — ' . htmlspecialchars($r['detalle'], ENT_QUOTES, 'UTF-8') : '' ?></div>
    <?php endforeach; ?>
    <?php if ($errorGeneral === null): ?><p class="ok"><strong>Pruebas completadas.</strong> Los registros temporales se eliminaron.</p><?php endif; ?>
    <p class="warning"><strong>Recordatorio:</strong> borra <code>models/prueba_modelos.php</code> después de verificar los modelos.</p>
</main></body></html>
