<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use InvalidArgumentException;
use RuntimeException;

class VoucherStorage
{
    public const MAX_BYTES = 4 * 1024 * 1024;
    private string $directorio;

    public function __construct(?string $directorio = null)
    {
        $this->directorio = rtrim($directorio ?? WRITEPATH . 'uploads/vouchers', DIRECTORY_SEPARATOR);
    }

    public function validar(?UploadedFile $archivo): string
    {
        if (!$archivo || !$archivo->isValid() || $archivo->hasMoved()) {
            throw new InvalidArgumentException('Adjunta una foto válida del voucher en cada pago electrónico.');
        }
        $ruta = $archivo->getTempName();
        $tamano = is_file($ruta) ? filesize($ruta) : false;
        $mime = is_file($ruta) ? (new \finfo(FILEINFO_MIME_TYPE))->file($ruta) : false;
        $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $imagen = isset($extensiones[$mime ?: '']) ? @getimagesize($ruta) : false;
        if (!$tamano || $tamano > self::MAX_BYTES || !$imagen || $imagen[0] > 12000 || $imagen[1] > 12000) {
            throw new InvalidArgumentException('El voucher debe ser una imagen JPG, PNG o WEBP de hasta 4 MB y 12000 píxeles por lado.');
        }
        return $extensiones[$mime];
    }

    public function guardar(UploadedFile $archivo): string
    {
        $extension = $this->validar($archivo);
        if (!is_dir($this->directorio) && !mkdir($this->directorio, 0700, true) && !is_dir($this->directorio)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento privado de vouchers.');
        }
        $nombre = bin2hex(random_bytes(24)) . '.' . $extension;
        $ruta = $this->directorio . DIRECTORY_SEPARATOR . $nombre;
        try {
            if (!$archivo->move($this->directorio, $nombre)) throw new RuntimeException('No se pudo guardar el voucher.');
            if (!@chmod($this->directorio, 0700) || !@chmod($ruta, 0600)) {
                throw new RuntimeException('No se pudieron proteger los permisos del voucher.');
            }
        } catch (\Throwable $e) {
            if (is_file($ruta)) @unlink($ruta);
            throw $e;
        }
        return $nombre;
    }

    public function ruta(string $nombre): string
    {
        if (!preg_match('/^[a-f0-9]{48}\.(?:jpg|png|webp)$/D', $nombre)) throw new InvalidArgumentException('Voucher no válido.');
        return $this->directorio . DIRECTORY_SEPARATOR . $nombre;
    }

    public function descartar(array $nombres): void
    {
        foreach ($nombres as $nombre) {
            $ruta = $this->ruta($nombre);
            if (is_file($ruta) && !@unlink($ruta)) log_message('error', 'No se pudo limpiar un voucher de una venta revertida.');
        }
    }
}
