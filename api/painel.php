<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['aluno_id'])) {
    header('Location: /api/login.php');
    exit;
}

$id    = $_SESSION['aluno_id'];
$nome  = $_SESSION['aluno_nome'];
$ano   = $_SESSION['aluno_ano'];
$plano = $_SESSION['aluno_plano'];

// Buscar progresso por disciplina
$stmt = $pdo->prepare("SELECT * FROM progresso WHERE aluno_id = ? ORDER BY total_msgs DESC");
$stmt->execute([$id]);
$progressos = $stmt->fetchAll();

// Limite de mensagens por plano
$limite = $plano === 'gratuito' ? 10 : ($plano === 'essencial' ? 999 : 999);

// Mensagens usadas hoje
$stmt2 = $pdo->prepare("SELECT msgs_hoje, ultima_msg_data FROM alunos WHERE id = ?");
$stmt2->execute([$id]);
$aluno = $stmt2->fetch();
$msgsHoje = ($aluno['ultima_msg_data'] === date('Y-m-d')) ? $aluno['msgs_hoje'] : 0;
$msgsRestantes = max(0, $limite - $msgsHoje);

$disciplinas = [
    ['id'=>'portugues',  'nome'=>'Português',  'emoji'=>'📝'],
    ['id'=>'matematica', 'nome'=>'Matemática',  'emoji'=>'🔢'],
    ['id'=>'redacao',    'nome'=>'Redação',     'emoji'=>'✍️'],
    ['id'=>'ciencias',   'nome'=>'Ciências',    'emoji'=>'🔬'],
    ['id'=>'historia',   'nome'=>'História',    'emoji'=>'🏛️'],
    ['id'=>'geografia',  'nome'=>'Geografia',   'emoji'=>'🌍'],
    ['id'=>'fisica',     'nome'=>'Física',      'emoji'=>'⚡'],
    ['id'=>'quimica',    'nome'=>'Química',     'emoji'=>'🧪'],
    ['id'=>'biologia',   'nome'=>'Biologia',    'emoji'=>'🧬'],
];

