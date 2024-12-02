<?php
// /srv/mail/templates/welcome_email.php

function getWelcomeEmailTemplate($name, $email, $temporaryPassword, $logoUrl = 'https://example.com/logo.png') {
    return "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Bienvenue sur notre plateforme</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
            }
            .email-container {
                max-width: 600px;
                margin: 20px auto;
                background: #ffffff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }
            .email-header {
                background-color: #007BFF;
                color: #ffffff;
                padding: 20px;
                text-align: center;
            }
            .email-header img {
                max-width: 100px;
                margin-bottom: 10px;
            }
            .email-header h1 {
                margin: 0;
                font-size: 24px;
            }
            .email-body {
                padding: 20px;
                color: #333333;
            }
            .email-body p {
                margin: 0 0 10px;
                line-height: 1.6;
            }
            .email-body .account-info {
                background-color: #f1f8ff;
                padding: 10px;
                border-left: 4px solid #007BFF;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .email-body a {
                display: inline-block;
                background-color: #007BFF;
                color: #ffffff;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 4px;
                font-weight: bold;
            }
            .email-body a:hover {
                background-color: #0056b3;
            }
            .email-footer {
                background-color: #f4f4f4;
                text-align: center;
                padding: 10px;
                font-size: 12px;
                color: #777777;
            }
            .email-footer a {
                color: #007BFF;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <!-- Header -->
            <div class='email-header'>
                <img src='$logoUrl' alt='Logo'>
                <h1>Bienvenue sur notre plateforme RH</h1>
            </div>
            <!-- Body -->
            <div class='email-body'>
                <p>Bonjour $name,</p>
                <p>Un compte a été créé pour vous sur notre plateforme RH. Voici vos identifiants de connexion :</p>
                <div class='account-info'>
                    <strong>Email :</strong> $email<br>
                    <strong>Mot de passe temporaire :</strong> $temporaryPassword
                </div>
                <p>Veuillez vous connecter à votre compte et modifier votre mot de passe depuis votre profil :</p>
                <p>
                    <a href='https://bwprod.outdoorsecours.fr/login.php' target='_blank'>Se connecter</a>
                </p>
                <p>Cordialement,</p>
                <p><strong>L'équipe de gestion</strong></p>
            </div>
            <!-- Footer -->
            <div class='email-footer'>
                &copy; 2024 BW PROD | <a href='https://myapps.bewitness.fr'>Accédez à la plateforme</a>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>
