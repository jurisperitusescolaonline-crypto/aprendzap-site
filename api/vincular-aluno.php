<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['responsavel_id'])) {
    header('Location: login.php?tipo=responsavel');
    exit;
}

$respId = $_SESSION['responsavel_id'];
$emailAluno = trim(strtolower($_POST['email_aluno'] ?? ''));
$erro = '';
$sucesso = '';

if (!$emailAluno) {
    $erro = 'Informe o e-mail do aluno.';
} else {
    $stmt = $pdo->prepare("SELECT id, nome FROM alunos WHERE email = ? AND ativo = 1");
    $stmt->execute([$emailAluno]);
    $aluno = $stmt->fetch();

    if (!$aluno) {
        $erro = 'Aluno não encontrado. Verifique o e-mail.';
    } else {
        // Verificar se já está vinculado
        $stmt2 = $pdo->prepare("SELECT id FROM aluno_responsavel WHERE aluno_id = ? AND responsavel_id = ?");
        $stmt2->execute([$aluno['id'], $respId]);
        if ($stmt2->fetch()) {
            $erro = 'Este aluno já está vinculado à sua conta.';
        } else {
            $pdo->prepare("INSERT INTO aluno_responsavel (aluno_id, responsavel_id) VALUES (?, ?)")
                ->execute([$aluno['id'], $respId]);
            $sucesso = "Aluno {$aluno['nome']} vinculado com sucesso!";
        }
    }
}

// Redirecionar de volta ao painel com mensagem
$param = $erro ? 'erro='.urlencode($erro) : 'sucesso='.urlencode($sucesso);
header("Location: /api/painel-responsavel.php?$param");
exit;
