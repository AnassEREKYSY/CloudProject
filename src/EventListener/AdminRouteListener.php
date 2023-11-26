<?php 
// src/EventListener/AdminRouteListener.php

namespace App\EventListener;

use App\Repository\MemberRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AdminRouteListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }
    private $jwtEncoder;
    private $memberRepository;
    public function __construct(JWTEncoderInterface $jwtEncoder,MemberRepository $memberRepository)
    {
        $this->jwtEncoder = $jwtEncoder;
        $this->memberRepository = $memberRepository;

    }
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');
        $isAdminRoute = strpos($routeName, 'admin') !== false;
        if ($isAdminRoute) {
            $cookie = $request->cookies->get('BEARER');
            if ($cookie) {
                $data = $this->jwtEncoder->decode($cookie);
                $user['id'] = $data['id'];
                $updated_user = $this->memberRepository->findOneBy(array('id'=> $data['id']));
                $user['pseudo'] = $updated_user->getPseudo();
                $user['nom'] = $updated_user->getNom();
                $user['prenom'] = $updated_user->getPrenom();
                $user['email'] = $updated_user->getEmail();
                $user['statut'] = $updated_user->getStatut();
                $request->attributes->set('user', $user);
            }
            if ($user == null){
                $response = new RedirectResponse('/login');
                $event->setResponse($response);
            }else if (!$user || $user['statut'] != 'admin') {
                throw new AccessDeniedHttpException('Vous n\'avez pas les droits pour accéder à cette page');
            }
        }
        
    }   
}
