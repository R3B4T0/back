<?php

namespace App\Security;

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Firebase\JWT\JWT;
use App\Entity\Usuario;

class JwtAuthenticator extends AbstractGuardAuthenticator {

    private $em;
    private $params;

    public function __construct(EntityManagerInterface $em, ContainerBagInterface $params) {
        $this->em = $em;
        $this->params = $params;
    }

    public function start(Request $request, AuthenticationException $authException = null) {
        $data = [
            'respuesta' => 'Usuario no logueado'
        ];
        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supports(Request $request) {
        return $request->headers->has('Authorization');
    }

    public function getCredentials(Request $request) {
        return $request->headers->get('Authorization');
    }

    public function getUser($credenciales, UserProviderInterface $userProvider) {
        try {
            $jwt = (array) JWT::decode(
                            $credenciales,
                            $this->params->get('jwt_secret'),
                            ['HS256']
            );
            $usuario = $this->em->getRepository(Usuario::class)
                            ->findOneBy(['email' => $jwt['usuario']]);
            return $usuario;
        } catch (\Exception $exception) {
            throw new AuthenticationException($exception->getMessage());
        }
    }

    public function checkCredentials($credentials, UserInterface $user) {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception) {
        return new JsonResponse([
            'error' => $exception->getMessage()
                ], Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey) {
        return;
    }

    public function supportsRememberMe() {
        return false;
    }

}