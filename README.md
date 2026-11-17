# Sistema de Inscrição On-line – Carmo de Minas 

### Projeto de conclusão do 4º Semestre de Análise e Desenvolvimento de Sistemas do Centro Universitário São Lourenço

Sistema web em **PHP orientado a objetos + MySQL** para inscrição, classificação, recursos e leilão de vagas de profissionais da educação.

### Grupo (NUMERO):

* Ana Carolina Valim Faria - (RA)
* Luccas - (RA)
* Naomi Marra Marcondes - 15888

---

## 1. Arquitetura em camadas

```
View (páginas PHP)  →  Service (regras do edital)  →  DAO (acesso ao banco)  →  MySQL
                              ↑                              ↑
                        Models (entidades)            Core (Config Database, Sessao)
```

- **Models:** representam os dados e validam o que é seu (CPF, e-mail, senha, idade). Não falam com o banco.
- **DAOs:** únicos que executam SQL (sempre com *prepared statements*). Cada INSERT/UPDATE gera um registro de auditoria.
- **Services:** orquestram as regras de negócio (RN01 a RN05) usando models e DAOs. *(a implementar)*
- **Core:** infraestrutura compartilhada (configuração, conexão, sessão).

## 2. Estrutura de pastas

```
raiz/
├── autoload.php            Carrega classes pelo nome do arquivo
├── config/config.php       Banco, prazos, limites do edital
├── sql/schema.sql          Criação das tabelas + cargos do Anexo I
├── storage/                (a criar) Uploads de documentos, fora do acesso web
└── src/
    ├── core/               Config, Database, Sessao
    ├── models/             Usuario, Candidato, Admin, Cargo (+ demais a implementar)
    ├── dao/                BaseDAO, LogAuditoriaDAO, CandidatoDAO, AdminDAO, CargoDAO (+ demais)
    └── services/           (a implementar)
```

As pastas `config/`, `sql/` e `src/` têm `.htaccess` com `Require all denied`.

## 3. Como começar

1. Crie o banco `oficinahotrods_equipeX` no cPanel e importe `sql/schema.sql`.
2. Edite `config/config.php` com usuário e senha do banco.
3. Nas páginas, inclua `require_once 'autoload.php';`.

---

## 4. Core

### `Config` (`src/core/Config.php`)
Leitura centralizada de `config/config.php`.

| Método | O que faz |
|---|---|
| `static get(string $chave, $padrao = null)` | Retorna um valor de configuração. Aceita notação com ponto: `Config::get('db.host')`, `Config::get('limite_inscricoes')`. Devolve `$padrao` se a chave não existir. |

### `Database` (`src/core/Database.php`)
Conexão única (*singleton*) com o MySQL via PDO.

| Método | O que faz |
|---|---|
| `static getConexao(): PDO` | Cria a conexão na primeira chamada e reutiliza nas seguintes. Usa exceções, `utf8mb4` e prepared statements reais. Em caso de falha, grava o erro no log do servidor e lança uma mensagem genérica (sem expor credenciais). |

### `Sessao` (`src/core/Sessao.php`)
Controle de sessão e permissões.

| Método | O que faz |
|---|---|
| `static iniciar()` | Inicia a sessão (se ainda não iniciada) com cookie `httponly`, `secure` (em HTTPS) e `SameSite=Lax`. |
| `static usuarioId(): ?int` | ID do usuário logado, ou `null`. |
| `static tipo(): ?string` | `'candidato'`, `'admin'` ou `null`. |
| `static exigir(string ...$tipos)` | Guarda de rota: se o usuário não for de um dos tipos informados, redireciona para `login.php`. Use no topo de páginas restritas: `Sessao::exigir('admin');`. |

---

## 5. Models

### `Usuario` (abstrata, `src/models/Usuario.php`)
Base de `Candidato` e `Admin`. Atributos protegidos: `id`, `nome`, `email`, `senhaHash`, `dataCriacao`.

