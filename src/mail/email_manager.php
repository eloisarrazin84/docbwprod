<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php'; // Adaptez le chemin selon votre projet

function sendEmailNotification($toEmail, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Configuration du serveur SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Remplacez par votre serveur SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'eloi@bewitness.fr'; // Votre adresse e-mail
        $mail->Password = 'efgv qvxh usko wmah'; // Mot de passe de votre e-mail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Configuration de l'encodage
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Configuration de l'expéditeur et du destinataire
        $mail->setFrom('eloi@bewitness.fr', 'Documents BW PROD');
        $mail->addAddress($toEmail);

        // Contenu de l'e-mail
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur d'envoi d'email : {$mail->ErrorInfo}");
        return false;
    }
}
