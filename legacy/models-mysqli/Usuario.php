<?php
require_once __DIR__ . '/ModeloBase.php';

/** Modelo de RegistroLogin. Nunca expone la columna Contrasena. */
class Usuario extends ModeloBase
{
    private const TABLA = 'RegistroLogin';

    public function listar(): array
    {
        return $this->todos('SELECT id_Registro, nombre, Correo FROM RegistroLogin ORDER BY id_Registro DESC');
    }

    public function obtenerPorId(int|string|null $id): ?array
    {
        if (!$this->idValido($id)) return null;
        return $this->uno('SELECT id_Registro, nombre, Correo FROM RegistroLogin WHERE id_Registro = ?', 'i', [(int)$id]);
    }

    public function buscarPorCorreo(string $correo): ?array
    {
        $correo = trim($correo);
        if (!$this->correoValido($correo)) return null;
        return $this->uno('SELECT id_Registro, nombre, Correo FROM RegistroLogin WHERE Correo = ?', 's', [$correo]);
    }

    public function correoDisponible(string $correo, ?int $exceptoId = null): bool
    {
        $sql = $exceptoId === null
            ? 'SELECT id_Registro FROM RegistroLogin WHERE Correo = ?'
            : 'SELECT id_Registro FROM RegistroLogin WHERE Correo = ? AND id_Registro <> ?';
        $fila = $exceptoId === null
            ? $this->uno($sql, 's', [$correo])
            : $this->uno($sql, 'si', [$correo, $exceptoId]);
        return $fila === [];
    }

    public function registrar(array $datos): array
    {
        $nombre = $this->texto($datos, 'nombre');
        $correo = $this->texto($datos, 'Correo');
        $contrasena = (string)($datos['Contrasena'] ?? '');
        if ($nombre === '' || mb_strlen($nombre) > 50) return $this->error('El nombre es obligatorio y debe tener hasta 50 caracteres.');
        if (!$this->correoValido($correo) || mb_strlen($correo) > 100) return $this->error('Ingresa un correo válido de hasta 100 caracteres.');
        if (strlen($contrasena) < 8) return $this->error('La contraseña debe tener al menos 8 caracteres.');
        if (!$this->correoDisponible($correo)) return $this->error('El correo ya está registrado.');
        try {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt = $this->ejecutarPreparada('INSERT INTO RegistroLogin (nombre, Correo, Contrasena) VALUES (?, ?, ?)', 'sss', [$nombre, $correo, $hash]);
            $id = $stmt->insert_id;
            $stmt->close();
            return ['ok' => true, 'id' => $id];
        } catch (mysqli_sql_exception $e) {
            return $this->error($this->duplicado($e) ? 'El correo ya está registrado.' : 'No fue posible registrar el usuario.');
        }
    }

    /** Alias CRUD solicitado; usa el mismo hash seguro que registrar(). */
    public function crear(array $datos): array { return $this->registrar($datos); }

    public function verificarLogin(string $correo, string $contrasena): bool
    {
        $correo = trim($correo);
        if (!$this->correoValido($correo) || $contrasena === '') return false;
        $fila = $this->uno('SELECT Contrasena FROM RegistroLogin WHERE Correo = ?', 's', [$correo]);
        return $fila !== [] && password_verify($contrasena, $fila['Contrasena']);
    }

    public function actualizar(int|string|null $id, array $datos): bool|array
    {
        if (!$this->idValido($id)) return $this->error('Identificador de usuario no válido.');
        $nombre = $this->texto($datos, 'nombre');
        $correo = $this->texto($datos, 'Correo');
        $contrasena = (string)($datos['Contrasena'] ?? '');
        if ($nombre === '' || mb_strlen($nombre) > 50) return $this->error('El nombre es obligatorio y debe tener hasta 50 caracteres.');
        if (!$this->correoValido($correo) || mb_strlen($correo) > 100) return $this->error('Ingresa un correo válido de hasta 100 caracteres.');
        if ($contrasena !== '' && strlen($contrasena) < 8) return $this->error('La contraseña debe tener al menos 8 caracteres.');
        if (!$this->correoDisponible($correo, (int)$id)) return $this->error('El correo ya está registrado.');
        try {
            if ($contrasena === '') {
                return $this->filasAfectadas('UPDATE RegistroLogin SET nombre = ?, Correo = ? WHERE id_Registro = ?', 'ssi', [$nombre, $correo, (int)$id]);
            }
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            return $this->filasAfectadas('UPDATE RegistroLogin SET nombre = ?, Correo = ?, Contrasena = ? WHERE id_Registro = ?', 'sssi', [$nombre, $correo, $hash, (int)$id]);
        } catch (mysqli_sql_exception $e) {
            return $this->error($this->duplicado($e) ? 'El correo ya está registrado.' : 'No fue posible actualizar el usuario.');
        }
    }

    public function eliminar(int|string|null $id): bool|array
    {
        if (!$this->idValido($id)) return false;
        try { return $this->filasAfectadas('DELETE FROM RegistroLogin WHERE id_Registro = ?', 'i', [(int)$id]); }
        catch (mysqli_sql_exception $e) { return $this->error($this->errorRelacion($e) ? 'No se puede eliminar porque tiene registros relacionados.' : 'No fue posible eliminar el usuario.'); }
    }
}
