<?php
declare(strict_types=1);

final class Config
{
    private static ?array $dados = null;

    /** Ex.: Config::get('db.host') ou Config::get('limite_inscricoes') */
    public static function get(string $chave, $padrao = null)
    {
        if (self::$dados === null) {
            self::$dados = require __DIR__ . '/../../config/config.php';
        }
        $valor = self::$dados;
        foreach (explode('.', $chave) as $parte) {
            if (!is_array($valor) || !array_key_exists($parte, $valor)) {
                return $padrao;
            }
            $valor = $valor[$parte];
        }
        return $valor;
    }
}
