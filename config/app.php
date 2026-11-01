<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']),'samesite'=>'Lax']);
    session_start();
}
function h(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function app_url(string $path): string { $script=str_replace('\\','/',(string)($_SERVER['SCRIPT_NAME']??''));$position=strpos($script,'/views/');$root=$position===false?'':substr($script,0,$position);return $root.'/'.ltrim($path,'/'); }
function require_login(): void { if (empty($_SESSION['user'])) { header('Location: '.app_url('views/login.php')); exit; } }
function check_csrf(): void { if (!hash_equals(csrf(), (string)($_POST['_csrf'] ?? ''))) { http_response_code(400); exit('Solicitud no válida. Recarga la página.'); } }
function modules(): array {
 return [
 'pacientes'=>['title'=>'Pacientes','table'=>'RegistroCliente','pk'=>'id_registroCliente','fields'=>['nombreCompleto'=>['Nombre completo','text',100],'DNI'=>['DNI','text',8],'Telefono'=>['Teléfono','tel',15]],'columns'=>['nombreCompleto','DNI','Telefono','fecha']],
 'odontologos'=>['title'=>'Odontólogos','table'=>'adminisOdontologo','pk'=>'id_odontologo','fields'=>['nombreCompleto'=>['Nombre completo','text',100],'Telefono'=>['Teléfono','tel',15],'especialidad'=>['Especialidad','text',100]],'columns'=>['nombreCompleto','Telefono','especialidad']],
 'servicios'=>['title'=>'Servicios','table'=>'GestionServicios','pk'=>'id_GestionServicios','fields'=>['nombre'=>['Nombre','text',50],'tipoServicio'=>['Tipo de servicio','text',100],'precio'=>['Precio (S/)','number',null]],'columns'=>['nombre','tipoServicio','precio']],
 'citas'=>['title'=>'Citas','table'=>'ProgramarCitas','pk'=>'id_Programar','fields'=>['id_registroCliente'=>['Paciente','ref','RegistroCliente'],'id_odontologo'=>['Odontólogo','ref','adminisOdontologo'],'id_GestionServicios'=>['Servicio','ref','GestionServicios'],'fecha'=>['Fecha y hora','datetime-local',null],'estado'=>['Estado','enum',['Pendiente','Atendida','Cancelada']],'Diagnostico'=>['Diagnóstico','text',255],'Tratamiento'=>['Tratamiento','text',100]],'columns'=>['fecha','paciente','odontologo','servicio','estado'],'join'=>'SELECT c.*,p.nombreCompleto paciente,o.nombreCompleto odontologo,s.nombre servicio FROM ProgramarCitas c JOIN RegistroCliente p USING(id_registroCliente) JOIN adminisOdontologo o USING(id_odontologo) JOIN GestionServicios s USING(id_GestionServicios)'],
 'pagos'=>['title'=>'Pagos','table'=>'TipoPago','pk'=>'id_tipoPago','fields'=>['id_Programar'=>['Cita','ref','ProgramarCitas'],'fecha'=>['Fecha y hora','datetime-local',null],'tipoPago'=>['Método de pago','enum',['Efectivo','Tarjeta de crédito','Tarjeta de débito','Yape']],'cantidad'=>['Monto (S/)','number',null]],'columns'=>['id_tipoPago','id_Programar','fecha','tipoPago','cantidad'],'join'=>'SELECT pg.* FROM TipoPago pg'],
 ];
}
