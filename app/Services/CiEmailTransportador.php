<?php

namespace App\Services;

use CodeIgniter\Email\Email;
use InvalidArgumentException;
use RuntimeException;

final class CiEmailTransportador implements PedidoCorreoTransportador
{
    public function __construct(private ?Email $email = null)
    {
        $this->email ??= service('email', false);
    }

    public function enviar(string $destino, string $asunto, string $mensaje): void
    {
        $config = config('Email');
        if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('El destinatario no es válido.');
        if (!filter_var($config->fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El remitente de correo no está configurado.');
        }
        $this->email->clear(true);
        $this->email->setFrom($config->fromEmail, $config->fromName ?: 'DELAFIBER');
        $this->email->setTo($destino);
        $this->email->setSubject($asunto);
        $this->email->setMessage($mensaje);
        if (!$this->email->send(false)) throw new RuntimeException('El servidor de correo rechazó el mensaje.');
    }
}
