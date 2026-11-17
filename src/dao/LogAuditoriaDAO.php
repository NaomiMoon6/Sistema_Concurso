<?php
declare(strict_types=1);

class LogAuditoriaDAO extends BaseDAO
{
    public function registrar(
        string $tipoUsuario,
        ?int $usuarioId,
        string $acao,
        string $tabela,
        ?int $registroId,
        ?array $antes,
        ?array $depois
    ): void {
        $this->executar(
            'INSERT INTO LogAuditoria
                (UsuarioTipo, UsuarioID, Acao, Tabela, RegistroID, DadosAntes, DadosDepois, IP, DataHora)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $tipoUsuario,
                $usuarioId,
                $acao,
                $tabela,
                $registroId,
                $antes  === null ? null : json_encode($antes,  JSON_UNESCAPED_UNICODE),
                $depois === null ? null : json_encode($depois, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]
        );
    }

    public function listarPorRegistro(string $tabela, int $registroId): array
    {
        return $this->executar(
            'SELECT * FROM LogAuditoria WHERE Tabela = ? AND RegistroID = ? ORDER BY DataHora DESC',
            [$tabela, $registroId]
        )->fetchAll();
    }
}
