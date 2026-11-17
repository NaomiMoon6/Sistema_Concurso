<?php
declare(strict_types=1);

final class Sessao
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'httponly' => true,
                'secure'   => !empty($_SERVER['HTTPS']),
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function usuarioId(): ?int
    {
        return isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
    }

    /** 'candidato' | 'admin' | null */
    public static function tipo(): ?string
    {
        return $_SESSION['tipo'] ?? null;
    }

    /** Guarda de rota: Sessao::exigir('admin') no topo das páginas restritas. */
    public static function exigir(string ...$tipos): void
    {
        self::iniciar();
        if (!in_array(self::tipo(), $tipos, true)) {
            header('Location: login.php');
            exit;
        }
    }
}
