<?php
// src/EventListener/JwtUserListener.php

namespace App\EventListener;

use App\Repository\MemberRepository;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class JwtUserListener implements EventSubscriberInterface
{
    private $jwtEncoder;
    private $memberRepository;

    public function __construct(JWTEncoderInterface $jwtEncoder, MemberRepository $memberRepository)
    {
        $this->jwtEncoder = $jwtEncoder;
        $this->memberRepository = $memberRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        try{

        $request = $event->getRequest();        
        $routeName = $request->attributes->get('_route');
        
        $is_auth = strpos($routeName, 'auth');
        
        if($is_auth != false){
            return;
        }
        $user_protected = strpos($routeName, 'user');
        
        $cookie = $request->cookies->get('BEARER');
        if ($cookie) {
            $data = $this->jwtEncoder->decode($cookie);
            $user['id'] = $data['id'];
            $updated_user = $this->memberRepository->findOneBy([ 'id' =>$user['id']]);
            $user['nom'] = $updated_user->getNom();
            $user['prenom'] = $updated_user->getPrenom();
            $user['pseudo'] = $updated_user->getPseudo();
            $user['email'] = $updated_user->getEmail();
            $user['civilite'] = $updated_user->getCivilite();
            $user['statut'] = $updated_user->getStatut();
            $request->attributes->set('user', $user);
        }else {
            
            if($user_protected !== false){
                $response = new RedirectResponse('/login');
                $event->setResponse($response);
            }else{
                return;
            }
        }
    }catch( \Exception $e){
        if($user_protected !== false){
            
            $response = new RedirectResponse('/login');
            $event->setResponse($response);
        }else{
           return;
        }
    }
    }
}
