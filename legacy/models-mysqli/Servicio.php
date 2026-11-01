<?php
require_once __DIR__ . '/ModeloBase.php';

/** Modelo de GestionServicios. */
class Servicio extends ModeloBase
{
    public function listar(): array
    {
        return $this->todos('SELECT id_GestionServicios, nombre, tipoServicio, precio FROM GestionServicios ORDER BY id_GestionServicios DESC');
    }

    public function obtenerPorId(int|string|null $id): ?array
    {
        if (!$this->idValido($id)) return null;
        return $this->uno('SELECT id_GestionServicios, nombre, tipoServicio, precio FROM GestionServicios WHERE id_GestionServicios = ?', 'i', [(int)$id]);
    }

    private function validarDatos(array $datos): array|string
    {
        $nombre = $this->texto($datos, 'nombre');
        $tipo = $this->texto($datos, 'tipoServicio');
        $precio = filter_var($datos['precio'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($nombre === '' || mb_strlen($nombre) > 50) return 'El nombre es obligatorio y debe tener hasta 50 caracteres.';
        if ($tipo === '' || mb_strlen($tipo) > 100) return 'El tipo de servicio es obligatorio y debe tener hasta 100 caracteres.';
        if ($precio === false || $precio <= 0) return 'El precio debe ser un monto mayor a 0.';
        return ['nombre' => $nombre, 'tipoServicio' => $tipo, 'precio' => (float)$precio];
    }

    public function crear(array $datos): array
    {
        $v = $this->validarDatos($datos);
        if (is_string($v)) return $this->error($v);
        try {
            $stmt = $this->ejecutarPreparada('INSERT INTO GestionServicios (nombre, tipoServicio, precio) VALUES (?, ?, ?)', 'ssd', [$v['nombre'], $v['tipoServicio'], $v['precio']]);
            $id = $stmt->insert_id; $stmt->close();
            return ['ok' => true, 'id' => $id];
        } catch (mysqli_sql_exception $e) { return $this->error('No fue posible registrar el servicio.'); }
    }

    public function actualizar(int|string|null $id, array $datos): bool|array
    {
        if (!$this->idValido($id)) return $this->error('Identificador de servicio no válido.');
        $v = $this->validarDatos($datos);
        if (is_string($v)) return $this->error($v);
        try { return $this->filasAfectadas('UPDATE GestionServicios SET nombre = ?, tipoServicio = ?, precio = ? WHERE id_GestionServicios = ?', 'ssdi', [$v['nombre'], $v['tipoServicio'], $v['precio'], (int)$id]); }
        catch (mysqli_sql_exception $e) { return $this->error('No fue posible actualizar el servicio.'); }
    }

    public function eliminar(int|string|null $id): bool|array
    {
        if (!$this->idValido($id)) return false;
        try { return $this->filasAfectadas('DELETE FROM GestionServicios WHERE id_GestionServicios = ?', 'i', [(int)$id]); }
        catch (mysqli_sql_exception $e) { return $this->error($this->errorRelacion($e) ? 'No se puede eliminar porque tiene registros relacionados.' : 'No fue posible eliminar el servicio.'); }
    }
}
