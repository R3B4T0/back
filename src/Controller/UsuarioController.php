<?php

namespace App\Controller;

use App\Repository\UsuarioRepository;
use App\Entity\Usuario;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Firebase\JWT\JWT;
use App\Security\JwtAuthenticator;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class UsuarioController
 * @package App\Controller
 * 
 * @Route(path="/api/")
 */
class UsuarioController extends AbstractController
{
    private $usuarioRepository;

    public function __construct(UsuarioRepository $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }

    /**
     * @Route("usuario", name="insertar_usuario", methods={"POST"})
     */
    public function insertar(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $nombre = $data['nombre'];
        $apellidos = $data['apellidos'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_BCRYPT);
        $foto = $data['foto'];
        $telefono = $data['telefono'];
        $role = $data['role'];
        if ($role == "ROLE_MUSICO") {
            $role = array(
                "ROLE_MUSICO"
            );
        }

        if ($role == "ROLE_BANDA") {
            $role = array(
                "ROLE_BANDA"
            );
        }

        if (empty($nombre) || empty($email) || empty($password) || empty($foto) || empty($role) || empty($telefono))
        {
            throw new NotFoundHttpException('Debe rellenar todos los campos obligatorios.');
        }

        $this->usuarioRepository->guardarUsuario($nombre, $apellidos, $email, $password, $foto, $role, $telefono);

        return new JsonResponse(['status' => 'Usuario creado'], Response::HTTP_CREATED);
    }

    /**
     * @Route("usuario/{id}", name="obtener_usuario", methods={"GET"})
     */
    public function get($id): JsonResponse
    {
        $usuario = $this->usuarioRepository->findOneBy(['id' => $id]);

        $data = [
            'id' => $usuario->getId(),
            'nombre' => $usuario->getNombre(),
            'apellidos' => $usuario->getApellidos(),
            'email' => $usuario->getEmail(),
            'foto' => $usuario->getFoto(),
            'role' => $usuario->getRoles(),
        ];

        return new JsonResponse($data, Response::HTTP_OK);
    }

    /**
     * @Route("perfil", name="obtener_perfil", methods={"GET"})
     */
    public function perfil(Request $request, ParameterBagInterface $params, UserProviderInterface $userProvider)
    {
        $em = $this->getDoctrine()->getManager();   
        $auth = new JwtAuthenticator($em, $params);
        
        $credenciales = $auth->getCredentials($request);
        
        $usuario = $auth->getUser($credenciales, $userProvider);
        if ($usuario) {

            $data = [
                'id' => $usuario->getId(),
                'email' => $usuario->getEmail(),
                'nombre' => $usuario->getNombre(),
                'apellidos' => $usuario->getApellidos(),
                'datosInteres' => $usuario->getDatosInteres(),
                'foto' => $usuario->getFoto()
            ];

            return new JsonResponse($data, Response::HTTP_OK);
        }
        return new JsonResponse(['error' => 'Usuario no logueado'], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @Route("usuarios", name="obtener_usuarios", methods={"GET"})
     */
    public function getAll(): JsonResponse
    {
        $role = 'ROLE_MUSICO';
        $usuarios = $this->usuarioRepository->findMusicos($role);
        $data = [];

        foreach ($usuarios as $usuario) {
            $data[] = [
                'id' => $usuario->getId(),
                'nombre' => $usuario->getNombre(),
                'apellidos' => $usuario->getApellidos(),
                'email' => $usuario->getEmail(),
                'foto' => $usuario->getFoto(),
                'role' => $usuario->getRoles(),
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    /**
     * @Route("usuario/{id}", name="actualizar_usuario", methods={"PUT"})
     */
    public function actualizar($id, Request $request): JsonResponse
    {
        $usuario = $this->usuarioRepository->findOneBy(['id' => $id]);
        $data = json_decode($request->getContent(), true);

        empty($data['nombre']) ? true : $usuario->setNombre($data['nombre']);
        empty($data['apellidos']) ? true : $usuario->setApellidos($data['apellidos']);
        empty($data['email']) ? true : $usuario->setEmail($data['email']);
        empty($data['foto']) ? true : $usuario->setFoto($data['foto']);

        $actualizaUsuario = $this->usuarioRepository->actualizaUsuario($usuario);

        return new JsonResponse(['status' => 'Usuario actualizado!'], Response::HTTP_OK);
    }

    /**
     * @Route("usuario/{id}", name="borrar_usuario", methods={"DELETE"})
     */
    public function borrar($id): JsonResponse
    {
        $usuario = $this->usuarioRepository->findOneBy(['id' => $id]);

        $this->usuarioRepository->removeUsuario($usuario);

        return new JsonResponse(['status' => 'Usuario borrado'], Response::HTTP_OK);
    }

    /**
     * @Route("bandas", name="obtener_bandas", methods={"GET"})
     */
    public function bandas(): JsonResponse
    {
        $role = 'ROLE_BANDA';
        $bandas = $this->usuarioRepository->findBandas($role);
        $data = [];

        foreach ($bandas as $banda) {
            $data[] = [
                'id' => $banda->getId(),
                'nombre' => $banda->getNombre(),
                'apellidos' => $banda->getApellidos(),
                'email' => $banda->getEmail(),
                'foto' => $banda->getFoto(),
                'telefono' => $banda->getTelefono(),
                'role' => $banda->getRoles(),
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    /**
     * @Route("login", name="login", methods={"POST"})
     */
    public function login(Request $request) {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'];
        $pwd = $data['password'];

        if ($email && $pwd) {
            $usuario = $this->usuarioRepository
                    ->findOneBy(['email' => $email]);

            if ($usuario) {
                if (password_verify($pwd, $usuario->getPassword())) {
                    // Creamos el JWT
                    $payload = [
                        "usuario" => $usuario->getEmail(),
                        "exp" => (new \DateTime())->modify("+3 day")->getTimestamp()
                    ];

                    $jwt = JWT::encode($payload, $this->getParameter('jwt_secret'), 'HS256');
                    $data = [
                        'repuesta' => 'Se ha iniciado sesion',
                        'userToken' => $jwt
                    ];

                    return new JsonResponse($data, Response::HTTP_OK);
                }
                return new JsonResponse(['error' => 'Credenciales inválidas'], Response::HTTP_NOT_FOUND);
            }
            return new JsonResponse(['error' => 'Credenciales inválidas'], Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse(['error' => 'Faltan parámetros'], Response::HTTP_PARTIAL_CONTENT);
    }
}
