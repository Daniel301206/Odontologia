<?php
require_once __DIR__ . '/../config/conexion.php';

/** Funciones compartidas para consultas preparadas y validaciones simples. */
abstract class ModeloBase
{
    protected mysqli $db;

    public function __construct()
    {
        $this->db = Conexion::obtenerInstancia()->obtenerConexion();
    }

    /** Ejecuta una consulta preparada y entrega el statement al modelo. */
    protected function ejecutarPreparada(string $sql, string $tipos = '', array $valores = []): mysqli_stmt
    {
        $stmt = $this->db->prepare($sql);
        if ($tipos !== '') {
            $referencias = [$tipos];
            foreach ($valores as $i => $valor) {
                $valores[$i] = $valor;
                $referencias[] = &$valores[$i];
            }
            call_user_func_array([$stmt, 'bind_param'], $referencias);
        }
        $stmt->execute();
        return $stmt;
    }

    protected function todos(string $sql, string $tipos = '', array $valores = []): array
    {
        $stmt = $this->ejecutarPreparada($sql, $tipos, $valores);
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $filas;
    }

    protected function uno(string $sql, string $tipos = '', array $valores = []): array
    {
        $stmt = $this->ejecutarPreparada($sql, $tipos, $valores);
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $fila ?: [];
    }

    protected function filasAfectadas(string $sql, string $tipos, array $valores = []): bool
    {
        $stmt = $this->ejecutarPreparada($sql, $tipos, $valores);
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    protected function error(string $mensaje): array
    {
        return ['ok' => false, 'mensaje' => $mensaje];
    }

    protected function texto(array $datos, string $campo): string
    {
        return trim((string)($datos[$campo] ?? ''));
    }

    protected function idValido($id): bool
    {
        return filter_var($id, FILTER_VALIDATE_INT) !== false && (int)$id > 0;
    }

    protected function correoValido(string $correo): bool
    {
        return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function fechaValida(string $fecha): bool
    {
        $valor = DateTime::createFromFormat('Y-m-d H:i:s', $fecha)
            ?: DateTime::createFromFormat('Y-m-d\TH:i', $fecha)
            ?: DateTime::createFromFormat('Y-m-d', $fecha);
        return $valor !== false;
    }

    protected function duplicado(mysqli_sql_exception $e): bool
    {
        return (int)$e->getCode() === 1062;
    }

    protected function errorRelacion(mysqli_sql_exception $e): bool
    {
        return (int)$e->getCode() === 1451;
    }
}
