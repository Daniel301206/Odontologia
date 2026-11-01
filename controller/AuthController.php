<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';

final class AuthController extends BaseController
{
    public function login(string $email, string $password): bool
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') return false;
        $statement = db()->prepare('SELECT id_Registro,nombre,Correo,Contrasena FROM RegistroLogin WHERE Correo = ?');
        $statement->execute([$email]);
        $user = $statement->fetch();
        if (!$user || !password_verify($password, $user['Contrasena'])) return false;
        session_regenerate_id(true);
        $_SESSION['user'] = ['id'=>(int)$user['id_Registro'], 'nombre'=>$user['nombre'], 'correo'=>$user['Correo']];
        return true;
    }

    public function register(string $name, string $email, string $password): array
    {
        $name = trim($name); $email = trim($email);
        if ($name === '' || mb_strlen($name) > 50) return ['ok'=>false, 'message'=>'El nombre debe tener entre 1 y 50 caracteres.'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) return ['ok'=>false, 'message'=>'Ingresa un correo válido de hasta 100 caracteres.'];
        if (strlen($password) < 8) return ['ok'=>false, 'message'=>'La contraseña debe tener al menos 8 caracteres.'];
        try {
            $statement = db()->prepare('INSERT INTO RegistroLogin(nombre,Correo,Contrasena) VALUES(?,?,?)');
            $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            return ['ok'=>true, 'message'=>'Cuenta creada. Ya puedes iniciar sesión.'];
        } catch (PDOException $e) {
            return ['ok'=>false, 'message'=>$e->getCode() === '23000' ? 'Ese correo ya está registrado.' : 'No se pudo registrar la cuenta. Verifica la conexión de base de datos.'];
        }
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', ['expires'=>time()-42000, 'path'=>$params['path'], 'domain'=>$params['domain'], 'secure'=>$params['secure'], 'httponly'=>$params['httponly'], 'samesite'=>'Lax']);
        }
        session_destroy();
    }
}
