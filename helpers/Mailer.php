<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    public static function enviarToken(
        $destinatario,
        $token
    ) {

        $config =
            parse_ini_file(
                "config/config.ini"
            );

        $mail =
            new PHPMailer(
                true
            );

        try {

            $mail->isSMTP();

            $mail->Host =
                $config[
                'smtp_host'
                ];

            $mail->SMTPAuth =
                true;

            $mail->Username =
                $config[
                'smtp_email'
                ];

            $mail->Password =
                $config[
                'smtp_password'
                ];

            $mail->SMTPSecure =
                $config[
                'smtp_secure'
                ];

            $mail->Port =
                $config[
                'smtp_port'
                ];

            $mail->CharSet =
                'UTF-8';

            $mail->setFrom(
                $config[
                'smtp_email'
                ],
                'Preguntados'
            );

            $mail->addAddress(
                $destinatario
            );

            $mail->isHTML(
                true
            );

            $mail->Subject =
                'Activá tu cuenta';

            $mail->Body =
                "
                <h2>Bienvenido a Preguntados</h2>

                <p>
                    Tu código de validación es:
                </p>

                <h1>$token</h1>

                <p>
                    Ingresalo para activar tu cuenta.
                </p>
                ";

            $mail->send();

            return true;

        } catch (Exception $e) {

            error_log(
                $mail->ErrorInfo
            );

            return false;
        }
    }
}