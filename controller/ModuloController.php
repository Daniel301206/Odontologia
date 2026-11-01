<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../model/PacienteModel.php';
require_once __DIR__ . '/../model/OdontologoModel.php';
require_once __DIR__ . '/../model/ServicioModel.php';
require_once __DIR__ . '/../model/CitaModel.php';
require_once __DIR__ . '/../model/PagoModel.php';
require_login();

$key = $moduleKey ?? basename($_SERVER['SCRIPT_NAME'], '.php');
$all = modules();
if (!isset($all[$key]) && $key !== 'reportes') {
    http_response_code(404);
    exit('Módulo no encontrado');
}
$m = $all[$key] ?? null;

$error = '';
$notice = '';
$dbUnavailable = false;
$dbError = '';
$edit = null;
$entityModel = null;
$pdo = null;
$rows = $refs = $att = $pending = $income = $storedReports = [];
$total = 0.0;
$entityModel = null;
$modelClasses = [
    'pacientes'=>PacienteModel::class,
    'odontologos'=>OdontologoModel::class,
    'servicios'=>ServicioModel::class,
    'citas'=>CitaModel::class,
    'pagos'=>PagoModel::class,
];

try {
    $pdo = db();
    if (isset($modelClasses[$key])) {
        $modelClass = $modelClasses[$key];
        $entityModel = new $modelClass($pdo);
    }
} catch (Throwable $e) {
    $dbUnavailable = true;
    $dbError = 'No se pudo conectar a MySQL. Comprueba la base sonrisa_perfecta, las credenciales y la extensión PDO MySQL.';
}

if (!$dbUnavailable && $key !== 'reportes') {
    $m = $all[$key];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $action = $_POST['action'] ?? '';
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        try {
            if ($action === 'delete' && $id) {
                if ($entityModel) $entityModel->delete((int)$id);
                $notice = 'Registro eliminado.';
            } elseif ($action === 'save') {
                $data = [];
                foreach ($m['fields'] as $field => $spec) {
                    $value = trim((string)($_POST[$field] ?? ''));
                    if ($value === '' && in_array($field, ['Diagnostico', 'Tratamiento'], true)) {
                        $data[$field] = null;
                    } elseif ($value === '') {
                        throw new RuntimeException("Completa el campo {$spec[0]}.");
                    } else {
                        $data[$field] = $value;
                    }

                    if ($value !== '' && is_int($spec[2]) && mb_strlen($value) > $spec[2]) {
                        throw new RuntimeException("{$spec[0]} supera el máximo de {$spec[2]} caracteres.");
                    }
                    if ($spec[1] === 'number' && (!is_numeric($value) || (float)$value <= 0)) {
                        throw new RuntimeException("{$spec[0]} debe ser un número mayor a cero.");
                    }
                    if ($spec[1] === 'enum' && !in_array($value, $spec[2], true)) {
                        throw new RuntimeException("Valor no válido para {$spec[0]}.");
                    }
                    if ($field === 'DNI' && !preg_match('/^\d{8}$/', $value)) {
                        throw new RuntimeException('El DNI debe tener exactamente 8 dígitos.');
                    }
                    if ($field === 'Telefono' && !preg_match('/^[0-9+()\-\s]{7,15}$/', $value)) {
                        throw new RuntimeException('Ingresa un teléfono válido de hasta 15 caracteres.');
                    }
                }

                foreach ($data as $field => $value) {
                    if ($value !== null && str_starts_with($field, 'id_') && ($m['fields'][$field][1] ?? '') === 'ref') {
                        $data[$field] = (int)$value;
                    }
                }
                if (!empty($data['fecha'])) {
                    $data['fecha'] = str_replace('T', ' ', $data['fecha']);
                    if (strlen($data['fecha']) === 16) $data['fecha'] .= ':00';
                }

                if ($id) {
                    if ($entityModel) $entityModel->update((int)$id, $data);
                    $notice = 'Cambios guardados.';
                } else {
                    if ($entityModel) $entityModel->create($data);
                    $notice = 'Registro creado.';
                }
            }
        } catch (Throwable $e) {
            $error = $e instanceof PDOException
                ? 'No se pudo guardar. Revisa los datos relacionados y las restricciones.'
                : $e->getMessage();
        }
    }

    if (isset($_GET['edit'])) {
        $edit = $entityModel?->find((int)$_GET['edit']);
    }
    $join = $m['join'] ?? null;
    $rows = $join ? $pdo->query($join)->fetchAll() : ($entityModel ? $entityModel->all() : []);
    foreach ($m['fields'] as $field => $spec) {
        if ($spec[1] === 'ref') {
            if ($spec[2] === 'ProgramarCitas') {
                $refs[$field] = $pdo->query("SELECT c.id_Programar, c.fecha, p.nombreCompleto paciente FROM ProgramarCitas c JOIN RegistroCliente p USING (id_registroCliente) ORDER BY c.fecha DESC")->fetchAll();
            } else {
                $refs[$field] = $pdo->query("SELECT * FROM {$spec[2]}")->fetchAll();
            }
        }
    }
} elseif (!$dbUnavailable) {
    $att = $pdo->query("SELECT c.fecha,p.nombreCompleto paciente,o.nombreCompleto odontologo,s.nombre servicio,c.Diagnostico,c.Tratamiento FROM ProgramarCitas c JOIN RegistroCliente p USING(id_registroCliente) JOIN adminisOdontologo o USING(id_odontologo) JOIN GestionServicios s USING(id_GestionServicios) WHERE c.estado='Atendida' ORDER BY c.fecha DESC")->fetchAll();
    $pending = $pdo->query("SELECT c.fecha,p.nombreCompleto paciente,o.nombreCompleto odontologo,s.nombre servicio FROM ProgramarCitas c JOIN RegistroCliente p USING(id_registroCliente) JOIN adminisOdontologo o USING(id_odontologo) JOIN GestionServicios s USING(id_GestionServicios) WHERE c.estado='Pendiente' ORDER BY c.fecha")->fetchAll();
    $income = $pdo->query('SELECT tipoPago,SUM(cantidad) total FROM TipoPago GROUP BY tipoPago')->fetchAll();
    $total = (float)$pdo->query('SELECT COALESCE(SUM(cantidad),0) FROM TipoPago')->fetchColumn();
    $storedReports = $pdo->query('SELECT r.id_reportes,r.id_Programar,r.ingreso,r.tipoPago,r.estado,c.fecha,p.nombreCompleto paciente FROM ReportesAtencion r JOIN ProgramarCitas c USING(id_Programar) JOIN RegistroCliente p USING(id_registroCliente) ORDER BY r.id_reportes DESC')->fetchAll();
}
