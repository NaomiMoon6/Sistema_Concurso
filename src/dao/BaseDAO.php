<?php
declare(strict_types=1);

abstract class BaseDAO
{
    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConexao();
    }

    /** Prepara e executa com parâmetros (sempre prepared statements). */
    protected function executar(string $sql, array $params = []): PDOStatement
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st;
    }

    /**
     * Roda $fn dentro de uma transação. Se já existir uma transação aberta
     * (ex.: iniciada por um Service), apenas participa dela.
     */
    protected function emTransacao(callable $fn)
    {
        if ($this->pdo->inTransaction()) {
            return $fn();
        }
        $this->pdo->beginTransaction();
        try {
            $resultado = $fn();
            $this->pdo->commit();
            return $resultado;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Violação de UNIQUE (SQLSTATE 23000) vira mensagem amigável. */
    protected function tratarDuplicidade(PDOException $e, string $mensagem): void
    {
        if ($e->getCode() === '23000') {
            throw new DomainException($mensagem);
        }
        throw $e;
    }

    /** RNF02/RNF04: quem, quando, IP, antes/depois. Nunca grava hash de senha. */
    protected function auditar(string $acao, string $tabela, ?int $registroId, ?array $antes = null, ?array $depois = null): void
    {
        unset($antes['SenhaHash'], $depois['SenhaHash']);
        (new LogAuditoriaDAO($this->pdo))->registrar(
            Sessao::tipo() ?? 'anonimo',
            Sessao::usuarioId(),
            $acao,
            $tabela,
            $registroId,
            $antes,
            $depois
        );
    }
}
