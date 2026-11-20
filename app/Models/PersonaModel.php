<?php

namespace App\Models;

use CodeIgniter\Model;

class PersonaModel extends Model
{
    protected $table            = 'personas';
    protected $primaryKey       = 'idpersona';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tipopersona',
        'razonsocial',
        'apellidos',
        'nombres',
        'tipodoc',
        'numerodoc',
        'direccion',
        'telefono',
        'email',
        'estado',
    ];

    protected $validationRules = [
        'idpersona' => 'permit_empty|is_natural_no_zero',
        'tipopersona' => 'permit_empty|in_list[NATURAL,JURIDICA]',
        'razonsocial' => 'required_without[nombres]|permit_empty|min_length[2]|max_length[150]',
        'apellidos' => 'permit_empty|min_length[2]|max_length[100]',
        'nombres'   => 'required_without[razonsocial]|permit_empty|min_length[2]|max_length[100]',
        'tipodoc'   => 'required|in_list[DNI,RUC,CE,PASAPORTE]',
        'numerodoc' => 'required|max_length[20]',
        'direccion' => 'permit_empty|max_length[150]',
        'telefono'  => 'permit_empty|max_length[20]',
        'email'     => 'permit_empty|valid_email|max_length[100]',
        'estado'    => 'permit_empty|in_list[0,1]',
    ];

    /**
     * Normaliza únicamente los campos editables del cliente. El tipo de persona
     * se deriva del documento, no de un campo manipulable del formulario.
     */
    public function datosCliente(array $entrada): array
    {
        $datos = [];
        foreach (['nombres', 'apellidos', 'razonsocial', 'tipodoc', 'numerodoc', 'direccion', 'telefono', 'email'] as $campo) {
            $valor = $entrada[$campo] ?? '';
            if (!is_scalar($valor)) {
                throw new \InvalidArgumentException('Los datos del cliente no son válidos.');
            }
            $valor = trim((string) $valor);
            $datos[$campo] = $valor === '' ? null : $valor;
        }

        if ($datos['tipodoc'] === 'RUC') {
            $datos['tipopersona'] = 'JURIDICA';
            // Compatibilidad con el formulario anterior y el registro rápido.
            $datos['razonsocial'] ??= $datos['nombres'];
            $datos['nombres'] = null;
            $datos['apellidos'] = null;
        } else {
            $datos['tipopersona'] = 'NATURAL';
            $datos['razonsocial'] = null;
        }

        return $datos;
    }

    public function reglasCliente(string $tipoDoc, bool $exigirApellidos = true): array
    {
        $reglas = $this->getValidationRules();
        $juridica = $tipoDoc === 'RUC';
        $reglas['nombres'] = ($juridica ? 'permit_empty' : 'required') . '|min_length[2]|max_length[100]';
        $reglas['razonsocial'] = ($juridica ? 'required' : 'permit_empty') . '|min_length[2]|max_length[150]';
        $reglas['apellidos'] = (!$juridica && $exigirApellidos ? 'required' : 'permit_empty') . '|min_length[2]|max_length[100]';
        $reglas['numerodoc'] = match ($tipoDoc) {
            'DNI' => 'required|regex_match[/^[0-9]{8}$/D]',
            'RUC' => 'required|regex_match[/^[0-9]{11}$/D]',
            default => 'required|regex_match[/^[A-Za-z0-9]{5,20}$/D]',
        };

        return $reglas;
    }

    public static function nombreCliente(array $persona): string
    {
        $razonSocial = trim((string) ($persona['razonsocial'] ?? ''));
        return $razonSocial !== '' ? $razonSocial : trim(($persona['nombres'] ?? '') . ' ' . ($persona['apellidos'] ?? ''));
    }
}
