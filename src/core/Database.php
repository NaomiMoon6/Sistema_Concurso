<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $instancia = null;

    private function __construct() {}

    public static function getConexao(): PDO
    {
        if (self::$instancia === null) {
            $c = Config::get('db');
            $dsn = "mysql:host={$c['host']};dbname={$c['nome']};charset={$c['charset']}";
            try {
                self::$instancia = new PDO($dsn, $c['usuario'], $c['senha'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Não vaza host/usuário/senha para a tela
                error_log('Falha ao conectar no banco: ' . $e->getMessage());
                throw new RuntimeException('Serviço temporariamente indisponível.');
            }
        }
        return self::$instancia;
    }
}
