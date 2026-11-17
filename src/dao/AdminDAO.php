<?php
declare(strict_types=1);

class AdminDAO extends BaseDAO
{
    public function inserir(Admin $a): int
    {
        try {
            return $this->emTransacao(function () use ($a): int {
                $this->executar(
                    'INSERT INTO Administrador (Nome, Email, SenhaHash, Perfil, Ativo, DataCriacao)
                     VALUES (?, ?, ?, ?, 1, ?)',
                    [$a->getNome(), $a->getEmail(), $a->getSenhaHash(), $a->getPerfil(), $a->getDataCriacao()]
                );
                $id = (int)$this->pdo->lastInsertId();
                $a->setId($id);
                $this->auditar('INSERT', 'Administrador', $id, null, $this->paraArray($a));
                return $id;
            });
        } catch (PDOException $e) {
            $this->tratarDuplicidade($e, 'E-mail já cadastrado.');
        }
    }

    public function buscarPorId(int $id): ?Admin
    {
        $l = $this->executar('SELECT * FROM Administrador WHERE ID = ? LIMIT 1', [$id])->fetch();
        return $l ? $this->hidratar($l) : null;
    }

    public function buscarPorEmail(string $email): ?Admin
    {
        $l = $this->executar('SELECT * FROM Administrador WHERE Email = ? LIMIT 1', [trim($email)])->fetch();
        return $l ? $this->hidratar($l) : null;
    }

    /** @return Admin[] */
    public function listar(): array
    {
        $linhas = $this->executar('SELECT * FROM Administrador ORDER BY Nome')->fetchAll();
        return array_map([$this, 'hidratar'], $linhas);
    }

    public function atualizarSenha(int $id, string $novoHash): void
    {
        $this->emTransacao(function () use ($id, $novoHash): void {
            $this->executar('UPDATE Administrador SET SenhaHash = ? WHERE ID = ?', [$novoHash, $id]);
            $this->auditar('ALTERAR_SENHA', 'Administrador', $id);
        });
    }

    public function alterarAtivo(int $id, bool $ativo): void
    {
        $this->emTransacao(function () use ($id, $ativo): void {
            $this->executar('UPDATE Administrador SET Ativo = ? WHERE ID = ?', [(int)$ativo, $id]);
            $this->auditar($ativo ? 'ATIVAR' : 'INATIVAR', 'Administrador', $id);
        });
    }

    private function hidratar(array $l): Admin
    {
        $a = new Admin((int)$l['ID'], $l['Nome'], $l['Email'], $l['SenhaHash'], $l['Perfil'], $l['DataCriacao']);
        $a->setAtivo((bool)$l['Ativo']);
        return $a;
    }

    private function paraArray(Admin $a): array
    {
        return ['Nome' => $a->getNome(), 'Email' => $a->getEmail(), 'Perfil' => $a->getPerfil(), 'Ativo' => (int)$a->isAtivo()];
    }
}
