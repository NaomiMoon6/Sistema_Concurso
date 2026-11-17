<?php
declare(strict_types=1);

abstract class Usuario
{
    protected int $id = 0;
    protected string $nome = '';
    protected string $email = '';
    protected string $senhaHash = '';
    protected string $dataCriacao = '';

    public function __construct(
        int $id = 0,
        string $nome = '',
        string $email = '',
        string $senhaHash = '',
        ?string $dataCriacao = null
    ) {
        $this->id = $id;
        $this->nome = $nome;
        $this->email = $email;
        $this->senhaHash = $senhaHash;
        $this->dataCriacao = $dataCriacao ?? date('Y-m-d H:i:s');
    }

    public function getId(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getNome(): string { return $this->nome; }
    public function setNome(string $nome): void { $this->nome = trim($nome); }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail inválido.');
        }
        $this->email = $email;
    }

    public function getSenhaHash(): string { return $this->senhaHash; }
    public function setSenhaHash(string $hash): void { $this->senhaHash = $hash; }

    public function definirSenha(string $senhaPura): void
    {
        if (strlen($senhaPura) < 8) {
            throw new InvalidArgumentException('A senha deve ter ao menos 8 caracteres.');
        }
        $this->senhaHash = password_hash($senhaPura, PASSWORD_DEFAULT);
    }

    public function verificarSenha(string $senhaPura): bool
    {
        return password_verify($senhaPura, $this->senhaHash);
    }

    public function getDataCriacao(): string { return $this->dataCriacao; }

    public function login(string $senhaDigitada): bool
    {
        if (!$this->verificarSenha($senhaDigitada)) {
            return false;
        }
        Sessao::iniciar();
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $this->id;
        $_SESSION['tipo'] = $this->getTipo();
        return true;
    }

    public function logout(): void
    {
        Sessao::iniciar();
        $_SESSION = [];
        session_destroy();
    }

    /** Valida a senha atual e gera o novo hash; quem persiste é o DAO. */
    public function alterarSenha(string $senhaAtual, string $novaSenha): bool
    {
        if (!$this->verificarSenha($senhaAtual)) {
            return false;
        }
        $this->definirSenha($novaSenha);
        return true;
    }

    abstract public function getTipo(): string;
    abstract public function exibirPainel(): void;
}
