<?php

namespace App\Controllers;

use App\Libraries\FiltrosTienda;
use App\Services\PedidoWebService;
use App\Services\TiendaCarritoService;
use App\Services\TiendaCatalogoService;
use App\Services\LoginAttemptService;
use InvalidArgumentException;

class TiendaController extends BaseController
{
    private function datosLayout(array $datos = []): array
    {
        $carrito = session('tienda_carrito') ?? [];
        return $datos + [
            'carritoCantidad' => count($carrito),
            'clienteWeb' => session('cliente_web'),
            'categoriasTienda' => (new TiendaCatalogoService())->categorias(),
            'busquedaGlobal' => FiltrosTienda::normalizar($this->request->getGet() ?? [])['q'],
        ];
    }

    public function catalogo()
    {
        $filtros = FiltrosTienda::normalizar($this->request->getGet() ?? []);
        $pagina = $this->request->getGet('pagina');
        $pagina = is_scalar($pagina) ? max(1, (int) $pagina) : 1;
        $catalogo = new TiendaCatalogoService();
        $resultado = $catalogo->listar($filtros['q'], $filtros['tipo'], $pagina, $filtros['categoria'], 12, $filtros);
        return view('tienda/catalogo', $this->datosLayout($resultado + $filtros + [
            'filtrosCatalogo' => $filtros, 'marcasTienda' => $catalogo->marcas(),
            'ordenesCatalogo' => FiltrosTienda::ORDENES, 'titulo' => 'Catálogo | Delafiber',
        ]));
    }

    public function detalle(int $id)
    {
        $catalogo = new TiendaCatalogoService();
        $producto = $catalogo->obtener($id);
        if (!$producto) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $componentes = $producto['tipo'] === 'COMBO' ? $catalogo->componentes($id) : [];
        return view('tienda/detalle', $this->datosLayout(compact('producto', 'componentes') + [
            'titulo' => $producto['nombre'] . ' | Delafiber',
            'descripcionPagina' => $producto['descripcion'] ?: $producto['nombre'] . '. Consulta características y coordina tu pedido con DELAFIBER.',
        ]));
    }

    public function carrito()
    {
        $resumen = (new TiendaCarritoService())->resolver(session('tienda_carrito') ?? []);
        if ($resumen['no_disponibles']) {
            $carrito = session('tienda_carrito') ?? [];
            foreach ($resumen['no_disponibles'] as $id) unset($carrito[$id]);
            session()->set('tienda_carrito', $carrito);
            session()->setFlashdata('error', 'Se retiraron articulos que ya no estan disponibles.');
        }
        return view('tienda/carrito', $this->datosLayout(compact('resumen') + ['titulo' => 'Carrito | Delafiber']));
    }

    public function agregarCarrito()
    {
        try {
            $id = $this->request->getPost('idproducto');
            if (!is_scalar($id) || !ctype_digit((string) $id) || strlen((string) $id) > 10 || (float) $id > 2147483647) {
                throw new InvalidArgumentException('El artículo no está disponible.');
            }
            $resultado = $this->servicioCarrito()->agregar(
                session('tienda_carrito') ?? [], (int) $id, $this->request->getPost('cantidad')
            );
            session()->set('tienda_carrito', $resultado['carrito']);
            $mensaje = 'Producto agregado al carrito.';
            if ($resultado['retirados']) $mensaje .= ' Se retiraron artículos que ya no están disponibles.';
            if ($this->request->isAJAX()) {
                $producto = $resultado['producto'];
                return $this->respuestaCarrito([
                    'success' => true, 'mensaje' => $mensaje,
                    'carrito' => [
                        'cantidad' => $resultado['resumen']['cantidad'],
                        'total' => $resultado['resumen']['total'],
                    ],
                    'producto' => [
                        'idproducto' => (int) $producto['idproducto'], 'nombre' => $producto['nombre'],
                        'cantidad_agregada' => $resultado['cantidad_agregada'],
                        'cantidad' => $producto['cantidad'], 'unidad' => $producto['unidad'] ?: 'unidad',
                    ],
                ]);
            }
            return redirect()->to(base_url('carrito'))->with('mensaje', $mensaje);
        } catch (\Throwable $e) {
            $mensaje = $e instanceof InvalidArgumentException ? $e->getMessage() : 'No se pudo actualizar el carrito.';
            if ($this->request->isAJAX()) {
                return $this->respuestaCarrito(['success' => false, 'mensaje' => $mensaje], $e instanceof InvalidArgumentException ? 422 : 500);
            }
            return redirect()->back()->with('error', $mensaje);
        }
    }

