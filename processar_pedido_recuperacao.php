<?php
// Inclua os arquivos do PHPMailer manualmente
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matricula = trim($_POST['matricula']);
    $emailDigitado = trim($_POST['email']);

    $dadosFirebase = buscarUsuario($matricula);

    if (isset($dadosFirebase['fields'])) {
        $emailNoBanco = $dadosFirebase['fields']['email']['stringValue'] ?? '';

        if ($emailDigitado === $emailNoBanco) {
            // --- PASSO 1: GERAR O TOKEN ---
            $token = bin2hex(random_bytes(16));
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // --- PASSO 2: SALVAR NO FIRESTORE ---
            $url = $baseUrl . $matricula . "?updateMask.fieldPaths=token_recuperacao&updateMask.fieldPaths=token_expiracao";
            
            $patchDados = [
                'fields' => [
                    'token_recuperacao' => ['stringValue' => $token],
                    'token_expiracao' => ['stringValue' => $expiracao]
                ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($patchDados));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_exec($ch);
            curl_close($ch);

            // --- PASSO 3: ENVIAR O E-MAIL ---
            $link = "http://localhost/verbum/recuperar_senha.php?token=$token&mat=$matricula";
            
            $mail = new PHPMailer(true);

            try {
                // Configurações do Servidor
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; 
                $mail->SMTPAuth   = true;
                $mail->Username   = 'bibliotecaverbum@gmail.com'; 
                $mail->Password   = 'xwcm fzqj fadq bvjj';    
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';

                // Opções para evitar erro de SSL no XAMPP (Localhost)
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                // Destinatário
                $mail->setFrom('bibliotecaverbum@gmail.com', 'Biblioteca Verbum');
                $mail->addAddress($emailDigitado); 

                // Conteúdo do E-mail
                $mail->isHTML(true);
                $mail->Subject = 'Recuperação de Senha - Verbum';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                        <h2 style='color: #2ecc71;'>Olá!</h2>
                        <p>Recebemos uma solicitação para redefinir a senha da sua conta da Biblioteca <strong>Verbum</strong>.</p>
                        <p>Clique no botão abaixo para prosseguir com a alteração:</p>
                        <p style='text-align: center; margin: 30px 0;'>
                            <a href='$link' style='display: inline-block; padding: 12px 25px; background-color: #2ecc71; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Redefinir Senha</a>
                        </p>
                        <p>Se você não solicitou isso, pode ignorar este e-mail com segurança.</p>
                        <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                        <p style='font-size: 12px; color: #888;'>Este link expirará automaticamente em 1 hora.</p>
                    </div>";

                $mail->send();
                header("Location: esqueceu_senha.php?sucesso=email_enviado");
            } catch (Exception $e) {
                // Se o e-mail falhar, avisa o SweetAlert
                header("Location: esqueceu_senha.php?erro=envio_falhou");
            }
        } else {
            // E-mail não bate com a matrícula
            header("Location: esqueceu_senha.php?erro=dados_invalidos");
        }
    } else {
        // Matrícula não existe no Firebase
        header("Location: esqueceu_senha.php?erro=usuario_nao_encontrado");
    }
    exit();
}