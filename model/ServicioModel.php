<?php
require_once __DIR__ . '/EntityModel.php';
final class ServicioModel extends EntityModel { protected string $table='GestionServicios'; protected string $primaryKey='id_GestionServicios'; protected array $allowedFields=['nombre','tipoServicio','precio']; }
