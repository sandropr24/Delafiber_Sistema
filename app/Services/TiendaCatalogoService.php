<?php

namespace App\Services;

use App\Libraries\FiltrosTienda;
use CodeIgniter\Database\BaseConnection;

class TiendaCatalogoService
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    public function listar(string $q, string $tipo, int $pagina, int $categoria = 0, int $porPagina = 12, array $filtros = []): array
    {
        $filtros = FiltrosTienda::normalizar(array_replace($filtros, compact('q', 'tipo', 'categoria')));
        $q = $filtros['q'];
        $tipo = $filtros['tipo'];
        $categoria = $filtros['categoria'];
        $porPagina = max(1, min(48, $porPagina));
        $categoriasFiltro = $categoria > 0 ? [$categoria] : [];
        if ($categoria > 0) {
            $hijas = $this->db->table('categorias')->select('idcategoria')->where('idcategoriapadre', $categoria)->where('estado', 1)->get()->getResultArray();
            $categoriasFiltro = array_merge($categoriasFiltro, array_map('intval', array_column($hijas, 'idcategoria')));
        }
        $crear = function () use ($q, $tipo, $categoriasFiltro, $filtros) {
            $builder = $this->db->table('productos p')
                ->join('categorias c', 'c.idcategoria = p.idcategoria')
                ->join('marcas m', 'm.idmarca = p.idmarca', 'left')
                ->join('unidadesmedida um', 'um.idunidad = p.idunidad', 'left')
                ->where('p.estado', 1);
            if ($tipo !== '') {
                $builder->where('p.tipo', $tipo);
            }
            if ($categoriasFiltro) {
                $builder->whereIn('p.idcategoria', $categoriasFiltro);
            }
            if ($filtros['marca'] > 0) {
                $builder->where('p.idmarca', $filtros['marca']);
            }
            if ($filtros['precio_min'] !== '') {
                $builder->where('p.precioventa >=', $filtros['precio_min']);
            }
            if ($filtros['precio_max'] !== '') {
                $builder->where('p.precioventa <=', $filtros['precio_max']);
            }
            if ($q !== '') {
                $builder->groupStart()->like('p.nombre', $q)->orLike('p.descripcion', $q)
                    ->orLike('p.modelo', $q)->orLike('c.categoria', $q)->orLike('m.marca', $q)->groupEnd();
            }
            return $builder;
        };
        $total = (int) $crear()->countAllResults();
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min($paginas, max(1, $pagina));
        [$campo, $direccion] = [
            'nombre_asc' => ['p.nombre', 'ASC'],
            'nombre_desc' => ['p.nombre', 'DESC'],
            'precio_asc' => ['p.precioventa', 'ASC'],
            'precio_desc' => ['p.precioventa', 'DESC'],
        ][$filtros['orden']];
        $items = $crear()->select('p.idproducto, p.tipo, p.nombre, p.descripcion, p.modelo, p.precioventa, p.imagen, p.garantiameses, c.categoria, m.marca, um.abreviatura AS unidad')
            ->orderBy($campo, $direccion)->orderBy('p.idproducto', 'ASC')->get($porPagina, ($pagina - 1) * $porPagina)->getResultArray();

        return compact('items', 'total', 'pagina', 'paginas');
    }

    public function marcas(): array
    {
        return $this->db->table('marcas m')->distinct()->select('m.idmarca, m.marca')
            ->join('productos p', 'p.idmarca = m.idmarca')
            ->where('m.estado', 1)->where('p.estado', 1)->orderBy('m.marca')
            ->get()->getResultArray();
    }

    public function componentes(int $id): array
    {
        return $this->db->table('combo_detalle cd')
            ->select('p.idproducto, p.nombre, p.tipo, p.modelo, p.garantiameses, cd.cantidad, um.abreviatura AS unidad')
            ->join('productos combo', 'combo.idproducto = cd.idcombo')
            ->join('productos p', 'p.idproducto = cd.idproducto')
            ->join('unidadesmedida um', 'um.idunidad = p.idunidad', 'left')
            ->where('cd.idcombo', $id)->where('combo.estado', 1)->where('combo.tipo', 'COMBO')
            ->where('p.estado', 1)->orderBy('p.tipo')->orderBy('p.nombre')
            ->get()->getResultArray();
    }

    public function categorias(): array
    {
        $filas = $this->db->table('categorias c')
            ->select('c.idcategoria, c.idcategoriapadre, c.categoria, COUNT(p.idproducto) AS productos')
            ->join('productos p', 'p.idcategoria = c.idcategoria AND p.estado = 1', 'left')
            ->where('c.estado', 1)
            ->groupBy('c.idcategoria, c.idcategoriapadre, c.categoria')
            ->orderBy('c.categoria')
            ->get()->getResultArray();

        $porPadre = [];
        $principales = [];
        foreach ($filas as &$fila) {
            $fila['idcategoria'] = (int) $fila['idcategoria'];
            $fila['idcategoriapadre'] = $fila['idcategoriapadre'] === null ? null : (int) $fila['idcategoriapadre'];
            $fila['productos'] = (int) $fila['productos'];
            if ($fila['idcategoriapadre']) $porPadre[$fila['idcategoriapadre']][] = &$fila;
            else $principales[] = &$fila;
        }
        unset($fila);

        $resultado = [];
        $incluidas = [];
        foreach ($principales as &$principal) {
            foreach ($porPadre[$principal['idcategoria']] ?? [] as $hija) $principal['productos'] += $hija['productos'];
            $resultado[] = $principal;
            $incluidas[$principal['idcategoria']] = true;
            foreach ($porPadre[$principal['idcategoria']] ?? [] as $hija) {
                $resultado[] = $hija;
                $incluidas[$hija['idcategoria']] = true;
            }
        }
        unset($principal);
        foreach ($filas as $fila) if (!isset($incluidas[$fila['idcategoria']])) $resultado[] = $fila;
        return $resultado;
    }

    public function obtener(int $id): ?array
    {
        return $this->db->table('productos p')
            ->select('p.idproducto, p.tipo, p.nombre, p.descripcion, p.modelo, p.precioventa, p.imagen, p.garantiameses, c.idcategoria, c.categoria, m.marca, um.abreviatura AS unidad')
            ->join('categorias c', 'c.idcategoria = p.idcategoria')
            ->join('marcas m', 'm.idmarca = p.idmarca', 'left')
            ->join('unidadesmedida um', 'um.idunidad = p.idunidad', 'left')
            ->where('p.idproducto', $id)->where('p.estado', 1)->get()->getRowArray() ?: null;
    }
}
