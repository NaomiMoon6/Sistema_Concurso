# Sistema de Inscrição Educacional – Carmo de Minas (PHP OO + MySQL)

## Instalação (cPanel)
1. Envie esta pasta para `projetoeducacao/equipeX` (domínio oficinahotrods.com.br).
2. No phpMyAdmin, selecione o banco `oficinahotrods_equipeX` e importe `config/schema.sql`.
3. Edite `config/config.php`: `DB_NAME`, `DB_USER`, `DB_PASS` e `BASE_URL` (ex.: `/projetoeducacao/equipe1`).
4. A pasta `uploads/` precisa de permissão de escrita (755 ou 775).
5. Acesse `/install.php`, crie o primeiro administrador e **apague o `install.php`** do servidor.
6. Login administrativo: `/admin/login.php`.
Requisitos: PHP 7.4+ (PDO MySQL, fileinfo e iconv – padrão no cPanel).

## Como testar antes de 2027
As datas do edital (08–10/01/2027 etc.) ficam em **Admin > Configurações**. Para testar todas as fases hoje,
preencha "Data/hora simulada" (ex.: 09/01/2027 10:00 para inscrever; 16/01/2027 13:00 para recursos).
Em produção, deixe o campo em branco.

## Estrutura
- `classes/` entidades (Usuario, Candidato, Admin, Cargo, Inscricao, DocumentoInscricao, Recurso, LeilaoVaga,
  ContratoLeilao, PunicaoCandidato, ConferenciaLeilao) + `Titulacao`, `Validador`, `Database`, `DAO`
- `classes/dao/` DAOs (acesso ao banco, PDO com prepared statements; transações ficam nos serviços)
- `classes/servicos/` Sessao, Auditoria, Calendario (prazos), MotorClassificacao, PdfGenerator,
  InscricaoService, RecursoService, LeilaoService (regras de negócio)
- raiz: páginas públicas; `candidato/`: área do candidato; `admin/`: painel administrativo
- `config/schema.sql`: banco completo, cargos e datas iniciais

## Requisitos x onde estão
| Requisito | Onde |
|---|---|
| RF01 cadastro, idade ≥ 18, dados pessoais | `cadastro.php`, `Candidato::validarDados()`, `Validador` |
| RF02.1–02.3 cargos, limite de 2, pontuação, anexos | `candidato/inscricao_form.php`, `InscricaoService::salvar()` |
| RF02.4 / RN02 edição só até 10/01 23h59 | `Calendario::inscricoesAbertas()` |
| RF02.5 comprovante PDF + QR + novo a cada edição | `candidato/comprovante*.php`, `PdfGenerator`, tabela `inscricao_versao` |
| RF03 classificação e desempates, PDF | `MotorClassificacao`, `admin/classificacao.php`, `resultados.php` |
| RF04 recursos, trava de cargo, ajuste, definitiva | `admin/recurso*.php`, `RecursoService`, `InscricaoService::ajusteAdmin()` |
| RF05 leilão, check-in, desclassificação, troca, suspensão 120 dias | `admin/leilao_chamada.php`, `LeilaoService` |
| RNF02/RNF04 LGPD, hash de senha, logs (quem/quando/IP), versões | `Auditoria`, `log_auditoria`, `inscricao_versao`, `password_hash` |

## Decisões e suposições (o PDF não detalha os anexos I, II e III)
- Escolaridade mínima e titulações aceitas de cada cargo são **valores iniciais editáveis** em Admin > Cargos.
- 1º critério de desempate: candidato que atinge a escolaridade mínima do cargo fica à frente dos demais.
  Empate total: ordem de inscrição.
- Tempo de serviço: anos × 365 + dias. Um candidato não pode ter 2 inscrições ativas no mesmo cargo.
- Conferência documental do leilão (Anexo II): checklist dos 7 documentos do anexo de inscrição.
- Suspensão: do dia da desistência até data + 120 dias, inclusive. Troca de vaga (candidato contratado que vence
  outro leilão) gera "desistência declarada" **sem** suspensão.
- Classificação é gravada como "foto" (preliminar/definitiva) e só aparece ao público na data programada.
- O comprovante em PDF é de texto (sem biblioteca externa); o QR Code aparece na versão em tela
  (imagem gerada por api.qrserver.com) e o PDF traz o código e a URL de validação.
