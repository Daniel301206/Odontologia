<?php
require_once __DIR__ . '/ModeloBase.php';

/** Consultas de reportes. Esta clase es de solo lectura y no guarda en ReportesAtencion. */
class Reporte extends ModeloBase
{
    public function atencionesRealizadas(): array
    {
        $sql = "SELECT c.id_Programar, c.fecha, p.nombreCompleto AS paciente, o.nombreCompleto AS odontologo, s.nombre AS servicio, c.Diagnostico, c.Tratamiento FROM ProgramarCitas c INNER JOIN RegistroCliente p ON p.id_registroCliente = c.id_registroCliente INNER JOIN adminisOdontologo o ON o.id_odontologo = c.id_odontologo INNER JOIN GestionServicios s ON s.id_GestionServicios = c.id_GestionServicios WHERE c.estado = ? ORDER BY c.fecha DESC";
        return $this->todos($sql, 's', ['Atendida']);
    }

    /** Devuelve el acumulado por método y una suma global calculada desde TipoPago. */
    public function ingresosObtenidos(): array
    {
        $porMetodo = $this->todos('SELECT tipoPago, SUM(cantidad) AS total FROM TipoPago GROUP BY tipoPago ORDER BY tipoPago');
        $fila = $this->uno('SELECT COALESCE(SUM(cantidad), 0) AS total_general FROM TipoPago');
        return ['por_metodo' => $porMetodo, 'total_general' => (float)($fila['total_general'] ?? 0)];
    }

    public function citasPendientes(): array
    {
        $sql = "SELECT c.id_Programar, c.fecha, c.estado, p.nombreCompleto AS paciente, o.nombreCompleto AS odontologo, s.nombre AS servicio FROM ProgramarCitas c INNER JOIN RegistroCliente p ON p.id_registroCliente = c.id_registroCliente INNER JOIN adminisOdontologo o ON o.id_odontologo = c.id_odontologo INNER JOIN GestionServicios s ON s.id_GestionServicios = c.id_GestionServicios WHERE c.estado = ? ORDER BY c.fecha ASC";
        return $this->todos($sql, 's', ['Pendiente']);
    }
}
