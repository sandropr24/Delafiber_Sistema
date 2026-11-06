<?php

namespace App\Models;

use CodeIgniter\Model;

class TecnicoModel extends Model
{
    protected $table = 'tecnicos';
    protected $primaryKey = 'idtecnico';
    protected $returnType = 'array';
    protected $allowedFields = ['nombres', 'apellidos', 'dni', 'telefono', 'especialidad', 'estado'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'nombres' => 'required|max_length[100]',
        'apellidos' => 'required|max_length[100]',
        'dni' => 'required|exact_length[8]|numeric|is_unique[tecnicos.dni,idtecnico,{idtecnico}]',
        'telefono' => 'permit_empty|max_length[20]',
        'especialidad' => 'permit_empty|max_length[100]',
        'estado' => 'required|in_list[ACTIVO,INACTIVO]',
    ];

    public function activos(): array
    {
        return $this->where('estado', 'ACTIVO')->orderBy('nombres', 'ASC')->findAll();
    }
}