| Método | O que faz |
|---|---|
| `__construct(id, nome, email, senhaHash, ?dataCriacao)` | Monta o objeto. Se `dataCriacao` não for informada, usa a data/hora atual (ao carregar do banco, passa-se a data real). |
| `getId()` / `setId()` | Lê/define o ID. |
| `getNome()` / `setNome()` | Lê/define o nome (aplica `trim`). |
| `getEmail()` / `setEmail()` | Lê/define o e-mail. O setter valida o formato e lança `InvalidArgumentException` se inválido. |
| `getSenhaHash()` / `setSenhaHash()` | Lê/define o hash já pronto (usado ao carregar do banco). |
| `definirSenha(string $senhaPura)` | Exige no mínimo 8 caracteres e grava o hash com `password_hash()`. **Nunca** guarda senha em texto puro. |
| `verificarSenha(string $senhaPura): bool` | Compara a senha digitada com o hash (`password_verify()`). |
| `getDataCriacao()` | Data de criação do usuário. |
| `login(string $senha): bool` | Se a senha confere, inicia a sessão, regenera o ID da sessão (contra *session fixation*) e grava `usuario_id` e `tipo`. Retorna `false` se a senha estiver errada. |
| `logout()` | Limpa e destrói a sessão. |
| `alterarSenha(string $atual, string $nova): bool` | Confere a senha atual e gera o novo hash. **Não persiste**: depois chame o `atualizarSenha()` do DAO. |
| `abstract getTipo(): string` | Cada filha informa seu tipo (`'candidato'` ou `'admin'`). |
| `abstract exibirPainel(): void` | Cada filha mostra seu próprio painel. |

### `Candidato` extends `Usuario` (`src/models/Candidato.php`)
Pessoa que se inscreve no processo seletivo (RF01).
Atributos próprios: `cpf`, `telefone`, `dataNascimento` (`Y-m-d`), `endereco`, `rg`, `tituloEleitor`, `ativo`.

| Método | O que faz |
|---|---|
| `__construct(id, nome, email, senhaHash, cpf, ?dataCriacao)` | Chama o construtor da base e, se houver CPF, valida e guarda. |
| `getCpf()` / `setCpf()` | O setter remove pontuação, valida os dígitos verificadores e lança `InvalidArgumentException` se inválido. |
| `getTelefone()` / `setTelefone()` | Guarda apenas os dígitos. |
| `getDataNascimento()` / `setDataNascimento()` | Data de nascimento no formato `Y-m-d`. |
| `getEndereco()` / `setEndereco()` | Endereço de residência. |
| `getRg()` / `setRg()` | RG/CIN. |
| `getTituloEleitor()` / `setTituloEleitor()` | Título de eleitor (apenas dígitos). |
| `isAtivo()` / `setAtivo()` | Indica se o cadastro está ativo (usado no *soft delete*). |
| `static validarCpf(string $cpf): bool` | Valida CPF: 11 dígitos, rejeita sequências repetidas (`111.111.111-11`) e confere os dois dígitos verificadores. |
| `verificarIdadeMinima(?DateTimeInterface $ref): bool` | **RF01.2.** Confere se tinha 18 anos (valor em `config.php`) na data de referência. Sem argumento, usa hoje; no fluxo de inscrição, passe a data da inscrição. Data inválida retorna `false`. |
| `getTipo()` | Retorna `'candidato'`. |
| `exibirPainel()` | Painel do candidato com suas inscrições *(a implementar)*. |

### `Admin` extends `Usuario` (`src/models/Admin.php`)
Membro da comissão ou administrador (RF04 e RF05).
Constante `PERFIS = ['admin', 'comissao']`. Atributos próprios: `perfil`, `ativo`.

| Método | O que faz |
|---|---|
| `__construct(id, nome, email, senhaHash, perfil, ?dataCriacao)` | Monta o admin e valida o perfil. |
| `getPerfil()` / `setPerfil()` | O setter só aceita valores de `PERFIS`; senão lança `InvalidArgumentException`. |
| `isAtivo()` / `setAtivo()` | Permite desativar um acesso sem apagar o histórico. |
| `getTipo()` | Retorna `'admin'`. |
| `exibirPainel()` | Painel de recursos, leilão e classificação *(a implementar)*. |

### `Cargo` (`src/models/Cargo.php`)
Cargo do Anexo I (RF02.1). Atributos: `id`, `titulo`, `escolaridadeRequerida`, `ativo`.

| Método | O que faz |
|---|---|
| `__construct(id, titulo, escolaridadeRequerida, ativo)` | Monta o cargo. |
| `getId()` / `setId()` | ID. |
| `getTitulo()` / `setTitulo()` | Título do cargo. |
| `getEscolaridade()` / `setEscolaridade()` | Escolaridade mínima exigida (1º critério de classificação). |
| `isAtivo()` / `setAtivo()` | Se o cargo está disponível para inscrição. |

---

## 6. DAOs

