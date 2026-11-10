<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\User;

class AuthController extends Controller {
    public function loginForm(): void {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->render('auth.login', ['title' => 'Sign In']);
    }

    public function login(): void {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->setFlash('error', 'Please enter your email and password.');
            $this->redirect('/login');
        }

        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password'])) {
            $this->setFlash('error', 'Invalid email or password credentials.');
            $this->redirect('/login');
        }

        Auth::login($user);
        $this->setFlash('success', 'Welcome back, ' . htmlspecialchars($user['full_name']) . '!');
        $this->redirect('/dashboard');
    }

    public function logout(): void {
        Auth::logout();
        $this->redirect('/login');
    }
}
