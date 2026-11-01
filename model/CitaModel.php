<?php
require_once __DIR__ . '/EntityModel.php';
final class CitaModel extends EntityModel { protected string $table='ProgramarCitas'; protected string $primaryKey='id_Programar'; protected array $allowedFields=['id_registroCliente','id_odontologo','id_GestionServicios','fecha','estado','Diagnostico','Tratamiento']; }
