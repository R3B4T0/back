<?php

namespace App\Controller;

use App\Repository\UsuarioRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


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
        $password = $data['password'];
        $foto = $data['foto'];
        $telefono = $data['telefono'];
        $role = $data['role'];

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
     * @Route("usuarios", name="obtener_usuarios", methods={"GET"})
     */
    public function getAll(): JsonResponse
    {
        $usuarios = $this->usuarioRepository->findAll();
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
}
