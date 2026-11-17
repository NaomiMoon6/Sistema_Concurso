-- Sistema de Inscrição – Carmo de Minas
-- Atenção: no Linux/cPanel os nomes de tabela diferenciam maiúsculas/minúsculas.
SET NAMES utf8mb4;

CREATE TABLE Candidato (
  ID             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CPF            CHAR(11)     NOT NULL,
  Nome           VARCHAR(150) NOT NULL,
  DataNascimento DATE         NOT NULL,
  Telefone       VARCHAR(15)  NULL,
  Email          VARCHAR(150) NOT NULL,
  Endereco       VARCHAR(255) NULL,
  RG             VARCHAR(20)  NULL,          -- RF01.3 (extra)
  TituloEleitor  VARCHAR(14)  NULL,          -- RF01.3 (extra)
  SenhaHash      VARCHAR(255) NOT NULL,
  Ativo          TINYINT(1)   NOT NULL DEFAULT 1,  -- soft delete (extra)
  DataCriacao    DATETIME     NOT NULL,
  PRIMARY KEY (ID),
  UNIQUE KEY uq_candidato_cpf (CPF),
  UNIQUE KEY uq_candidato_email (Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Administrador (               -- (extra) não está na lista do edital
  ID          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  Nome        VARCHAR(150) NOT NULL,
  Email       VARCHAR(150) NOT NULL,
  SenhaHash   VARCHAR(255) NOT NULL,
  Perfil      ENUM('admin','comissao') NOT NULL DEFAULT 'comissao',
  Ativo       TINYINT(1)   NOT NULL DEFAULT 1,
  DataCriacao DATETIME     NOT NULL,
  PRIMARY KEY (ID),
  UNIQUE KEY uq_admin_email (Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Cargo (
  ID                        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  TituloCargo               VARCHAR(120) NOT NULL,
  EscolaridadeMinimaExigida VARCHAR(60)  NOT NULL,
  Ativo                     TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (ID),
  UNIQUE KEY uq_cargo_titulo (TituloCargo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Inscricao (
  ID               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CandidatoID      INT UNSIGNED NOT NULL,
  CargoID          INT UNSIGNED NOT NULL,
  TempoServicoDias INT UNSIGNED NOT NULL DEFAULT 0,
  NivelTitulacao   VARCHAR(30)  NOT NULL,
  DataInscricao    DATETIME     NOT NULL,
  UltimaAlteracao  DATETIME     NOT NULL,
  Status           ENUM('Ativa','Cancelada','Desclassificada') NOT NULL DEFAULT 'Ativa',
  PRIMARY KEY (ID),
  KEY idx_insc_candidato (CandidatoID, Status),
  KEY idx_insc_cargo (CargoID, Status),
  CONSTRAINT fk_insc_candidato FOREIGN KEY (CandidatoID) REFERENCES Candidato (ID),
  CONSTRAINT fk_insc_cargo     FOREIGN KEY (CargoID)     REFERENCES Cargo (ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- RNF04 + RF02.5: um registro por edição; o código vira o QR Code do comprovante (extra)
CREATE TABLE InscricaoVersao (
  ID              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  InscricaoID     INT UNSIGNED NOT NULL,
  Versao          INT UNSIGNED NOT NULL,
  DadosJson       LONGTEXT     NOT NULL,
  CodigoValidacao CHAR(32)     NOT NULL,
  DataRegistro    DATETIME     NOT NULL,
  IP              VARCHAR(45)  NULL,
  PRIMARY KEY (ID),
  UNIQUE KEY uq_versao (InscricaoID, Versao),
  UNIQUE KEY uq_codigo (CodigoValidacao),
  CONSTRAINT fk_versao_insc FOREIGN KEY (InscricaoID) REFERENCES Inscricao (ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE DocumentoInscricao (
  ID             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  InscricaoID    INT UNSIGNED NOT NULL,
  TipoDocumento  VARCHAR(40)  NOT NULL,
  CaminhoArquivo VARCHAR(255) NOT NULL,
  DataUpload     DATETIME     NOT NULL,
  PRIMARY KEY (ID),
  KEY idx_doc_insc (InscricaoID),
  CONSTRAINT fk_doc_insc FOREIGN KEY (InscricaoID) REFERENCES Inscricao (ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Recurso (
  ID                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  InscricaoID        INT UNSIGNED NOT NULL,
  NumeroProtocolo    VARCHAR(30)  NOT NULL,
  DataProtocolo      DATETIME     NOT NULL,
  Descricao          TEXT         NOT NULL,
  ParecerDeferimento ENUM('Pendente','Deferido','Indeferido') NOT NULL DEFAULT 'Pendente',
  DataParecer        DATETIME     NULL,
  PRIMARY KEY (ID),
  UNIQUE KEY uq_protocolo (NumeroProtocolo),
  KEY idx_rec_insc (InscricaoID),
  CONSTRAINT fk_rec_insc FOREIGN KEY (InscricaoID) REFERENCES Inscricao (ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE LeilaoVaga (
  ID             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CargoID        INT UNSIGNED NOT NULL,
  Escolaridade   VARCHAR(60)  NOT NULL,
  Horario        DATETIME     NOT NULL,
  VagasOfertadas INT UNSIGNED NOT NULL DEFAULT 1,
  Status         ENUM('Aberto','Encerrado') NOT NULL DEFAULT 'Aberto',
  PRIMARY KEY (ID),
  KEY idx_leilao_cargo (CargoID),
  CONSTRAINT fk_leilao_cargo FOREIGN KEY (CargoID) REFERENCES Cargo (ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- RF05.2 / RF05.3: conferência documental e desclassificação (extra)
CREATE TABLE CheckinLeilao (
  ID                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  LeilaoID             INT UNSIGNED NOT NULL,
  InscricaoID          INT UNSIGNED NOT NULL,
  AvaliadorID          INT UNSIGNED NOT NULL,
  DocumentosConferidos TINYINT(1)   NOT NULL DEFAULT 0,
  Resultado            ENUM('Apto','Desclassificado') NOT NULL,
  Motivo               VARCHAR(255) NULL,
  DataCheckin          DATETIME     NOT NULL,
  PRIMARY KEY (ID),
  UNIQUE KEY uq_checkin (LeilaoID, InscricaoID),
  CONSTRAINT fk_chk_leilao FOREIGN KEY (LeilaoID)    REFERENCES LeilaoVaga (ID),
  CONSTRAINT fk_chk_insc   FOREIGN KEY (InscricaoID) REFERENCES Inscricao (ID),
  CONSTRAINT fk_chk_aval   FOREIGN KEY (AvaliadorID) REFERENCES Administrador (ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ContratoLeilao (
  ID              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  LeilaoID        INT UNSIGNED NOT NULL,
  CandidatoID     INT UNSIGNED NOT NULL,
  DataContratacao DATE         NOT NULL,
  Status          ENUM('Ativo','Desistido','Encerrado') NOT NULL DEFAULT 'Ativo',
  DataDesistencia DATE         NULL,        -- base da RN04 (extra)
  PRIMARY KEY (ID),
  KEY idx_ctr_candidato (CandidatoID, Status),
  CONSTRAINT fk_ctr_leilao    FOREIGN KEY (LeilaoID)    REFERENCES LeilaoVaga (ID),
  CONSTRAINT fk_ctr_candidato FOREIGN KEY (CandidatoID) REFERENCES Candidato (ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE PunicaoCandidato (
  ID                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CandidatoID       INT UNSIGNED NOT NULL,
  CargoID           INT UNSIGNED NOT NULL,
  DataInicioPunicao DATE         NOT NULL,
  DataFimPunicao    DATE         NOT NULL,   -- início + 120 dias
  Motivo            VARCHAR(255) NOT NULL,
  PRIMARY KEY (ID),
  KEY idx_pun (CandidatoID, CargoID, DataFimPunicao),
  CONSTRAINT fk_pun_candidato FOREIGN KEY (CandidatoID) REFERENCES Candidato (ID),
  CONSTRAINT fk_pun_cargo     FOREIGN KEY (CargoID)     REFERENCES Cargo (ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- RNF02/RNF04 (extra)
CREATE TABLE LogAuditoria (
  ID          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  UsuarioTipo VARCHAR(15)  NOT NULL,          -- candidato | admin | anonimo
  UsuarioID   INT UNSIGNED NULL,
  Acao        VARCHAR(30)  NOT NULL,
  Tabela      VARCHAR(40)  NOT NULL,
  RegistroID  INT UNSIGNED NULL,
  DadosAntes  LONGTEXT     NULL,
  DadosDepois LONGTEXT     NULL,
  IP          VARCHAR(45)  NULL,
  DataHora    DATETIME     NOT NULL,
  PRIMARY KEY (ID),
  KEY idx_log_registro (Tabela, RegistroID),
  KEY idx_log_usuario (UsuarioTipo, UsuarioID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cargos do Anexo I. CONFIRMAR as escolaridades mínimas no edital.
INSERT INTO Cargo (TituloCargo, EscolaridadeMinimaExigida) VALUES
 ('Servente Escolar',                                   'Ensino Fundamental'),
 ('Educador Infantil',                                  'Ensino Médio'),
 ('Inspetor de Alunos',                                 'Ensino Médio'),
 ('Auxiliar de Secretaria',                             'Ensino Médio'),
 ('Professor de Educação Infantil',                     'Ensino Superior'),
 ('Professor dos Anos Iniciais do Ensino Fundamental',  'Ensino Superior'),
 ('Professor de Educação Física',                       'Ensino Superior'),
 ('Especialista da Educação (Orientador Educacional)',  'Ensino Superior'),
 ('Especialista da Educação (Supervisor Pedagógico)',   'Ensino Superior'),
 ('Psicopedagogo',                                      'Ensino Superior');