### `BaseDAO` (abstrata, `src/dao/BaseDAO.php`)
Herdada por todos os DAOs. Guarda a conexão PDO (`$pdo`), que pode ser injetada no construtor (útil para compartilhar uma transação).

| Método | O que faz |
|---|---|
| `__construct(?PDO $pdo = null)` | Usa a conexão informada ou `Database::getConexao()`. |
| `executar(string $sql, array $params): PDOStatement` | Prepara e executa a consulta com parâmetros. **Nunca** concatene valores no SQL. |
| `emTransacao(callable $fn)` | Executa `$fn` dentro de uma transação: *commit* se tudo der certo, *rollback* e relançamento do erro se falhar. Se já houver transação aberta (ex.: de um Service), apenas participa dela. |
| `tratarDuplicidade(PDOException $e, string $msg)` | Converte violação de `UNIQUE` (SQLSTATE 23000) em `DomainException` com a mensagem amigável; outros erros são relançados. |
| `auditar(acao, tabela, registroId, ?antes, ?depois)` | Grava no `LogAuditoria` quem fez (da sessão, ou `anonimo`), o IP e os dados antes/depois. Remove `SenhaHash` antes de gravar. |

### `LogAuditoriaDAO` (`src/dao/LogAuditoriaDAO.php`)
Atende ao RNF02 e ao RNF04.

| Método | O que faz |
|---|---|
| `registrar(tipoUsuario, usuarioId, acao, tabela, registroId, antes, depois)` | Insere uma linha de auditoria (os arrays são salvos como JSON, junto com IP e data/hora). Normalmente chamado via `BaseDAO::auditar()`. |
| `listarPorRegistro(string $tabela, int $registroId): array` | Histórico de um registro, do mais recente ao mais antigo (útil em litígios). |

### `CandidatoDAO` (`src/dao/CandidatoDAO.php`)
Tabela `Candidato`.

| Método | O que faz |
|---|---|
| `inserir(Candidato $c): int` | Cadastra o candidato, define o ID gerado no objeto, audita e retorna o ID. CPF/e-mail repetido → `DomainException('CPF ou e-mail já cadastrado.')`. |
| `atualizar(Candidato $c): void` | Atualiza os dados pessoais (não mexe em CPF, senha nem `Ativo`) e audita antes/depois. |
| `atualizarSenha(int $id, string $novoHash): void` | Troca o hash da senha (o log registra a ação, sem a senha). |
| `inativar(int $id): void` | *Soft delete* (`Ativo = 0`). Preserva inscrições e auditoria, em conformidade com a LGPD e o RNF04. |
| `buscarPorId(int $id): ?Candidato` | Retorna o objeto ou `null`. |
| `buscarPorCpf(string $cpf): ?Candidato` | Normaliza o CPF (só dígitos) e busca. Usado no login. |
| `buscarPorEmail(string $email): ?Candidato` | Busca por e-mail. |
| `cpfExiste(string $cpf): bool` | Verifica rapidamente se o CPF já está cadastrado. |
| *(privados)* `buscarUm`, `hidratar`, `paraArray` | Montam o `Candidato` a partir da linha do banco e geram o *snapshot* usado no log. |

### `AdminDAO` (`src/dao/AdminDAO.php`)
Tabela `Administrador`.

| Método | O que faz |
|---|---|
| `inserir(Admin $a): int` | Cadastra o administrador/comissão e audita. E-mail repetido → `DomainException`. |
| `buscarPorId(int $id): ?Admin` | Busca por ID. |
| `buscarPorEmail(string $email): ?Admin` | Busca por e-mail (login do painel). |
| `listar(): array` | Todos os administradores, em ordem alfabética. |
| `atualizarSenha(int $id, string $novoHash): void` | Troca o hash da senha. |
| `alterarAtivo(int $id, bool $ativo): void` | Ativa ou desativa o acesso e audita. |

### `CargoDAO` (`src/dao/CargoDAO.php`)
Tabela `Cargo`.

| Método | O que faz |
|---|---|
| `listar(bool $somenteAtivos = true): array` | Lista cargos (por padrão, só os ativos) em ordem alfabética. Alimenta o formulário de inscrição. |
| `buscarPorId(int $id): ?Cargo` | Busca um cargo. |
| `inserir(Cargo $c): int` | Cadastra e audita. |
| `atualizar(Cargo $c): void` | Atualiza título, escolaridade e status e audita antes/depois. |

---

