<?php
require_once __DIR__ . '/ModeloBase.php';

/** Modelo de adminisOdontologo. */
class Odontologo extends ModeloBase
{
    public function listar(): array
    {
        return $this->todos('SELECT id_odontologo, nombreCompleto, Telefono, especialidad FROM adminisOdontologo ORDER BY id_odontologo DESC');
    }

    public function obtenerPorId(int|string|null $id): ?array
    {
        if (!$this->idValido($id)) return null;
        return $this->uno('SELECT id_odontologo, nombreCompleto, Telefono, especialidad FROM adminisOdontologo WHERE id_odontologo = ?', 'i', [(int)$id]);
    }

    private function validarDatos(array $datos): array|string
    {
        $nombre = $this->texto($datos, 'nombreCompleto');
        $telefono = $this->texto($datos, 'Telefono');
        $especialidad = $this->texto($datos, 'especialidad');
        if ($nombre === '' || mb_strlen($nombre) > 100) return 'El nombre completo es obligatorio y debe tener hasta 100 caracteres.';
        if ($telefono === '' || mb_strlen($telefono) > 15 || !preg_match('/^[0-9+()\-\s]{7,15}$/', $telefono)) return 'Ingresa un teléfono válido de hasta 15 caracteres.';
        if ($especialidad === '' || mb_strlen($especialidad) > 100) return 'La especialidad es obligatoria y debe tener hasta 100 caracteres.';
        return ['nombreCompleto' => $nombre, 'Telefono' => $telefono, 'especialidad' => $especialidad];
    }

    public function crear(array $datos): array
    {
        $v = $this->validarDatos($datos);
        if (is_string($v)) return $this->error($v);
        try {
            $stmt = $this->ejecutarPreparada('INSERT INTO adminisOdontologo (nombreCompleto, Telefono, especialidad) VALUES (?, ?, ?)', 'sss', [$v['nombreCompleto'], $v['Telefono'], $v['especialidad']]);
            $id = $stmt->insert_id; $stmt->close();
            return ['ok' => true, 'id' => $id];
        } catch (mysqli_sql_exception $e) { return $this->error('No fue posible registrar el odontólogo.'); }
    }

    public function actualizar(int|string|null $id, array $datos): bool|array
    {
        if (!$this->idValido($id)) return $this->error('Identificador de odontólogo no válido.');
        $v = $this->validarDatos($datos);
        if (is_string($v)) return $this->error($v);
        try { return $this->filasAfectadas('UPDATE adminisOdontologo SET nombreCompleto = ?, Telefono = ?, especialidad = ? WHERE id_odontologo = ?', 'sssi', [$v['nombreCompleto'], $v['Telefono'], $v['especialidad'], (int)$id]); }
        catch (mysqli_sql_exception $e) { return $this->error('No fue posible actualizar el odontólogo.'); }
    }

    public function eliminar(int|string|null $id): bool|array
    {
        if (!$this->idValido($id)) return false;
        try { return $this->filasAfectadas('DELETE FROM adminisOdontologo WHERE id_odontologo = ?', 'i', [(int)$id]); }
        catch (mysqli_sql_exception $e) { return $this->error($this->errorRelacion($e) ? 'No se puede eliminar porque tiene registros relacionados.' : 'No fue posible eliminar el odontólogo.'); }
    }
}