    protected function servicioCarrito(): TiendaCarritoService
    {
        return new TiendaCarritoService();
    }

    private function respuestaCarrito(array $datos, int $estado = 200)
    {
        return $this->response->setStatusCode($estado)->setHeader('Cache-Control', 'private, no-store')
            ->setJSON($datos + ['csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()]]);
    }

    public function actualizarCarrito()
    {
        $cantidades = $this->request->getPost('cantidades');
        try {
            if (!is_array($cantidades)) throw new InvalidArgumentException('Carrito invalido.');
            $carrito = [];
            foreach ($cantidades as $id => $cantidad) {
                if ((int) $id > 0 && (float) $cantidad > 0) $carrito[(int) $id] = TiendaCarritoService::cantidad($cantidad);
            }
            (new TiendaCarritoService())->resolver($carrito);
            session()->set('tienda_carrito', $carrito);
            return redirect()->to(base_url('carrito'))->with('mensaje', 'Carrito actualizado.');
        } catch (\Throwable $e) {
            return redirect()->to(base_url('carrito'))->with('error', 'Revisa las cantidades ingresadas.');
        }
    }

    public function eliminarCarrito(int $id)
    {
        $carrito = session('tienda_carrito') ?? [];
        unset($carrito[$id]);
        session()->set('tienda_carrito', $carrito);
        return redirect()->to(base_url('carrito'))->with('mensaje', 'Articulo retirado.');
    }

    public function checkout()
    {
        $resumen = (new TiendaCarritoService())->resolver(session('tienda_carrito') ?? []);
        if (!$resumen['items']) return redirect()->to(base_url('carrito'))->with('error', 'Tu carrito esta vacio.');
        $cuenta = $this->cuentaActual();
        $tokenCheckout = (string) session('tienda_checkout_token');
        if (!preg_match('/^[a-f0-9]{64}$/D', $tokenCheckout)) {
            $tokenCheckout = bin2hex(random_bytes(32));
            session()->set('tienda_checkout_token', $tokenCheckout);
        }
        return view('tienda/checkout', $this->datosLayout(compact('resumen', 'cuenta', 'tokenCheckout') + ['titulo' => 'Finalizar pedido | Delafiber']));
    }

    public function confirmarCheckout()
    {
        try {
            $datos = $this->request->getPost();
            $token = (string) ($datos['request_token'] ?? '');
            $tokenSesion = (string) session('tienda_checkout_token');
            if (!preg_match('/^[a-f0-9]{64}$/D', $token) || !hash_equals($tokenSesion, $token)) {
                throw new InvalidArgumentException('La solicitud de compra expiro. Recarga el checkout.');
            }
            $resultado = (new PedidoWebService())->crear($datos, session('tienda_carrito') ?? [], $this->cuentaActual());
            session()->remove('tienda_carrito');
            session()->remove('tienda_checkout_token');
            $mensaje = !empty($resultado['correo_enviado'])
                ? 'Pedido recibido correctamente. También enviamos el enlace de seguimiento a tu correo.'
                : 'Pedido recibido correctamente. Guarda este enlace de seguimiento; no pudimos enviar el correo.';
            return redirect()->to(base_url('seguimiento/' . $resultado['token']))->with('mensaje', $mensaje);
        } catch (\Throwable $e) {
            log_message('error', 'Pedido web: ' . $e->getMessage());
            return redirect()->to(base_url('checkout'))->withInput()->with('error', $e instanceof InvalidArgumentException ? $e->getMessage() : 'No se pudo registrar el pedido. Intenta nuevamente.');
        }
    }

    public function seguimiento(string $token)
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        $pedido = db_connect()->table('pedido_web pw')
            ->select('p.codigo, p.fecha, p.total, e.estado, e.codigo AS estado_codigo, pw.modalidad, pw.direccion_entrega, pw.nombre_contacto')
            ->join('pedido p', 'p.idpedido = pw.idpedido')->join('estadopedido e', 'e.idestado = p.idestado')
            ->where('pw.token_seguimiento_hash', hash('sha256', $token))->get()->getRowArray();
        if (!$pedido) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        return view('tienda/seguimiento', $this->datosLayout(compact('pedido') + ['titulo' => $pedido['codigo'] . ' | Delafiber']));
    }

    public function login()
    {
        if (session('cliente_web')) return redirect()->to(base_url('cuenta'));
        return view('tienda/login', $this->datosLayout(['titulo' => 'Ingresar | Delafiber']));
    }

    public function autenticar()
    {
        $email = $this->request->getPost('email');
        $clave = $this->request->getPost('clave');
        $email = is_string($email) ? mb_strtolower(trim($email)) : '';
        $clave = is_string($clave) ? $clave : '';
        $cuenta = strlen($email) <= 100 && strlen($clave) <= 72 ? $this->cuentaLogin($email) : null;
        $pendiente = $cuenta && empty($cuenta['email_verificado_en'])
            && ((int) $cuenta['activo'] === 1 || !empty($cuenta['verificacion_token_hash']));
        $resultado = $this->intentosLogin()->intentar('cliente', $email, $this->request->getIPAddress(), $clave,
            $cuenta['clave_hash'] ?? null, (int) ($cuenta['activo'] ?? 0) === 1 || $pendiente);
        if ($resultado !== LoginAttemptService::OK) return redirect()->to(base_url('cliente/login'))->with('error',
            $resultado === LoginAttemptService::BLOQUEADO ? 'Demasiados intentos fallidos. Espera cinco minutos.' : 'Correo o clave incorrectos.');
        if (empty($cuenta['email_verificado_en'])) return redirect()->to(base_url('cliente/login'))->with('error', 'Debes verificar tu correo antes de ingresar. Solicita el enlace desde el formulario de registro.');
        session()->regenerate(true);
        session()->set('cliente_web', ['id' => (int) $cuenta['idcliente_cuenta'], 'email' => $cuenta['email']]);
        db_connect()->table('cliente_cuentas')->where('idcliente_cuenta', $cuenta['idcliente_cuenta'])->update(['ultimo_acceso' => date('Y-m-d H:i:s')]);
        return redirect()->to(base_url('cuenta'));
    }

    protected function cuentaLogin(string $email): ?array
    {
        return db_connect()->table('cliente_cuentas')->where('email', $email)->get()->getRowArray();
    }

    protected function intentosLogin(): LoginAttemptService { return new LoginAttemptService(); }

    public function registro()
    {
        if (session('cliente_web')) return redirect()->to(base_url('cuenta'));
        return view('tienda/registro', $this->datosLayout(['titulo' => 'Crear cuenta | Delafiber']));
    }

    public function registrar()
    {
        $this->registroClientes()->registrar($this->request->getPost() ?? []);
        return redirect()->to(base_url('cliente/registro'))->with('mensaje', \App\Services\ClienteRegistroService::MENSAJE);
    }

    protected function registroClientes(): \App\Services\ClienteRegistroService
    {
        return new \App\Services\ClienteRegistroService();
    }

    public function verificar(string $token)
    {
        $ok = $this->registroClientes()->verificar($token);
        return redirect()->to(base_url('cliente/login'))->with($ok ? 'mensaje' : 'error',
            $ok ? 'Correo verificado. Ya puedes iniciar sesión.' : 'El enlace no es válido o venció. Solicita otro desde Registro.');
    }

    public function cuenta()
    {
        $cuenta = $this->cuentaActual();
        if (!$cuenta) return redirect()->to(base_url('cliente/login'))->with('error', 'Inicia sesion para ver tus pedidos.');
        $pedidos = db_connect()->table('pedido_web pw')->select('p.codigo, p.fecha, p.total, e.estado, e.codigo AS estado_codigo, pw.modalidad')
            ->join('pedido p', 'p.idpedido = pw.idpedido')->join('estadopedido e', 'e.idestado = p.idestado')
            ->where('pw.idcliente_cuenta', $cuenta['idcliente_cuenta'])->orderBy('p.idpedido', 'DESC')->get()->getResultArray();
        return view('tienda/cuenta', $this->datosLayout(compact('cuenta', 'pedidos') + ['titulo' => 'Mi cuenta | Delafiber']));
    }

    public function logout()
    {
        session()->remove('cliente_web');
        session()->regenerate(true);
        return redirect()->to(base_url('/'));
    }

    private function cuentaActual(): ?array
    {
        $sesion = session('cliente_web');
        if (!$sesion || empty($sesion['id'])) return null;
        return db_connect()->table('cliente_cuentas c')->select('c.idcliente_cuenta, c.idpersona, c.email, p.nombres, p.apellidos, p.razonsocial, p.tipodoc, p.numerodoc, p.telefono, p.direccion')
            ->join('personas p', 'p.idpersona = c.idpersona')->where('c.idcliente_cuenta', (int) $sesion['id'])->where('c.activo', 1)->where('c.email_verificado_en IS NOT NULL', null, false)->get()->getRowArray() ?: null;
    }
}