$primeiroNome = explode(' ', $nome)[0];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel — Aprendzap ⚡</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Righteous&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;background:linear-gradient(135deg,#0a0f2e 0%,#0d1b4b 100%);font-family:'Nunito',sans-serif;color:#fff}
header{display:flex;align-items:center;justify-content:space-between;padding:18px 28px;border-bottom:1px solid rgba(255,255,255,0.1);background:rgba(0,0,0,0.2);backdrop-filter:blur(10px);position:sticky;top:0;z-index:100}
.logo{font-family:'Righteous',cursive;font-size:22px}.logo span{color:#f0c040}
.header-right{display:flex;align-items:center;gap:14px}
.plano-badge{background:rgba(240,192,64,0.15);border:1px solid rgba(240,192,64,0.4);border-radius:20px;padding:4px 14px;font-size:12px;font-weight:700;color:#f0c040;text-transform:uppercase}
.logout{background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:10px;padding:7px 14px;color:rgba(255,255,255,0.6);font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:all .2s}
.logout:hover{color:#fff;border-color:rgba(255,255,255,0.4)}
main{max-width:900px;margin:0 auto;padding:32px 20px}
.boas-vindas{margin-bottom:32px}
.boas-vindas h2{font-size:26px;font-weight:900;margin-bottom:6px}
.boas-vindas p{color:rgba(255,255,255,0.55);font-size:15px}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:36px}
.stat{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:16px;padding:20px;text-align:center}
.stat-num{font-size:32px;font-weight:900;color:#f0c040;line-height:1}
.stat-label{font-size:12px;color:rgba(255,255,255,0.5);margin-top:6px;font-weight:600}
.secao-titulo{font-size:16px;font-weight:800;margin-bottom:16px;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:1px}
.disciplinas{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:36px}
.disc-card{background:rgba(255,255,255,0.06);border:2px solid rgba(255,255,255,0.1);border-radius:16px;padding:20px 14px;text-align:center;cursor:pointer;transition:all .25s;text-decoration:none;color:#fff;display:block}
.disc-card:hover{border-color:#f0c040;background:rgba(240,192,64,0.1);transform:translateY(-3px)}
.disc-emoji{font-size:32px;margin-bottom:10px}
.disc-nome{font-size:14px;font-weight:800}
.disc-msgs{font-size:11px;color:rgba(255,255,255,0.4);margin-top:4px}
.upgrade-box{background:linear-gradient(135deg,rgba(240,192,64,0.1),rgba(224,144,32,0.15));border:1px solid rgba(240,192,64,0.3);border-radius:20px;padding:24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.upgrade-box h3{font-size:17px;font-weight:900;margin-bottom:6px}
.upgrade-box p{font-size:14px;color:rgba(255,255,255,0.6)}
.btn-upgrade{background:linear-gradient(135deg,#f0c040,#e09020);color:#0a0f2e;border:none;border-radius:12px;padding:12px 24px;font-size:14px;font-weight:900;cursor:pointer;font-family:'Nunito',sans-serif;white-space:nowrap;text-decoration:none;display:inline-block}
.limite-bar{margin-bottom:32px}
.limite-info{display:flex;justify-content:space-between;font-size:13px;color:rgba(255,255,255,0.5);margin-bottom:8px}
.bar{height:8px;background:rgba(255,255,255,0.1);border-radius:4px;overflow:hidden}
.bar-fill{height:100%;background:linear-gradient(90deg,#f0c040,#e09020);border-radius:4px;transition:width .5s}
</style>
</head>
<body>

<header>
  <div class="logo">Aprend<span>zap</span> ⚡</div>
  <div class="header-right">
    <span class="plano-badge"><?= ucfirst($plano) ?></span>
    <a href="logout.php" class="logout">Sair</a>
  </div>
</header>

<main>
  <div class="boas-vindas">
    <h2>Olá, <?= htmlspecialchars($primeiroNome) ?>! 👋</h2>
    <p><?= htmlspecialchars($ano) ?> · O que vamos aprender hoje?</p>
  </div>

  <!-- Estatísticas -->
  <div class="stats">
    <div class="stat">
      <div class="stat-num"><?= count($progressos) ?></div>
      <div class="stat-label">Disciplinas estudadas</div>
    </div>
    <div class="stat">
      <div class="stat-num"><?= array_sum(array_column($progressos,'total_msgs')) ?></div>
      <div class="stat-label">Mensagens com o Zap</div>
    </div>
    <div class="stat">
      <div class="stat-num"><?= $msgsRestantes ?></div>
      <div class="stat-label">Msgs restantes hoje</div>
    </div>
  </div>

  <!-- Barra de limite (plano gratuito) -->
  <?php if($plano === 'gratuito'): ?>
  <div class="limite-bar">
    <div class="limite-info">
      <span>Mensagens hoje</span>
      <span><?= $msgsHoje ?>/<?= $limite ?></span>
    </div>
    <div class="bar">
      <div class="bar-fill" style="width:<?= min(100, ($msgsHoje/$limite)*100) ?>%"></div>
    </div>
  </div>
  <?php endif ?>

  <!-- Disciplinas -->
  <div class="secao-titulo">📚 Escolha uma disciplina</div>
  <div class="disciplinas">
    <?php foreach($disciplinas as $d):
      $prog = array_filter($progressos, fn($p) => $p['disciplina'] === $d['id']);
      $prog = $prog ? array_values($prog)[0] : null;
      $totalMsgs = $prog ? $prog['total_msgs'] : 0;
    ?>
    <a href="tutor.php?disciplina=<?= $d['id'] ?>" class="disc-card">
      <div class="disc-emoji"><?= $d['emoji'] ?></div>
      <div class="disc-nome"><?= $d['nome'] ?></div>
      <div class="disc-msgs"><?= $totalMsgs ?> msgs</div>
    </a>
    <?php endforeach ?>
  </div>

  <!-- Upgrade (plano gratuito) -->
  <?php if($plano === 'gratuito'): ?>
  <div class="upgrade-box">
    <div>
      <h3>🚀 Quer estudar sem limites?</h3>
      <p>Plano Essencial — Zap ilimitado + todas as disciplinas por R$ 49/mês</p>
    </div>
    <a href="https://hotmart.com" target="_blank" class="btn-upgrade">Fazer upgrade</a>
  </div>
  <?php endif ?>
</main>

</body>
</html>
