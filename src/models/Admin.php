<?php
declare(strict_types=1);

class Admin extends Usuario
{
    public const PERFIS = ['admin', 'comissao'];

    private string $perfil = 'comissao';
    private bool $ativo = true;

    public function __construct(
        int $id = 0,
        string $nome = '',
        string $email = '',
        string $senhaHash = '',
        string $perfil = 'comissao',
        ?string $dataCriacao = null
    ) {
        parent::__construct($id, $nome, $email, $senhaHash, $dataCriacao);
        $this->setPerfil($perfil);
    }

    public function getPerfil(): string { return $this->perfil; }
    public function setPerfil(string $perfil): void
    {
        if (!in_array($perfil, self::PERFIS, true)) {
            throw new InvalidArgumentException('Perfil inválido.');
        }
        $this->perfil = $perfil;
    }

    public function isAtivo(): bool { return $this->ativo; }
    public function setAtivo(bool $ativo): void { $this->ativo = $ativo; }

    public function getTipo(): string { return 'admin'; }

    public function exibirPainel(): void { /* painel: recursos, leilão, classificação */ }
}
