<?php
session_start();
require_once 'db.php';

$erro = '';
$sucesso = '';
$tipo = $_GET['tipo'] ?? 'aluno'; // aluno | responsavel

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo  = $_POST['tipo'] ?? 'aluno';
    $nome  = trim($_POST['nome'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';
    $conf  = $_POST['confirmar_senha'] ?? '';
    $ano   = $_POST['ano_escolar'] ?? '';

    if (!$nome || !$email || !$senha) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $conf) {
        $erro = 'As senhas não conferem.';
    } elseif ($tipo === 'aluno' && !$ano) {
        $erro = 'Selecione o ano escolar.';
    } else {
        $tabela = $tipo === 'responsavel' ? 'responsaveis' : 'alunos';
        $stmt = $pdo->prepare("SELECT id FROM `$tabela` WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            if ($tipo === 'aluno') {
                $pdo->prepare("INSERT INTO alunos (nome, email, senha, ano_escolar) VALUES (?, ?, ?, ?)")
                    ->execute([$nome, $email, $hash, $ano]);
            } else {
                $tel = trim($_POST['telefone'] ?? '');
                $pdo->prepare("INSERT INTO responsaveis (nome, email, senha, telefone) VALUES (?, ?, ?, ?)")
                    ->execute([$nome, $email, $hash, $tel]);
            }
            $sucesso = 'Cadastro realizado com sucesso!';
        }
    }
}

$anos = ['1º Ano Fund.','2º Ano Fund.','3º Ano Fund.','4º Ano Fund.','5º Ano Fund.',
         '6º Ano Fund.','7º Ano Fund.','8º Ano Fund.','9º Ano Fund.',
         '1º Ano Médio','2º Ano Médio','3º Ano Médio'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro — Aprendzap ⚡</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Righteous&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;background:linear-gradient(135deg,#0a0f2e 0%,#0d1b4b 60%,#0a2a6e 100%);font-family:'Nunito',sans-serif;color:#fff;display:flex;align-items:center;justify-content:center;padding:20px}
.card{width:100%;max-width:460px;background:rgba(255,255,255,0.06);border-radius:24px;padding:36px;border:1px solid rgba(255,255,255,0.12)}
.logo{text-align:center;margin-bottom:28px}
.logo h1{font-family:'Righteous',cursive;font-size:32px}.logo span{color:#f0c040}
.logo p{color:rgba(255,255,255,0.5);font-size:14px;margin-top:6px}
.tabs{display:flex;gap:8px;margin-bottom:24px;background:rgba(255,255,255,0.05);border-radius:12px;padding:4px}
.tab{flex:1;padding:10px;border:none;border-radius:10px;font-family:'Nunito',sans-serif;font-weight:700;font-size:14px;cursor:pointer;transition:all .2s;background:transparent;color:rgba(255,255,255,0.5)}
.tab.ativo{background:linear-gradient(135deg,#f0c040,#e09020);color:#0a0f2e}
.campo{margin-bottom:18px}
label{display:block;font-size:12px;font-weight:700;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:1px;margin-bottom:7px}
input,select{width:100%;background:rgba(255,255,255,0.08);border:2px solid rgba(255,255,255,0.15);border-radius:12px;padding:13px 15px;color:#fff;font-size:15px;font-family:'Nunito',sans-serif;transition:border-color .2s;outline:none}
input:focus,select:focus{border-color:#f0c040}
select option{background:#0d1b4b;color:#fff}
.btn{width:100%;padding:15px;background:linear-gradient(135deg,#f0c040,#e09020);color:#0a0f2e;border:none;border-radius:14px;font-size:16px;font-weight:900;cursor:pointer;font-family:'Nunito',sans-serif;margin-top:8px;transition:all .2s}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(240,192,64,0.4)}
.erro{background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.4);border-radius:10px;padding:12px 15px;font-size:14px;color:#fca5a5;margin-bottom:16px}
.sucesso{background:rgba(34,197,94,0.15);border:1px solid rgba(34,197,94,0.4);border-radius:10px;padding:12px 15px;font-size:14px;color:#86efac;margin-bottom:16px}
.link{text-align:center;margin-top:20px;font-size:14px;color:rgba(255,255,255,0.5)}
.link a{color:#f0c040;text-decoration:none;font-weight:700}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <h1>Aprend<span>zap</span> ⚡</h1>
    <p>Crie sua conta gratuita</p>
  </div>

  <div class="tabs">
    <button class="tab <?= $tipo==='aluno'?'ativo':'' ?>" onclick="location.href='/api/cadastro.php?tipo=aluno'">Sou Aluno</button>
    <button class="tab <?= $tipo==='responsavel'?'ativo':'' ?>" onclick="location.href='/api/cadastro.php?tipo=responsavel'">Sou Responsável</button>
  </div>

  <?php if($erro): ?><div class="erro">⚠️ <?= htmlspecialchars($erro) ?></div><?php endif ?>
  <?php if($sucesso): ?>
    <div class="sucesso">✅ <?= $sucesso ?> <a href="login.php" style="color:#86efac;font-weight:700">Fazer login →</a></div>
  <?php else: ?>

  <form method="POST">
    <input type="hidden" name="tipo" value="<?= $tipo ?>">

    <div class="campo">
      <label>Nome completo</label>
      <input type="text" name="nome" placeholder="Seu nome" required value="<?= htmlspecialchars($_POST['nome']??'') ?>">
    </div>

    <div class="campo">
      <label>E-mail</label>
      <input type="email" name="email" placeholder="seu@email.com" required value="<?= htmlspecialchars($_POST['email']??'') ?>">
    </div>

    <?php if($tipo === 'aluno'): ?>
    <div class="campo">
      <label>Ano escolar</label>
      <select name="ano_escolar" required>
        <option value="">Selecione...</option>
        <?php foreach($anos as $a): ?>
        <option value="<?= $a ?>" <?= ($_POST['ano_escolar']??'')===$a?'selected':'' ?>><?= $a ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <?php else: ?>
    <div class="campo">
      <label>Telefone / WhatsApp</label>
      <input type="tel" name="telefone" placeholder="(61) 99999-9999" value="<?= htmlspecialchars($_POST['telefone']??'') ?>">
    </div>
    <?php endif ?>

    <div class="campo">
      <label>Senha</label>
      <input type="password" name="senha" placeholder="Mínimo 6 caracteres" required>
    </div>

    <div class="campo">
      <label>Confirmar senha</label>
      <input type="password" name="confirmar_senha" placeholder="Repita a senha" required>
    </div>

    <button type="submit" class="btn">Criar conta grátis ⚡</button>
  </form>
  <?php endif ?>

  <div class="link">Já tem conta? <a href="login.php">Fazer login</a></div>
</div>
</body>
</html>
