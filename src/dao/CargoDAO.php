<?php
declare(strict_types=1);

class CargoDAO extends BaseDAO
{
    /** @return Cargo[] */
    public function listar(bool $somenteAtivos = true): array
    {
        $sql = 'SELECT * FROM Cargo' . ($somenteAtivos ? ' WHERE Ativo = 1' : '') . ' ORDER BY TituloCargo';
        return array_map([$this, 'hidratar'], $this->executar($sql)->fetchAll());
    }

    public function buscarPorId(int $id): ?Cargo
    {
        $l = $this->executar('SELECT * FROM Cargo WHERE ID = ? LIMIT 1', [$id])->fetch();
        return $l ? $this->hidratar($l) : null;
    }

    public function inserir(Cargo $c): int
    {
        return $this->emTransacao(function () use ($c): int {
            $this->executar(
                'INSERT INTO Cargo (TituloCargo, EscolaridadeMinimaExigida, Ativo) VALUES (?, ?, ?)',
                [$c->getTitulo(), $c->getEscolaridade(), (int)$c->isAtivo()]
            );
            $id = (int)$this->pdo->lastInsertId();
            $c->setId($id);
            $this->auditar('INSERT', 'Cargo', $id, null, $this->paraArray($c));
            return $id;
        });
    }

    public function atualizar(Cargo $c): void
    {
        $antes = $this->buscarPorId($c->getId());
        $this->emTransacao(function () use ($c, $antes): void {
            $this->executar(
                'UPDATE Cargo SET TituloCargo = ?, EscolaridadeMinimaExigida = ?, Ativo = ? WHERE ID = ?',
                [$c->getTitulo(), $c->getEscolaridade(), (int)$c->isAtivo(), $c->getId()]
            );
            $this->auditar('UPDATE', 'Cargo', $c->getId(), $antes ? $this->paraArray($antes) : null, $this->paraArray($c));
        });
    }

    private function hidratar(array $l): Cargo
    {
        return new Cargo((int)$l['ID'], $l['TituloCargo'], $l['EscolaridadeMinimaExigida'], (bool)$l['Ativo']);
    }

    private function paraArray(Cargo $c): array
    {
        return ['TituloCargo' => $c->getTitulo(), 'EscolaridadeMinimaExigida' => $c->getEscolaridade(), 'Ativo' => (int)$c->isAtivo()];
    }
}
