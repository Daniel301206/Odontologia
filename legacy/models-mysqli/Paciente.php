<?php
require_once __DIR__ . '/ModeloBase.php';

/** Modelo de RegistroCliente. */
class Paciente extends ModeloBase
{
    public function listar(): array
    {
        return $this->todos('SELECT id_registroCliente, nombreCompleto, DNI, Telefono, fecha FROM RegistroCliente ORDER BY id_registroCliente DESC');
    }

    public function obtenerPorId(int|string|null $id): ?array
    {
        if (!$this->idValido($id)) return null;
        return $this->uno('SELECT id_registroCliente, nombreCompleto, DNI, Telefono, fecha FROM RegistroCliente WHERE id_registroCliente = ?', 'i', [(int)$id]);
    }

    public function buscarPorDNI(string $dni): ?array
    {
        $dni = trim($dni);
        if (!preg_match('/^\d{8}$/', $dni)) return null;
        return $this->uno('SELECT id_registroCliente, nombreCompleto, DNI, Telefono, fecha FROM RegistroCliente WHERE DNI = ?', 's', [$dni]);
    }

    public function dniDisponible(string $dni, ?int $exceptoId = null): bool
    {
        $fila = $exceptoId === null
            ? $this->uno('SELECT id_registroCliente FROM RegistroCliente WHERE DNI = ?', 's', [$dni])
            : $this->uno('SELECT id_registroCliente FROM RegistroCliente WHERE DNI = ? AND id_registroCliente <> ?', 'si', [$dni, $exceptoId]);
        return $fila === [];
    }

    private function validarDatos(array $datos, ?int $exceptoId = null): array|string
    {
        $nombre = $this->texto($datos, 'nombreCompleto');
        $dni = trim((string)($datos['DNI'] ?? ''));
        $telefono = $this->texto($datos, 'Telefono');
        if ($nombre === '' || mb_strlen($nombre) > 100) return 'El nombre completo es obligatorio y debe tener hasta 100 caracteres.';
        if (!preg_match('/^\d{8}$/', $dni)) return 'El DNI debe contener exactamente 8 dígitos.';
        if ($telefono === '' || mb_strlen($telefono) > 15 || !preg_match('/^[0-9+()\-\s]{7,15}$/', $telefono)) return 'Ingresa un teléfono válido de hasta 15 caracteres.';
        if (!$this->dniDisponible($dni, $exceptoId)) return 'El DNI ya está registrado.';
        return ['nombreCompleto' => $nombre, 'DNI' => $dni, 'Telefono' => $telefono];
    }

    public function crear(array $datos): array
    {
        $valores = $this->validarDatos($datos);
        if (is_string($valores)) return $this->error($valores);
        try {
            $stmt = $this->ejecutarPreparada('INSERT INTO RegistroCliente (nombreCompleto, DNI, Telefono) VALUES (?, ?, ?)', 'sss', [$valores['nombreCompleto'], $valores['DNI'], $valores['Telefono']]);
            $id = $stmt->insert_id;
            $stmt->close();
            return ['ok' => true, 'id' => $id];
        } catch (mysqli_sql_exception $e) {
            return $this->error($this->duplicado($e) ? 'El DNI ya está registrado.' : 'No fue posible registrar el paciente.');
        }
    }

    public function actualizar(int|string|null $id, array $datos): bool|array
    {
        if (!$this->idValido($id)) return $this->error('Identificador de paciente no válido.');
        $valores = $this->validarDatos($datos, (int)$id);
        if (is_string($valores)) return $this->error($valores);
        try {
            return $this->filasAfectadas('UPDATE RegistroCliente SET nombreCompleto = ?, DNI = ?, Telefono = ? WHERE id_registroCliente = ?', 'sssi', [$valores['nombreCompleto'], $valores['DNI'], $valores['Telefono'], (int)$id]);
        } catch (mysqli_sql_exception $e) {
            return $this->error($this->duplicado($e) ? 'El DNI ya está registrado.' : 'No fue posible actualizar el paciente.');
        }
    }

    public function eliminar(int|string|null $id): bool|array
    {
        if (!$this->idValido($id)) return false;
        try { return $this->filasAfectadas('DELETE FROM RegistroCliente WHERE id_registroCliente = ?', 'i', [(int)$id]); }
        catch (mysqli_sql_exception $e) { return $this->error($this->errorRelacion($e) ? 'No se puede eliminar porque tiene registros relacionados.' : 'No fue posible eliminar el paciente.'); }
    }
}
