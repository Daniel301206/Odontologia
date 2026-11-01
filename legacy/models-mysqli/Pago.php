<?php
require_once __DIR__ . '/ModeloBase.php';

/** Modelo de TipoPago con datos descriptivos de la cita asociada. */
class Pago extends ModeloBase
{
    private const TIPOS = ['Efectivo', 'Tarjeta de crédito', 'Tarjeta de débito', 'Yape'];
    private const SELECT_JOIN = 'SELECT pg.id_tipoPago, pg.id_Programar, pg.fecha, pg.tipoPago, pg.cantidad, p.nombreCompleto AS paciente, o.nombreCompleto AS odontologo, s.nombre AS servicio FROM TipoPago pg INNER JOIN ProgramarCitas c ON c.id_Programar = pg.id_Programar INNER JOIN RegistroCliente p ON p.id_registroCliente = c.id_registroCliente INNER JOIN adminisOdontologo o ON o.id_odontologo = c.id_odontologo INNER JOIN GestionServicios s ON s.id_GestionServicios = c.id_GestionServicios';

    public function listar(): array
    {
        return $this->todos(self::SELECT_JOIN . ' ORDER BY pg.fecha DESC');
    }

    public function obtenerPorId(int|string|null $id): ?array
    {
        if (!$this->idValido($id)) return null;
        return $this->uno(self::SELECT_JOIN . ' WHERE pg.id_tipoPago = ?', 'i', [(int)$id]);
    }

    private function normalizarFecha(string $fecha): ?string
    {
        $fecha = trim($fecha);
        if ($fecha === '') return date('Y-m-d H:i:s');
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $fecha)
            ?: DateTime::createFromFormat('Y-m-d\TH:i', $fecha)
            ?: DateTime::createFromFormat('Y-m-d', $fecha);
        $errores = DateTime::getLastErrors();
        if (!$dt || (is_array($errores) && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))) return null;
        return $dt->format('Y-m-d H:i:s');
    }

    private function validarDatos(array $datos): array|string
    {
        $cita = filter_var($datos['id_Programar'] ?? null, FILTER_VALIDATE_INT);
        $metodo = $this->texto($datos, 'tipoPago');
        $monto = filter_var($datos['cantidad'] ?? null, FILTER_VALIDATE_FLOAT);
        $fecha = $this->normalizarFecha((string)($datos['fecha'] ?? ''));
        if (!$cita || $cita < 1) return 'Selecciona una cita válida.';
        if (!in_array($metodo, self::TIPOS, true)) return 'El método de pago no es válido.';
        if ($monto === false || $monto <= 0) return 'El monto debe ser mayor a 0.';
        if ($fecha === null) return 'La fecha del pago no es válida.';
        return ['id_Programar' => (int)$cita, 'tipoPago' => $metodo, 'cantidad' => (float)$monto, 'fecha' => $fecha];
    }

    public function crear(array $datos): array
    {
        $v = $this->validarDatos($datos);
        if (is_string($v)) return $this->error($v);
        try {
            $stmt = $this->ejecutarPreparada('INSERT INTO TipoPago (id_Programar, fecha, tipoPago, cantidad) VALUES (?, ?, ?, ?)', 'issd', [$v['id_Programar'], $v['fecha'], $v['tipoPago'], $v['cantidad']]);
            $id = $stmt->insert_id; $stmt->close();
            return ['ok' => true, 'id' => $id];
        } catch (mysqli_sql_exception $e) { return $this->error('No fue posible registrar el pago. Verifica que la cita exista.'); }
    }

    public function actualizar(int|string|null $id, array $datos): bool|array
    {
        if (!$this->idValido($id)) return $this->error('Identificador de pago no válido.');
        $v = $this->validarDatos($datos);
        if (is_string($v)) return $this->error($v);
        try { return $this->filasAfectadas('UPDATE TipoPago SET id_Programar = ?, fecha = ?, tipoPago = ?, cantidad = ? WHERE id_tipoPago = ?', 'issdi', [$v['id_Programar'], $v['fecha'], $v['tipoPago'], $v['cantidad'], (int)$id]); }
        catch (mysqli_sql_exception $e) { return $this->error('No fue posible actualizar el pago. Verifica que la cita exista.'); }
    }

    public function eliminar(int|string|null $id): bool|array
    {
        if (!$this->idValido($id)) return false;
        try { return $this->filasAfectadas('DELETE FROM TipoPago WHERE id_tipoPago = ?', 'i', [(int)$id]); }
        catch (mysqli_sql_exception $e) { return $this->error($this->errorRelacion($e) ? 'No se puede eliminar porque tiene registros relacionados.' : 'No fue posible eliminar el pago.'); }
    }
}
