<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['aluno_id'])) header('Location: /api/painel.php'), exit;
if (isset($_SESSION['responsavel_id'])) header('Location: /api/painel-responsavel.php'), exit;

$erro = '';
$tipo = $_GET['tipo'] ?? 'aluno';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo  = $_POST['tipo'] ?? 'aluno';
    $email = trim(strtolower($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';

    if (!$email || !$senha) {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $tabela = $tipo === 'responsavel' ? 'responsaveis' : 'alunos';
        $stmt = $pdo->prepare("SELECT * FROM `$tabela` WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            if ($tipo === 'aluno') {
                $_SESSION['aluno_id']   = $usuario['id'];
                $_SESSION['aluno_nome'] = $usuario['nome'];
                $_SESSION['aluno_ano']  = $usuario['ano_escolar'];
                $_SESSION['aluno_plano']= $usuario['plano'];
                header('Location: /api/painel.php');
            } else {
                $_SESSION['responsavel_id']   = $usuario['id'];
                $_SESSION['responsavel_nome'] = $usuario['nome'];
                header('Location: /api/painel-responsavel.php');
            }
            exit;
        } else {
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Aprendzap ⚡</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Righteous&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;background:linear-gradient(135deg,#0a0f2e 0%,#0d1b4b 60%,#0a2a6e 100%);font-family:'Nunito',sans-serif;color:#fff;display:flex;align-items:center;justify-content:center;padding:20px}
.card{width:100%;max-width:420px;background:rgba(255,255,255,0.06);border-radius:24px;padding:36px;border:1px solid rgba(255,255,255,0.12)}
.logo{text-align:center;margin-bottom:28px}
.logo h1{font-family:'Righteous',cursive;font-size:32px}.logo span{color:#f0c040}
.logo p{color:rgba(255,255,255,0.5);font-size:14px;margin-top:6px}
.tabs{display:flex;gap:8px;margin-bottom:24px;background:rgba(255,255,255,0.05);border-radius:12px;padding:4px}
.tab{flex:1;padding:10px;border:none;border-radius:10px;font-family:'Nunito',sans-serif;font-weight:700;font-size:14px;cursor:pointer;transition:all .2s;background:transparent;color:rgba(255,255,255,0.5)}
.tab.ativo{background:linear-gradient(135deg,#f0c040,#e09020);color:#0a0f2e}
.campo{margin-bottom:18px}
label{display:block;font-size:12px;font-weight:700;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:1px;margin-bottom:7px}
input{width:100%;background:rgba(255,255,255,0.08);border:2px solid rgba(255,255,255,0.15);border-radius:12px;padding:13px 15px;color:#fff;font-size:15px;font-family:'Nunito',sans-serif;transition:border-color .2s;outline:none}
input:focus{border-color:#f0c040}
.btn{width:100%;padding:15px;background:linear-gradient(135deg,#f0c040,#e09020);color:#0a0f2e;border:none;border-radius:14px;font-size:16px;font-weight:900;cursor:pointer;font-family:'Nunito',sans-serif;margin-top:8px;transition:all .2s}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(240,192,64,0.4)}
.erro{background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.4);border-radius:10px;padding:12px 15px;font-size:14px;color:#fca5a5;margin-bottom:16px}
.link{text-align:center;margin-top:16px;font-size:14px;color:rgba(255,255,255,0.5)}
.link a{color:#f0c040;text-decoration:none;font-weight:700}
.recuperar{text-align:right;margin-top:-10px;margin-bottom:18px}
.recuperar a{font-size:13px;color:rgba(255,255,255,0.4);text-decoration:none}
.recuperar a:hover{color:#f0c040}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <h1>Aprend<span>zap</span> ⚡</h1>
    <p>Acesse sua conta</p>
  </div>

  <div class="tabs">
    <button class="tab <?= $tipo==='aluno'?'ativo':'' ?>" onclick="location.href='login.php?tipo=aluno'">Sou Aluno</button>
    <button class="tab <?= $tipo==='responsavel'?'ativo':'' ?>" onclick="location.href='login.php?tipo=responsavel'">Sou Responsável</button>
  </div>

  <?php if($erro): ?><div class="erro">⚠️ <?= htmlspecialchars($erro) ?></div><?php endif ?>

  <form method="POST">
    <input type="hidden" name="tipo" value="<?= $tipo ?>">

    <div class="campo">
      <label>E-mail</label>
      <input type="email" name="email" placeholder="seu@email.com" required autofocus value="<?= htmlspecialchars($_POST['email']??'') ?>">
    </div>

    <div class="campo">
      <label>Senha</label>
      <input type="password" name="senha" placeholder="Sua senha" required>
    </div>

    <div class="recuperar"><a href="recuperar-senha.php">Esqueci minha senha</a></div>

    <button type="submit" class="btn">Entrar ⚡</button>
  </form>

  <div class="link">Não tem conta? <a href="cadastro.php?tipo=<?= $tipo ?>">Cadastre-se grátis</a></div>
</div>
</body>
</html>
