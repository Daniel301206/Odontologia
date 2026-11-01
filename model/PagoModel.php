<?php
require_once __DIR__ . '/EntityModel.php';
final class PagoModel extends EntityModel { protected string $table='TipoPago'; protected string $primaryKey='id_tipoPago'; protected array $allowedFields=['id_Programar','fecha','tipoPago','cantidad']; }
