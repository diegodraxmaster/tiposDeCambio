<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function obtenerTiposDeCambio()
{
    $url = 'https://v6.exchangerate-api.com/v6/617ee60d7da82906cd586141/latest/USD';
    $response = file_get_contents($url);
    if ($response === false) {
        throw new Exception('Error al obtener los datos del servicio web');
    }

    $data = json_decode($response, true);

    // Filtrar y guardar solo los tipos de cambio relevantes
    $tiposDeCambio = [
        'PEN' => $data['conversion_rates']['PEN'],
        'USD' => $data['conversion_rates']['USD'],
        'EUR' => $data['conversion_rates']['EUR'],
        'CNY' => $data['conversion_rates']['CNY'],
    ];

    return $tiposDeCambio;
}
function guardarLectura($lectura)
{
    // Generar un nombre de archivo único con marca de tiempo
    $timestamp = time();
    $filename = "lectura_$timestamp.json";

    // Guardar la lectura en un archivo JSON
    file_put_contents($filename, json_encode($lectura));
}
function enviarCorreo($data, $archivosAdjuntos)
{
    //configuracion de php mailer
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'diegoarmandocharpacerrato@gmail.com'; // este dato deberia estar en un .env o config.php
    $mail->Password = 'mxxqnkyevhepewwl'; // este dato deberia estar en un .env o config.php
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->setFrom('diegoarmandocharpacerrato@gmail.com', 'Diego Armando Charpa Cerrato');
    $mail->addAddress('rcusi@itesur.com', 'Reynaldo Isaac Cusi Ascencio ');
    $mail->isHTML(true);

    // Crear un correo electrónico en formato HTML con los tipos de cambio
    $subject = 'Tipos de Cambio';
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Tipos de Cambio</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
            }
            h1 {
                background-color: #007bff;
                color: #fff;
                padding: 20px;
                text-align: center;
            }
            ul {
                list-style: none;
                padding: 0;
            }
            li {
                background-color: #fff;
                margin: 10px 0;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <h1>Tipos de Cambio</h1>
        <ul>
            <li><strong>PEN (Soles):</strong> {$data['PEN']}</li>
            <li><strong>USD (Dólares Americanos):</strong> {$data['USD']}</li>
            <li><strong>EUR (Euros):</strong> {$data['EUR']}</li>
            <li><strong>CNY (Yuan):</strong> {$data['CNY']}</li>
        </ul>
    </body>
    </html>
    ";

    $mail->Subject = $subject;
    $mail->Body = $body;

    // Adjuntar los archivos con lecturas anteriores
    foreach ($archivosAdjuntos as $archivoAdjunto) {
        $mail->addAttachment($archivoAdjunto);
    }

    if ($mail->send()) {
        echo 'Correo electrónico enviado con éxito.';
    } else {
        throw new Exception('Error al enviar el correo electrónico: ' . $mail->ErrorInfo);
    }
}

try {
    $data = obtenerTiposDeCambio();

    // Guardar la lectura actual
    guardarLectura($data);

    // Obtener archivos con lecturas anteriores (por ejemplo, todos los archivos JSON en el directorio)
    $archivosAdjuntos = glob('lectura_*.json');

    // Enviar el correo electrónico con tipos de cambio y archivos adjuntos
    enviarCorreo($data, $archivosAdjuntos);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
