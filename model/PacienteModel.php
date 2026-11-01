<?php
require_once __DIR__ . '/EntityModel.php';
final class PacienteModel extends EntityModel { protected string $table='RegistroCliente'; protected string $primaryKey='id_registroCliente'; protected array $allowedFields=['nombreCompleto','DNI','Telefono']; }
