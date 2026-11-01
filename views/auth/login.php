<?php
require_once __DIR__.'/../../controller/AuthController.php';
$auth = new AuthController();
$error='';
if($_SERVER['REQUEST_METHOD']==='POST') { check_csrf(); $email=trim($_POST['correo']??''); $password=(string)($_POST['contrasena']??'');
 try { if($auth->login($email,$password)) { header('Location: '.app_url('views/home.php'));exit; } $error='Correo o contraseña incorrectos.'; }
 catch(Throwable $e){$error='No se pudo conectar con la base de datos. Verifica MySQL y la extensión PDO MySQL.';}
}
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión | Sonrisa Perfecta</title>
<link rel="stylesheet" href="<?=h(app_url('assets/css/estilos.css'))?>">
<style>
body.auth-page{min-height:100vh;background:linear-gradient(145deg,#e8f7fb,#f7fafb 55%,#eaf5f7);display:grid;place-items:center;padding:24px}
.auth-window{width:min(100%,440px);background:#fff;border:1px solid #d9e6eb;border-radius:20px;padding:38px;box-shadow:0 24px 70px #17334220}
.auth-brand{text-align:center;margin-bottom:28px}.auth-brand .brand-mark{margin:0 auto 12px;width:58px;height:58px}.auth-brand strong{display:block;font-size:1.35rem;color:#173342}.auth-brand small{color:#617782}
.auth-window h1{margin:0;text-align:center;font-size:1.7rem}.auth-window .intro{text-align:center;color:#617782;margin:8px 0 26px}
.auth-window .field{margin:0 0 17px}.auth-window .button{width:100%;margin-top:5px;padding:12px}.auth-window .auth-links{text-align:center;margin:21px 0 0;color:#617782}
@media(max-width:480px){.auth-window{padding:28px 22px}}
</style>
</head>
<body class="auth-page">
<main class="auth-window" aria-labelledby="login-title">
<div class="auth-brand"><span class="brand-mark" aria-hidden="true">✦</span><strong>Sonrisa Perfecta</strong><small>Clínica odontológica</small></div>
<h1 id="login-title">Iniciar sesión</h1><p class="intro">Ingresa con tu cuenta registrada</p>
<?php if($error):?><div class="notice error" role="alert"><?=h($error)?></div><?php endif?>
<form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf())?>"><div class="field"><label for="correo">Correo electrónico</label><input id="correo" name="correo" type="email" maxlength="100" required autocomplete="username"></div><div class="field"><label for="contrasena">Contraseña</label><input id="contrasena" name="contrasena" type="password" required autocomplete="current-password"></div><button class="button button-primary" type="submit">Ingresar</button></form>
<p class="auth-links">¿No tienes una cuenta? <a href="<?=h(app_url('views/register.php'))?>">Regístrate</a></p>
</main></body></html>
