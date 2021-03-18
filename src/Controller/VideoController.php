<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Repository\VideoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class VideoController
 * @package App\Controller
 * 
 * @Route(path="/api/video/")
 */
class VideoController extends AbstractController
{
    private $videoRepository;

    public function __construct(VideoRepository $videoRepository)
    {
        $this->videoRepository = $videoRepository;
    }

    /**
     * @Route("insertar/{id}", name="insertar_video", methods={"POST"})
     */
    public function insertar(Request $request, Usuario $usuario): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $idUsuario = $this->getUser();
        $codigo = $data['codigo'];
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $codigo, $match);
        $youtube_id = $match[1];

        $this->videoRepository->insertar($youtube_id, $idUsuario);

        return new JsonResponse(['status' => 'Video insertado'], Response::HTTP_CREATED);
    }
}
