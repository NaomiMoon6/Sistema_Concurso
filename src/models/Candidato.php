<?php
declare(strict_types=1);

class Candidato extends Usuario
{
    private string $cpf = '';
    private string $telefone = '';
    private string $dataNascimento = '';   // Y-m-d
    private string $endereco = '';
    private string $rg = '';
    private string $tituloEleitor = '';
    private bool $ativo = true;

    public function __construct(
        int $id = 0,
        string $nome = '',
        string $email = '',
        string $senhaHash = '',
        string $cpf = '',
        ?string $dataCriacao = null
    ) {
        parent::__construct($id, $nome, $email, $senhaHash, $dataCriacao);
        if ($cpf !== '') {
            $this->setCpf($cpf);
        }
    }

    public function getCpf(): string { return $this->cpf; }
    public function setCpf(string $cpf): void
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (!self::validarCpf($cpf)) {
            throw new InvalidArgumentException('CPF inválido.');
        }
        $this->cpf = $cpf;
    }

    public function getTelefone(): string { return $this->telefone; }
    public function setTelefone(string $t): void { $this->telefone = preg_replace('/\D/', '', $t); }

    public function getDataNascimento(): string { return $this->dataNascimento; }
    public function setDataNascimento(string $d): void { $this->dataNascimento = $d; }

    public function getEndereco(): string { return $this->endereco; }
    public function setEndereco(string $e): void { $this->endereco = trim($e); }

    public function getRg(): string { return $this->rg; }
    public function setRg(string $rg): void { $this->rg = trim($rg); }

    public function getTituloEleitor(): string { return $this->tituloEleitor; }
    public function setTituloEleitor(string $t): void { $this->tituloEleitor = preg_replace('/\D/', '', $t); }

    public function isAtivo(): bool { return $this->ativo; }
    public function setAtivo(bool $ativo): void { $this->ativo = $ativo; }

    public static function validarCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int)$cpf[$i] * (($t + 1) - $i);
            }
            if ((int)$cpf[$t] !== ((10 * $soma) % 11) % 10) {
                return false;
            }
        }
        return true;
    }

    // RF01.2
    public function verificarIdadeMinima(?DateTimeInterface $dataReferencia = null): bool
    {
        $nasc = DateTime::createFromFormat('Y-m-d', $this->dataNascimento);
        if ($nasc === false) {
            return false;
        }
        $ref = $dataReferencia ?? new DateTime('today');
        return $nasc->diff($ref)->y >= (int)Config::get('idade_minima', 18);
    }

    public function getTipo(): string { return 'candidato'; }

    public function exibirPainel(): void { /* include da view do painel */ }
}
