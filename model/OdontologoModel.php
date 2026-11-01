<?php
require_once __DIR__ . '/EntityModel.php';
final class OdontologoModel extends EntityModel { protected string $table='adminisOdontologo'; protected string $primaryKey='id_odontologo'; protected array $allowedFields=['nombreCompleto','Telefono','especialidad']; }
