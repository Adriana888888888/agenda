<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$db = new PDO("sqlite:base_dados.sqlite");

// Criar tabela se não existir
$db->exec("CREATE TABLE IF NOT EXISTS marcacoes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nome TEXT,
  servico TEXT,
  data TEXT,
  hora TEXT,
  contacto TEXT
)");

$data = $_POST['data'];
$hora = $_POST['hora'];
$diaSemana = date('w', strtotime($data)); // 0 = domingo, 1 = segunda

$mensagem = "";
$sucesso = false;

if ($diaSemana == 0 || $diaSemana == 1) {
  $mensagem = "❌ Não aceitamos marcações ao domingo nem à segunda-feira.";
} else {
  $verificar = $db->prepare("SELECT COUNT(*) FROM marcacoes WHERE data = :data AND hora = :hora");
  $verificar->bindParam(':data', $data);
  $verificar->bindParam(':hora', $hora);
  $verificar->execute();
  $existe = $verificar->fetchColumn();

  if ($existe > 0) {
    $mensagem = "❌ Já existe uma marcação para <strong>$data às $hora</strong>.";
  } else {
    // Gravar marcação
    $stmt = $db->prepare("INSERT INTO marcacoes (nome, servico, data, hora, contacto)
                          VALUES (:nome, :servico, :data, :hora, :contacto)");
    $stmt->bindParam(':nome', $_POST['nome']);
    $stmt->bindParam(':servico', $_POST['servico']);
    $stmt->bindParam(':data', $data);
    $stmt->bindParam(':hora', $hora);
    $stmt->bindParam(':contacto', $_POST['contacto']);
    $stmt->execute();

    // Enviar email
    $mail = new PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->Host = 'smtp.gmail.com';
      $mail->SMTPAuth = true;
      $mail->Username = 'adrianasandra90@gmail.com'; // Conta Gmail de envio
      $mail->Password = 'kwvznaorcojjxlbb'; // Palavra-passe de aplicação (sem espaços)
      $mail->SMTPSecure = 'tls';
      $mail->Port = 587;

      $mail->setFrom('adrianasandra90@gmail.com', 'AM Estética');
      $mail->addAddress('adrianasandra90@gmail.com'); // Email que recebe as marcações

      $mail->isHTML(true);
      $mail->Subject = '🗓️ Nova marcação no site AM Estética';
      $mail->Body = "
        <h2>Nova Marcação</h2>
        <p><strong>Nome:</strong> {$_POST['nome']}</p>
        <p><strong>Serviço:</strong> {$_POST['servico']}</p>
        <p><strong>Data:</strong> $data</p>
        <p><strong>Hora:</strong> $hora</p>
        <p><strong>Contacto:</strong> {$_POST['contacto']}</p>
      ";

      $mail->send();
    } catch (Exception $e) {
      $mensagem .= "<br><br>⚠️ Erro ao enviar email: " . $mail->ErrorInfo;
    }

    $mensagem = "✅ A sua marcação para <strong>{$_POST['servico']}</strong> no dia <strong>$data às $hora</strong> foi registada com sucesso.";
    $sucesso = true;
  }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Confirmação - AM Estética</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
  <div class="formulario">
    <img src="logo.png" alt="Logotipo AM Estética" class="logo">
    <h2 class="<?= $sucesso ? 'titulo-verde' : 'titulo-vermelho' ?>">Confirmação</h2>
    <p><?= $mensagem ?></p>
    <div class="botoes-centro">
      <a href="index.html"><button class="botao-vermelho">Nova Marcação</button></a>
    </div>
  </div>

  <div class="imagem-lateral">
    <img src="modelo.jpg" alt="Imagem estética">
  </div>
</div>
</body>
</html>
