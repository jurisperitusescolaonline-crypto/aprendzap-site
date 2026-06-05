<?php
session_start();
require_once 'db.php';

$erro = '';
$sucesso = '';
$etapa = $_GET['etapa'] ?? 'email'; // email | nova
$token = $_GET['token'] ?? '';

// Etapa 2: redefinir senha com token
if ($etapa === 'nova' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $conf  = $_POST['confirmar_senha'] ?? '';

    if (!$token || !$senha) {
        $erro = 'Dados inválidos.';
    } elseif ($senha !== $conf) {
        $erro = 'As senhas não conferem.';
    } elseif (strlen($senha) < 6) {
        $erro = 'Mínimo 6 caracteres.';
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $ok = false;
        foreach (['alunos','responsaveis'] as $tabela) {
            $stmt = $pdo->prepare("SELECT id FROM `$tabela` WHERE token_recuperar = ? AND token_expira > NOW()");
            $stmt->execute([$token]);
            if ($u = $stmt->fetch()) {
                $pdo->prepare("UPDATE `$tabela` SET senha = ?, token_recuperar = NULL, token_expira = NULL WHERE id = ?")
                    ->execute([$hash, $u['id']]);
                $ok = true;
                break;
            }
        }
        if ($ok) {
            $sucesso = 'Senha redefinida com sucesso!';
            $etapa = 'concluido';
        } else {
            $erro = 'Link inválido ou expirado. Solicite um novo.';
        }
    }
}

// Etapa 1: enviar e-mail
if ($etapa === 'email' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    if (!$email) {
        $erro = 'Informe o e-mail.';
    } else {
        $token_gerado = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $encontrado = false;
        foreach (['alunos','responsaveis'] as $tabela) {
            $stmt = $pdo->prepare("SELECT id FROM `$tabela` WHERE email = ? AND ativo = 1");
            $stmt->execute([$email]);
            if ($u = $stmt->fetch()) {
                $pdo->prepare("UPDATE `$tabela` SET token_recuperar = ?, token_expira = ? WHERE id = ?")
                    ->execute([$token_gerado, $expira, $u['id']]);
                $link = "https://aprendzap.com.br/recuperar-senha.php?etapa=nova&token=$token_gerado";
                // Envio de e-mail (configurar SMTP na Hostinger)
                $assunto = "Aprendzap — Redefinição de senha";
                $msg = "Olá!\n\nClique no link abaixo para redefinir sua senha (válido por 1 hora):\n\n$link\n\nSe não foi você, ignore este e-mail.\n\nEquipe Aprendzap";
                mail($email, $assunto, $msg, "From: contato@aprendzap.com.br\r\nContent-Type: text/plain; charset=utf-8");
                $encontrado = true;
                break;
            }
        }
        // Sempre mostrar sucesso (segurança)
        $sucesso = 'Se este e-mail estiver cadastrado, você receberá as instruções em breve.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar senha — Aprendzap ⚡</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Righteous&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;background:linear-gradient(135deg,#0a0f2e 0%,#0d1b4b 60%,#0a2a6e 100%);font-family:'Nunito',sans-serif;color:#fff;display:flex;align-items:center;justify-content:center;padding:20px}
.card{width:100%;max-width:400px;background:rgba(255,255,255,0.06);border-radius:24px;padding:36px;border:1px solid rgba(255,255,255,0.12)}
.logo{text-align:center;margin-bottom:28px}
.logo h1{font-family:'Righteous',cursive;font-size:28px}.logo span{color:#f0c040}
.logo p{color:rgba(255,255,255,0.5);font-size:14px;margin-top:6px}
.campo{margin-bottom:18px}
label{display:block;font-size:12px;font-weight:700;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:1px;margin-bottom:7px}
input{width:100%;background:rgba(255,255,255,0.08);border:2px solid rgba(255,255,255,0.15);border-radius:12px;padding:13px 15px;color:#fff;font-size:15px;font-family:'Nunito',sans-serif;outline:none;transition:border-color .2s}
input:focus{border-color:#f0c040}
.btn{width:100%;padding:15px;background:linear-gradient(135deg,#f0c040,#e09020);color:#0a0f2e;border:none;border-radius:14px;font-size:16px;font-weight:900;cursor:pointer;font-family:'Nunito',sans-serif;margin-top:8px}
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
    <p><?= $etapa === 'nova' ? 'Criar nova senha' : 'Recuperar senha' ?></p>
  </div>

  <?php if($erro): ?><div class="erro">⚠️ <?= htmlspecialchars($erro) ?></div><?php endif ?>
  <?php if($sucesso): ?><div class="sucesso">✅ <?= $sucesso ?></div><?php endif ?>

  <?php if($etapa === 'email' && !$sucesso): ?>
  <form method="POST">
    <div class="campo">
      <label>Seu e-mail cadastrado</label>
      <input type="email" name="email" placeholder="seu@email.com" required autofocus>
    </div>
    <button type="submit" class="btn">Enviar instruções</button>
  </form>

  <?php elseif($etapa === 'nova' && !$sucesso && $token): ?>
  <form method="POST">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <div class="campo">
      <label>Nova senha</label>
      <input type="password" name="senha" placeholder="Mínimo 6 caracteres" required autofocus>
    </div>
    <div class="campo">
      <label>Confirmar nova senha</label>
      <input type="password" name="confirmar_senha" placeholder="Repita a senha" required>
    </div>
    <button type="submit" class="btn">Redefinir senha</button>
  </form>
  <?php endif ?>

  <div class="link"><a href="login.php">← Voltar ao login</a></div>
</div>
</body>
</html>
