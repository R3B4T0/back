<?php

namespace App\Controller;

use App\Repository\UsuarioRepository;
use App\Entity\Usuario;
use App\Entity\Video;
use App\Repository\VideoRepository;
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
    private $idUsuario;
    private $videoRepository;

    public function __construct(UsuarioRepository $usuarioRepository, VideoRepository $videoRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
        $this->videoRepository = $videoRepository;
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
            'videos' => $usuario->getVideos(),
            'datosInteres' => $usuario->getDatosInteres()
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
        $this->idUsuario = $usuario->getId();
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
     * @Route("insertar/video", name="insertar_video", methods={"POST"})
     */
    public function insertar2(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $idUsuario = $this->idUsuario;
        $codigo = $data['codigo'];
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $codigo, $match);
        $youtube_id = $match[1];

        $this->videoRepository->insertar($youtube_id, $idUsuario);

        return new JsonResponse(['status' => 'Video insertado'], Response::HTTP_CREATED);
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

    /**
     * @Route("imagen", name="insertar_imagen", methods={"POST"})
     */
    public function insertar_imagen()
    {
        if(is_null(IDUSER)){
            http_response_code(401);
            exit(json_encode(["error" => "Fallo de autorizacion"]));
        }
        if(isset($_FILES['imagen'])) {
            $imagen = $_FILES['imagen'];
            $mime = $imagen['type'];
            $size = $imagen['size'];
            $rutaTemp = $imagen['tmp_name'];
        
            //Comprobamos que la imagen sea JPEG o PNG y que el tamaño sea menor que 400KB.
            if( !(strpos($mime, "jpeg") || strpos($mime, "png")) || ($size > 400000) ) {
                http_response_code(400);
                exit(json_encode(["error" => "La imagen tiene que ser JPG o PNG y no puede ocupar mas de 400KB"]));
            } else {
        
                //Comprueba cual es la extensión del archivo.
                $ext = strpos($mime, "jpeg") ? ".jpg":".png";
                $nombreFoto = "p-".IDUSER."-".time().$ext;
                $ruta = ROOT."images/".$nombreFoto;
            
                //Comprobamos que el usuario no tenga mas fotos de perfil subidas al servidor.
                //En caso de que exista una imagen anterior la elimina.
                $imgFind = ROOT."images/p-".IDUSER."-*";
                $imgFile = glob($imgFind);
                foreach($imgFile as $fichero) unlink($fichero);
              
                //Si se guarda la imagen correctamente actualiza la ruta en la tabla usuarios
                if(move_uploaded_file($rutaTemp,$ruta)) {
        
                    //Prepara el contenido del campo imgSrc
                    $imgSRC = "http://localhost/".basename(ROOT)."/images/".$nombreFoto;
            
                    $eval = "UPDATE users SET imgSrc=? WHERE id=?";
                    $peticion = $this->db->prepare($eval);
                    $peticion->execute([$imgSRC,IDUSER]);
            
                    http_response_code(201);
                    exit(json_encode("Imagen actualizada correctamente"));
                } else {
                    http_response_code(500);
                    exit(json_encode(["error" => "Ha habido un error con la subida"]));      
                }
            }
        }  else {
            http_response_code(400);
            exit(json_encode(["error" => "No se han enviado todos los parametros"]));
        }
    }
}