## 7. Banco de dados (`sql/schema.sql`)

| Tabela | Finalidade | Origem |
|---|---|---|
| `Candidato` | Dados pessoais e de acesso | Edital (+ `RG`, `TituloEleitor`, `Ativo`) |
| `Administrador` | Comissão e administradores | Adicional |
| `Cargo` | Catálogo do Anexo I | Edital |
| `Inscricao` | Inscrição do candidato em um cargo | Edital |
| `InscricaoVersao` | Histórico de cada edição + código/QR do comprovante | Adicional (RNF04, RF02.5) |
| `DocumentoInscricao` | Anexos da inscrição | Edital |
| `Recurso` | Protocolos de recurso e parecer | Edital |
| `LeilaoVaga` | Vagas ofertadas no leilão | Edital |
| `CheckinLeilao` | Conferência documental e desclassificação | Adicional (RF05.2/05.3) |
| `ContratoLeilao` | Contratações (+ `DataDesistencia`) | Edital (+ coluna) |
| `PunicaoCandidato` | Suspensão de 120 dias | Edital |
| `LogAuditoria` | Quem, quando, IP, antes/depois | Adicional (RNF02/04) |

> No Linux/cPanel os nomes de tabela diferenciam maiúsculas de minúsculas. Use exatamente os nomes do `schema.sql`.

## 8. Regras de negócio: onde cada uma será aplicada

| Regra | Onde |
|---|---|
| RF01.2 (idade ≥ 18) | `Candidato::verificarIdadeMinima()` |
| RN01 (máx. 2 inscrições por CPF) | `InscricaoDAO`, contagem com `SELECT … FOR UPDATE` dentro da transação |
| RN02 (editar até 10/01 23:59) | `InscricaoService` + `InscricaoDAO`, usando `prazos.inscricao_fim` |
| RN03 (sem troca de cargo em recurso) | `InscricaoDAO`, ao detectar recurso na inscrição |
| RN04 (suspensão de 120 dias) | `PunicaoCandidatoDAO::bloqueioAtivo()` |
| RN05 / RF03.1 (classificação) | `MotorClassificacao`: escolaridade → tempo de serviço → titulação → data de nascimento (mais velho primeiro) |
| RNF02 / RNF04 (logs) | `BaseDAO::auditar()` + `LogAuditoriaDAO` + `InscricaoVersao` |

## 9. Fluxos de uso

**Cadastro de candidato**
```php
require_once 'autoload.php';

$c = new Candidato();
$c->setNome($_POST['nome']);
$c->setEmail($_POST['email']);
$c->setCpf($_POST['cpf']);                  // valida CPF
$c->setDataNascimento($_POST['nascimento']);
if (!$c->verificarIdadeMinima()) { /* recusar: menor de 18 */ }
$c->definirSenha($_POST['senha']);          // gera o hash

try {
    (new CandidatoDAO())->inserir($c);
} catch (DomainException $e) {
    echo $e->getMessage();                  // "CPF ou e-mail já cadastrado."
}
```

**Login de candidato**
```php
$c = (new CandidatoDAO())->buscarPorCpf($_POST['cpf']);
if ($c && $c->isAtivo() && $c->login($_POST['senha'])) {
    header('Location: painel.php');
} else {
    echo 'CPF ou senha inválidos.';         // mesma mensagem nos dois casos
}
```

**Página restrita**
```php
Sessao::exigir('admin');
```

## 10. Próximas classes (a implementar)

| Camada | Classe | Papel |
|---|---|---|
| Model | `Inscricao`, `DocumentoInscricao`, `Recurso`, `LeilaoVaga`, `ContratoLeilao`, `PunicaoCandidato` | Entidades restantes do edital |
| DAO | `InscricaoDAO`, `InscricaoVersaoDAO`, `DocumentoInscricaoDAO`, `RecursoDAO`, `LeilaoVagaDAO`, `CheckinLeilaoDAO`, `ContratoLeilaoDAO`, `PunicaoCandidatoDAO` | Persistência de cada tabela |
| Service | `AuthService` | Login de candidato (CPF) e admin (e-mail) |
| Service | `InscricaoService` | RN01 a RN03, versão, comprovante |
| Service | `MotorClassificacao` | Classificação preliminar e definitiva |
| Service | `LeilaoService` | Check-in, desclassificação, atribuição, desistência e punição |
| Service | `PdfGenerator` | Comprovante com QR Code e relatórios de classificação |