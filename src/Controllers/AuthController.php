<?php

namespace Clinica\Controllers;

use Clinica\Core\Controller;
use Clinica\Core\Database;
use Clinica\Helpers\Csrf;

class AuthController extends Controller
{
    public function login()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Se já estiver logado, manda para o dashboard
        if (!empty($_SESSION['usuario_id'])) {
            $this->redirect('dashboard');
        }

        $mensagemErro = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::requireValidation();

            $email = trim($_POST['email'] ?? '');
            $senha = trim($_POST['senha'] ?? '');

            if ($email === '' || $senha === '') {
                $mensagemErro = 'Informe e-mail e senha.';
            } else {
                $pdo = Database::getInstance()->getConnection();
                $stmt = $pdo->prepare("SELECT id, email, senha_hash FROM usuarios WHERE email = :email");
                $stmt->execute([':email' => $email]);
                $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
                    $_SESSION['usuario_id'] = (int) $usuario['id'];
                    $_SESSION['usuario_email'] = $usuario['email'];

                    $this->redirect('dashboard');
                } else {
                    $mensagemErro = 'E-mail ou senha inválidos.';
                }
            }
        }

        // Include simple auth view manually since we might not want the admin layout
        $data = [
            'mensagemErro' => $mensagemErro,
            'csrfField' => Csrf::csrfField()
        ];
        extract($data);

        // Let's use the standard view rendering if we structure it. Or just include it.
        // We can create a dedicated view.
        $this->view('auth/login', $data);
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_unset();
        session_destroy();

        $this->redirect('login');
    }
}
