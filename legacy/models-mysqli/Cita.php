<?php
require_once __DIR__ . '/ModeloBase.php';

/** Modelo de ProgramarCitas con referencias descriptivas mediante JOIN. */
class Cita extends ModeloBase
{
    private const SELECT_JOIN = 'SELECT c.id_Programar, c.id_registroCliente, p.nombreCompleto AS paciente, c.id_odontologo, o.nombreCompleto AS odontologo, c.id_GestionServicios, s.nombre AS servicio, c.fecha, c.estado, c.Diagnostico, c.Tratamiento FROM ProgramarCitas c INNER JOIN RegistroCliente p ON p.id_registroCliente = c.id_registroCliente INNER JOIN adminisOdontologo o ON o.id_odontologo = c.id_odontologo INNER JOIN GestionServicios s ON s.id_GestionServicios = c.id_GestionServicios';
    private const ESTADOS = ['Pendiente', 'Atendida', 'Cancelada'];

    public function listar(): array
    {
        return $this->todos(self::SELECT_JOIN . ' ORDER BY c.fecha DESC');
    }

    public function obtenerPorId(int|string|null $id): ?array
    {
        if (!$this->idValido($id)) return null;
        return $this->uno(self::SELECT_JOIN . ' WHERE c.id_Programar = ?', 'i', [(int)$id]);
    }

    public function listarPendientes(): array
    {
        return $this->todos(self::SELECT_JOIN . " WHERE c.estado = ? ORDER BY c.fecha ASC", 's', ['Pendiente']);
    }

    public function listarPorOdontologo(int|string|null $id): array
    {
        if (!$this->idValido($id)) return [];
        return $this->todos(self::SELECT_JOIN . ' WHERE c.id_odontologo = ? ORDER BY c.fecha DESC', 'i', [(int)$id]);
    }

    private function normalizarFecha(string $fecha): ?string
    {
        $fecha = trim($fecha);
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $fecha)
            ?: DateTime::createFromFormat('Y-m-d\TH:i', $fecha)
            ?: DateTime::createFromFormat('Y-m-d H:i', $fecha);
        $errores = DateTime::getLastErrors();
        if (!$dt || (is_array($errores) && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))) return null;
        return $dt->format('Y-m-d H:i:s');
    }

    private function validarDatos(array $datos, ?int $exceptoId = null): array|string
    {
        $paciente = filter_var($datos['id_registroCliente'] ?? null, FILTER_VALIDATE_INT);
        $odontologo = filter_var($datos['id_odontologo'] ?? null, FILTER_VALIDATE_INT);
        $servicio = filter_var($datos['id_GestionServicios'] ?? null, FILTER_VALIDATE_INT);
        $fecha = $this->normalizarFecha((string)($datos['fecha'] ?? ''));
        $estado = $this->texto($datos, 'estado');
        $diagnostico = trim((string)($datos['Diagnostico'] ?? ''));
        $tratamiento = trim((string)($datos['Tratamiento'] ?? ''));
        if (!$paciente || $paciente < 1 || !$odontologo || $odontologo < 1 || !$servicio || $servicio < 1) return 'Selecciona un paciente, odontólogo y servicio válidos.';
        if ($fecha === null) return 'La fecha y hora de la cita no son válidas.';
        if (!in_array($estado, self::ESTADOS, true)) return 'El estado debe ser Pendiente, Atendida o Cancelada.';
        if (mb_strlen($diagnostico) > 255 || mb_strlen($tratamiento) > 100) return 'El diagnóstico admite 255 caracteres y el tratamiento 100.';
        $valores = ['id_registroCliente' => (int)$paciente, 'id_odontologo' => (int)$odontologo, 'id_GestionServicios' => (int)$servicio, 'fecha' => $fecha, 'estado' => $estado, 'Diagnostico' => $diagnostico === '' ? null : $diagnostico, 'Tratamiento' => $tratamiento === '' ? null : $tratamiento];
        if (!$this->odontologoDisponible($valores['id_odontologo'], $fecha, $exceptoId)) return 'El odontólogo ya tiene una cita en esa fecha y hora.';
        return $valores;
    }

    private function odontologoDisponible(int $odontologo, string $fecha, ?int $exceptoId): bool
    {
        $sql = $exceptoId === null
            ? 'SELECT id_Programar FROM ProgramarCitas WHERE id_odontologo = ? AND fecha = ?'
            : 'SELECT id_Programar FROM ProgramarCitas WHERE id_odontologo = ? AND fecha = ? AND id_Programar <> ?';
        $fila = $exceptoId === null
            ? $this->uno($sql, 'is', [$odontologo, $fecha])
            : $this->uno($sql, 'isi', [$odontologo, $fecha, $exceptoId]);
        return $fila === [];
    }

    public function crear(array $datos): array
    {
        try {
            $v = $this->validarDatos($datos);
            if (is_string($v)) return $this->error($v);
            $stmt = $this->ejecutarPreparada('INSERT INTO ProgramarCitas (id_registroCliente, id_odontologo, id_GestionServicios, fecha, estado, Diagnostico, Tratamiento) VALUES (?, ?, ?, ?, ?, ?, ?)', 'iiissss', [$v['id_registroCliente'], $v['id_odontologo'], $v['id_GestionServicios'], $v['fecha'], $v['estado'], $v['Diagnostico'], $v['Tratamiento']]);
            $id = $stmt->insert_id; $stmt->close();
            return ['ok' => true, 'id' => $id];
        } catch (mysqli_sql_exception $e) { return $this->error('No fue posible registrar la cita. Verifica que las referencias existan.'); }
    }

    public function actualizar(int|string|null $id, array $datos): bool|array
    {
        if (!$this->idValido($id)) return $this->error('Identificador de cita no válido.');
        try {
            $v = $this->validarDatos($datos, (int)$id);
            if (is_string($v)) return $this->error($v);
            return $this->filasAfectadas('UPDATE ProgramarCitas SET id_registroCliente = ?, id_odontologo = ?, id_GestionServicios = ?, fecha = ?, estado = ?, Diagnostico = ?, Tratamiento = ? WHERE id_Programar = ?', 'iiissssi', [$v['id_registroCliente'], $v['id_odontologo'], $v['id_GestionServicios'], $v['fecha'], $v['estado'], $v['Diagnostico'], $v['Tratamiento'], (int)$id]);
        } catch (mysqli_sql_exception $e) { return $this->error('No fue posible actualizar la cita. Verifica que las referencias existan.'); }
    }

    public function eliminar(int|string|null $id): bool|array
    {
        if (!$this->idValido($id)) return false;
        try { return $this->filasAfectadas('DELETE FROM ProgramarCitas WHERE id_Programar = ?', 'i', [(int)$id]); }
        catch (mysqli_sql_exception $e) { return $this->error($this->errorRelacion($e) ? 'No se puede eliminar porque tiene registros relacionados.' : 'No fue posible eliminar la cita.'); }
    }
}
