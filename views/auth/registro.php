<?php
require_once __DIR__.'/../../controller/AuthController.php'; $auth=new AuthController();$error='';$notice='';
if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();$name=trim($_POST['nombre']??'');$email=trim($_POST['correo']??'');$pass=(string)($_POST['contrasena']??'');
 $result=$auth->register($name,$email,$pass);if($result['ok'])$notice=$result['message'];else$error=$result['message'];}
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Crear cuenta | Sonrisa Perfecta</title><link rel="stylesheet" href="<?=h(app_url('assets/css/estilos.css'))?>">
<style>
body.auth-page{min-height:100vh;background:linear-gradient(145deg,#e8f7fb,#f7fafb 55%,#eaf5f7);display:grid;place-items:center;padding:24px}
.auth-window{width:min(100%,480px);background:#fff;border:1px solid #d9e6eb;border-radius:20px;padding:36px;box-shadow:0 24px 70px #17334220}
.auth-brand{text-align:center;margin-bottom:22px}.auth-brand .brand-mark{margin:0 auto 10px;width:54px;height:54px}.auth-brand strong{display:block;font-size:1.3rem;color:#173342}.auth-brand small{color:#617782}
.auth-window h1{margin:0;text-align:center;font-size:1.7rem}.auth-window .intro{text-align:center;color:#617782;margin:8px 0 22px}
.auth-window .field{margin:0 0 14px}.auth-window .button{width:100%;margin-top:5px;padding:12px}.auth-window .auth-links{text-align:center;margin:20px 0 0;color:#617782}
@media(max-width:480px){.auth-window{padding:28px 22px}}
</style></head><body class="auth-page"><main class="auth-window" aria-labelledby="register-title">
<div class="auth-brand"><span class="brand-mark" aria-hidden="true">✦</span><strong>Sonrisa Perfecta</strong><small>Clínica odontológica</small></div>
<h1 id="register-title">Crear cuenta</h1><p class="intro">Registra los datos de acceso de tu cuenta</p>
<?php if($error):?><div class="notice error" role="alert"><?=h($error)?></div><?php endif?><?php if($notice):?><div class="notice" role="status"><?=h($notice)?></div><?php endif?>
<form method="post" data-validate><input type="hidden" name="_csrf" value="<?=h(csrf())?>"><div class="field"><label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="50" required autocomplete="name"></div><div class="field"><label for="correo">Correo</label><input id="correo" name="correo" type="email" maxlength="100" required autocomplete="email"></div><div class="field"><label for="contrasena">Contraseña</label><input id="contrasena" name="contrasena" type="password" minlength="8" required autocomplete="new-password"></div><div class="field"><label for="rol">Rol</label><select id="rol" name="rol"><option value="recepcion" selected>Recepción</option></select><small class="role-note">El registro existente asigna automáticamente el rol de recepción.</small></div><button class="button button-primary" type="submit">Crear cuenta</button></form>
<p class="auth-links">¿Ya tienes una cuenta? <a href="<?=h(app_url('views/login.php'))?>">Inicia sesión</a></p>
</main><script src="<?=h(app_url('assets/js/validaciones.js'))?>" defer></script></body></html>
