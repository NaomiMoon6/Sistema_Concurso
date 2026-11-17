<?php
declare(strict_types=1);

class CandidatoDAO extends BaseDAO
{
    public function inserir(Candidato $c): int
    {
        try {
            return $this->emTransacao(function () use ($c): int {
                $this->executar(
                    'INSERT INTO Candidato
                        (CPF, Nome, DataNascimento, Telefone, Email, Endereco, RG, TituloEleitor, SenhaHash, Ativo, DataCriacao)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)',
                    [
                        $c->getCpf(), $c->getNome(), $c->getDataNascimento(), $c->getTelefone(),
                        $c->getEmail(), $c->getEndereco(), $c->getRg(), $c->getTituloEleitor(),
                        $c->getSenhaHash(), $c->getDataCriacao(),
                    ]
                );
                $id = (int)$this->pdo->lastInsertId();
                $c->setId($id);
                $this->auditar('INSERT', 'Candidato', $id, null, $this->paraArray($c));
                return $id;
            });
        } catch (PDOException $e) {
            $this->tratarDuplicidade($e, 'CPF ou e-mail já cadastrado.');
        }
    }

    public function atualizar(Candidato $c): void
    {
        $antes = $this->buscarPorId($c->getId());
        if ($antes === null) {
            throw new DomainException('Candidato não encontrado.');
        }
        try {
            $this->emTransacao(function () use ($c, $antes): void {
                $this->executar(
                    'UPDATE Candidato
                        SET Nome = ?, DataNascimento = ?, Telefone = ?, Email = ?,
                            Endereco = ?, RG = ?, TituloEleitor = ?
                      WHERE ID = ?',
                    [
                        $c->getNome(), $c->getDataNascimento(), $c->getTelefone(), $c->getEmail(),
                        $c->getEndereco(), $c->getRg(), $c->getTituloEleitor(), $c->getId(),
                    ]
                );
                $this->auditar('UPDATE', 'Candidato', $c->getId(), $this->paraArray($antes), $this->paraArray($c));
            });
        } catch (PDOException $e) {
            $this->tratarDuplicidade($e, 'E-mail já cadastrado.');
        }
    }

    public function atualizarSenha(int $id, string $novoHash): void
    {
        $this->emTransacao(function () use ($id, $novoHash): void {
            $this->executar('UPDATE Candidato SET SenhaHash = ? WHERE ID = ?', [$novoHash, $id]);
            $this->auditar('ALTERAR_SENHA', 'Candidato', $id);
        });
    }

    /** Soft delete: preserva inscrições/auditoria (LGPD + RNF04). */
    public function inativar(int $id): void
    {
        $this->emTransacao(function () use ($id): void {
            $this->executar('UPDATE Candidato SET Ativo = 0 WHERE ID = ?', [$id]);
            $this->auditar('INATIVAR', 'Candidato', $id);
        });
    }

    public function buscarPorId(int $id): ?Candidato
    {
        return $this->buscarUm('WHERE ID = ?', [$id]);
    }

    public function buscarPorCpf(string $cpf): ?Candidato
    {
        return $this->buscarUm('WHERE CPF = ?', [preg_replace('/\D/', '', $cpf)]);
    }

    public function buscarPorEmail(string $email): ?Candidato
    {
        return $this->buscarUm('WHERE Email = ?', [trim($email)]);
    }

    public function cpfExiste(string $cpf): bool
    {
        return (bool)$this->executar(
            'SELECT 1 FROM Candidato WHERE CPF = ? LIMIT 1',
            [preg_replace('/\D/', '', $cpf)]
        )->fetchColumn();
    }

    // ---------- internos ----------

    private function buscarUm(string $where, array $params): ?Candidato
    {
        $linha = $this->executar("SELECT * FROM Candidato $where LIMIT 1", $params)->fetch();
        return $linha ? $this->hidratar($linha) : null;
    }

    private function hidratar(array $l): Candidato
    {
        $c = new Candidato((int)$l['ID'], $l['Nome'], $l['Email'], $l['SenhaHash'], $l['CPF'], $l['DataCriacao']);
        $c->setDataNascimento((string)$l['DataNascimento']);
        $c->setTelefone((string)($l['Telefone'] ?? ''));
        $c->setEndereco((string)($l['Endereco'] ?? ''));
        $c->setRg((string)($l['RG'] ?? ''));
        $c->setTituloEleitor((string)($l['TituloEleitor'] ?? ''));
        $c->setAtivo((bool)$l['Ativo']);
        return $c;
    }

    /** Snapshot para o log (sem senha). */
    private function paraArray(Candidato $c): array
    {
        return [
            'CPF' => $c->getCpf(), 'Nome' => $c->getNome(), 'DataNascimento' => $c->getDataNascimento(),
            'Telefone' => $c->getTelefone(), 'Email' => $c->getEmail(), 'Endereco' => $c->getEndereco(),
            'RG' => $c->getRg(), 'TituloEleitor' => $c->getTituloEleitor(), 'Ativo' => (int)$c->isAtivo(),
        ];
    }
}
