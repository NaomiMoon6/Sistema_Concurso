<?php
declare(strict_types=1);

class Cargo
{
    private int $id;
    private string $titulo;
    private string $escolaridadeRequerida;
    private bool $ativo;

    public function __construct(int $id = 0, string $titulo = '', string $escolaridadeRequerida = '', bool $ativo = true)
    {
        $this->id = $id;
        $this->titulo = $titulo;
        $this->escolaridadeRequerida = $escolaridadeRequerida;
        $this->ativo = $ativo;
    }

    public function getId(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getTitulo(): string { return $this->titulo; }
    public function setTitulo(string $titulo): void { $this->titulo = trim($titulo); }

    public function getEscolaridade(): string { return $this->escolaridadeRequerida; }
    public function setEscolaridade(string $e): void { $this->escolaridadeRequerida = trim($e); }

    public function isAtivo(): bool { return $this->ativo; }
    public function setAtivo(bool $ativo): void { $this->ativo = $ativo; }
}
