<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['responsavel_id'])) {
    header('Location: login.php?tipo=responsavel');
    exit;
}

$id   = $_SESSION['responsavel_id'];
$nome = $_SESSION['responsavel_nome'];
$primeiroNome = explode(' ', $nome)[0];

// Buscar alunos vinculados
$stmt = $pdo->prepare("
    SELECT a.id, a.nome, a.ano_escolar, a.plano, a.msgs_hoje, a.ultima_msg_data,
           (SELECT COUNT(*) FROM conversas c WHERE c.aluno_id = a.id) as total_msgs
    FROM alunos a
    INNER JOIN aluno_responsavel ar ON ar.aluno_id = a.id
    WHERE ar.responsavel_id = ?
");
$stmt->execute([$id]);
$alunos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel do Responsável — Aprendzap ⚡</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Righteous&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;background:linear-gradient(135deg,#0a0f2e 0%,#0d1b4b 100%);font-family:'Nunito',sans-serif;color:#fff}
header{display:flex;align-items:center;justify-content:space-between;padding:18px 28px;border-bottom:1px solid rgba(255,255,255,0.1);background:rgba(0,0,0,0.2);backdrop-filter:blur(10px);position:sticky;top:0;z-index:100}
.logo{font-family:'Righteous',cursive;font-size:22px}.logo span{color:#f0c040}
.logout{background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:10px;padding:7px 14px;color:rgba(255,255,255,0.6);font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:all .2s}
.logout:hover{color:#fff}
main{max-width:860px;margin:0 auto;padding:32px 20px}
.boas-vindas{margin-bottom:32px}
.boas-vindas h2{font-size:26px;font-weight:900;margin-bottom:6px}
.boas-vindas p{color:rgba(255,255,255,0.55);font-size:15px}
.badge-resp{display:inline-block;background:rgba(77,166,255,0.15);border:1px solid rgba(77,166,255,0.4);border-radius:20px;padding:4px 14px;font-size:12px;font-weight:700;color:#4da6ff;margin-bottom:20px}
.secao-titulo{font-size:16px;font-weight:800;margin-bottom:16px;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:1px}
.aluno-card{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:20px;padding:24px;margin-bottom:16px}
.aluno-header{display:flex;align-items:center;gap:14px;margin-bottom:18px}
.aluno-avatar{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#f0c040,#e09020);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:900;color:#0a0f2e;flex-shrink:0}
.aluno-nome{font-size:18px;font-weight:900}
.aluno-ano{font-size:13px;color:rgba(255,255,255,0.5)}
.plano-badge{background:rgba(240,192,64,0.15);border:1px solid rgba(240,192,64,0.4);border-radius:20px;padding:3px 12px;font-size:11px;font-weight:700;color:#f0c040;text-transform:uppercase;margin-left:auto}
.aluno-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.mini-stat{background:rgba(255,255,255,0.05);border-radius:12px;padding:14px;text-align:center}
.mini-num{font-size:22px;font-weight:900;color:#f0c040}
.mini-label{font-size:11px;color:rgba(255,255,255,0.4);margin-top:4px}
.vazio{text-align:center;padding:48px 20px;color:rgba(255,255,255,0.3)}
.vazio-emoji{font-size:48px;margin-bottom:16px}
.vazio p{font-size:15px;line-height:1.6}
.vazio a{color:#f0c040;font-weight:700}
.vincular-box{background:rgba(77,166,255,0.08);border:1px solid rgba(77,166,255,0.2);border-radius:16px;padding:20px;margin-top:24px}
.vincular-box h3{font-size:15px;font-weight:800;margin-bottom:8px}
.vincular-box p{font-size:13px;color:rgba(255,255,255,0.5);margin-bottom:14px}
.vincular-form{display:flex;gap:10px;flex-wrap:wrap}
.vincular-form input{flex:1;min-width:200px;background:rgba(255,255,255,0.08);border:2px solid rgba(255,255,255,0.15);border-radius:10px;padding:10px 14px;color:#fff;font-size:14px;font-family:'Nunito',sans-serif;outline:none}
.vincular-form input:focus{border-color:#4da6ff}
.btn-vincular{background:rgba(77,166,255,0.2);border:1px solid rgba(77,166,255,0.4);border-radius:10px;padding:10px 20px;color:#4da6ff;font-size:14px;font-weight:700;cursor:pointer;font-family:'Nunito',sans-serif}
</style>
</head>
<body>

<header>
  <div class="logo">Aprend<span>zap</span> ⚡</div>
  <a href="logout.php" class="logout">Sair</a>
</header>

<main>
  <div class="boas-vindas">
    <div class="badge-resp">👤 Área do Responsável</div>
    <h2>Olá, <?= htmlspecialchars($primeiroNome) ?>! 👋</h2>
    <p>Acompanhe o desempenho dos seus filhos no Aprendzap.</p>
  </div>

  <div class="secao-titulo">👦 Meus filhos</div>

  <?php if(empty($alunos)): ?>
  <div class="vazio">
    <div class="vazio-emoji">🔗</div>
    <p>Nenhum aluno vinculado ainda.<br>Peça ao seu filho para acessar o painel dele<br>e vincular o seu e-mail como responsável.</p>
  </div>
  <?php else: ?>
    <?php foreach($alunos as $a):
      $inicial = strtoupper($a['nome'][0]);
      $msgsHoje = ($a['ultima_msg_data'] === date('Y-m-d')) ? $a['msgs_hoje'] : 0;
    ?>
    <div class="aluno-card">
      <div class="aluno-header">
        <div class="aluno-avatar"><?= $inicial ?></div>
        <div>
          <div class="aluno-nome"><?= htmlspecialchars($a['nome']) ?></div>
          <div class="aluno-ano"><?= htmlspecialchars($a['ano_escolar']) ?></div>
        </div>
        <span class="plano-badge"><?= ucfirst($a['plano']) ?></span>
      </div>
      <div class="aluno-stats">
        <div class="mini-stat">
          <div class="mini-num"><?= $a['total_msgs'] ?></div>
          <div class="mini-label">Total de msgs</div>
        </div>
        <div class="mini-stat">
          <div class="mini-num"><?= $msgsHoje ?></div>
          <div class="mini-label">Msgs hoje</div>
        </div>
        <div class="mini-stat">
          <div class="mini-num"><?= $a['ultima_msg_data'] ? date('d/m', strtotime($a['ultima_msg_data'])) : '—' ?></div>
          <div class="mini-label">Último acesso</div>
        </div>
      </div>
    </div>
    <?php endforeach ?>
  <?php endif ?>

  <!-- Vincular novo aluno -->
  <div class="vincular-box">
    <h3>🔗 Vincular novo aluno</h3>
    <p>Informe o e-mail cadastrado do seu filho para vincular ao seu painel.</p>
    <form method="POST" action="vincular-aluno.php" class="vincular-form">
      <input type="email" name="email_aluno" placeholder="E-mail do aluno" required>
      <button type="submit" class="btn-vincular">Vincular</button>
    </form>
  </div>
</main>

</body>
</html>
