<?php
// Cabeçalho da área administrativa. Espera: $tituloPagina (opcional)
$tituloPagina = $tituloPagina ?? 'Administração';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title><?= e($tituloPagina) ?> – Administração – <?= e(SITE_NOME) ?></title>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<a class="pular" href="#conteudo">Ir para o conteúdo</a>
<header class="topo">
  <div class="container topo-barra">
    <a class="marca" href="<?= e(url('admin/index.php')) ?>">Administração</a>
    <nav aria-label="Menu administrativo">
      <a href="<?= e(url('admin/index.php')) ?>">Painel</a>
      <a href="<?= e(url('admin/inscricoes.php')) ?>">Inscrições</a>
      <a href="<?= e(url('admin/classificacao.php')) ?>">Classificação</a>
      <a href="<?= e(url('admin/recursos.php')) ?>">Recursos</a>
      <a href="<?= e(url('admin/leiloes.php')) ?>">Leilões</a>
      <a href="<?= e(url('admin/punicoes.php')) ?>">Suspensões</a>
      <a href="<?= e(url('admin/cargos.php')) ?>">Cargos</a>
      <a href="<?= e(url('admin/configuracao.php')) ?>">Configurações</a>
      <a href="<?= e(url('admin/auditoria.php')) ?>">Auditoria</a>
      <a href="<?= e(url('admin/admins.php')) ?>">Administradores</a>
      <a href="<?= e(url('index.php')) ?>">Site</a>
      <a href="<?= e(url('admin/logout.php')) ?>">Sair</a>
    </nav>
  </div>
</header>
<main id="conteudo" class="container">
<?php if (Calendario::simulada()): ?>
  <div class="alerta aviso">Modo de teste: data/hora simulada = <?= e(dataHoraBr(Calendario::agora()->format('Y-m-d H:i:s'))) ?> (altere em Configurações).</div>
<?php endif; ?>
<?php foreach (flashes() as $_f): ?>
  <div class="alerta <?= e($_f[0]) ?>" role="alert"><?= e($_f[1]) ?></div>
<?php endforeach; ?>
