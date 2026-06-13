<?php
require_once BASE_PATH . '/src/helpers/csrf.php';

use models\User;
use models\Registration;

class AuthController {
    public static function login(): void {
        if (getCurrentUser()) {
            redirect(isBoard() ? '/admin' : '/dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности. Попробуйте ещё раз.', 'error');
                renderTemplate('auth/login');
                return;
            }
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $user = User::findByEmail($email);
            if ($user && User::verifyPassword($password, $user['password_hash'])) {
                if ($user['status'] !== 'active') {
                    flash('Учётная запись заблокирована', 'error');
                    redirect('/login');
                    return;
                }
                unset($user['password_hash']);
                session_regenerate_id(true);
                $_SESSION['user'] = $user;
                redirect(in_array((int)$user['role_id'], [1,2,3,4], true) ? '/admin' : '/dashboard');
            }
            flash('Неверный email или пароль', 'error');
        }

        renderTemplate('auth/login', ['flash' => getFlash()]);
    }

    public static function register(): void {
        if (getCurrentUser()) redirect('/dashboard');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности', 'error');
                renderTemplate('auth/register');
                return;
            }
            $fullName = trim($_POST['full_name'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $phone    = trim($_POST['phone'] ?? '');
            $address  = trim($_POST['address'] ?? '');
            $message  = trim($_POST['message'] ?? '');

            if (!$fullName || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('Заполните обязательные поля корректно', 'error');
                renderTemplate('auth/register', ['flash' => getFlash()]);
                return;
            }
            if (Registration::emailExists($email)) {
                flash('Заявка с этим email уже существует или email занят', 'error');
                renderTemplate('auth/register', ['flash' => getFlash()]);
                return;
            }
            Registration::create($email, $fullName, $phone, $address, $message);
            flash('Заявка отправлена! Председатель рассмотрит её в ближайшее время.', 'success');
            redirect('/login');
        }

        renderTemplate('auth/register', ['flash' => getFlash()]);
    }

    public static function logout(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/');
            return;
        }
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            flash('Ошибка безопасности. Попробуйте ещё раз.', 'error');
            redirect('/');
            return;
        }
        session_unset();
        session_destroy();
        redirect('/');
    }
}
