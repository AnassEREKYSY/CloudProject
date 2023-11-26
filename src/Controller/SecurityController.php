<?php

namespace App\Controller;

use App\Entity\Member;
use App\Form\LoginFormType;
use App\Form\ProfileType;
use App\Form\RegistrationFormType;
use App\Repository\CommandeRepository;
use App\Repository\MemberRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;

class SecurityController extends AbstractController
{
    #[Route('/register', name: 'register')]

    public function register(Request $request, UserPasswordHasherInterface $passwordEncoder, EntityManagerInterface $em,MemberRepository $memberRepository): Response
    {
        $user = new Member();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setMdp(
                $passwordEncoder->hashPassword($user, $form->get("mdp")->getData())
            );

            // verify if email exist

            $email = $form->get("email")->getData();

            $email_exist = $memberRepository->findOneBy(["email"=> $email]);

            if ($email_exist) {
                return $this->render('security/register.html.twig', [
                    'registrationForm' => $form->createView(),
                    'error' => "Email déjà utilisé"
                ]);

            }

            // verify if pseudo exist

            $pseudo_exist = $memberRepository->findOneBy(["pseudo"=> $user->getPseudo()]);

            if ($pseudo_exist) {
                return $this->render('security/register.html.twig', [
                    'registrationForm' => $form->createView(),
                    'error' => "Pseudo déjà utilisé"
                ]);
            }

            $user->setCivilite($request->request->get('civilite'));

            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('login_form');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
            'error' => ""
        ]);
    }

    #[Route('/login', name: 'login_form')]

    public function login( JWTTokenManagerInterface $tokenManager, MemberRepository $memberRepository,Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {

        $query = $request->query->get('redirect');
        $form = $this->createForm(LoginFormType::class);
        $form->handleRequest($request);


        if($form->isSubmitted() && $form->isValid()) {
            $pseudo = $form->get('pseudo')->getData();
            $password = $form->get('mdp')->getData();
            
            $user = null;
            $email_exist = $memberRepository->findOneBy(['email'=> $pseudo]);
            if ($email_exist) {
                $user = $email_exist;
            } else {
                $pseudo_exist = $memberRepository->findOneBy(['pseudo'=> $pseudo]);
                if ($pseudo_exist) {
                    $user = $pseudo_exist;
                }
            }
            
            $form = $this->createForm(LoginFormType::class);
            
            if (!$user) {
                return $this->render('security/login.html.twig', [
                    'loginForm'=> $form->createView(),
                    'error'=> 'Utilisateur non trouvé',
                    ]);
                }
            

        $password_valid = $passwordHasher->isPasswordValid($user, $password);  
        
        if (!$password_valid) {
                return $this->render('security/login.html.twig', [
                    'loginForm'=> $form->createView(),
                    'error'=> 'Mot de passe incorrect',
                ]);
        }

        $userData = [
                'id' => $user->getId(),
                'email'=> $user->getEmail(),
                'pseudo'=> $user->getPseudo(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'civilite' => $user->getCivilite(),
                'statut' => $user->getStatut(),
                'date_enregistrement' => $user->getDateEnregistrement(),
            ];
        
        $token = $tokenManager->createFromPayload($user, $userData);
        
        if ($query) {
            $response = new RedirectResponse('/vehicules?'.$query);
        } else {
            $response = new RedirectResponse('/');
        }

        $response->headers->setCookie(
            new Cookie(
                'BEARER',
                $token,
                time() + (3600 * 24), 
                '/',
                null,
                false,
                true 
            )

        );

        return $response;
    }


        return $this->render('security/login.html.twig', [
            'loginForm' => $form->createView(),
            'error' => ''
        ]);

        
    }

    #[Route('/logout', name: 'logout')]
    public function logoutAction(Request $request): Response
    {
        $response = new RedirectResponse("/login");
        $response->headers->setCookie(
                new Cookie(
                    "BEARER",
                    null,
                    new \DateTime("-1 day"),
                )
            );
        return $response;
    }


    #[Route("/profile/email", name: "profile_email_user", methods: ["POST"])]
    public function profile_email(Request $request, MemberRepository $memberRepository, EntityManagerInterface $entityManager): Response
    {
        $user_data = $request->attributes->get("user");
        $email = $request->request->get("email");

        $user_exist = $memberRepository->findOneBy(["id"=> $user_data["id"]]);

        if (!$email) {
            $this->addFlash("error","Veuillez remplir tous les champs");
            return $this->redirectToRoute("profile_user");
        }

        $email_exist = $memberRepository->findOneBy(["email"=> $email]);

        if ($email_exist) {
            $this->addFlash("error","Email déjà utilisé");
            return $this->redirectToRoute("profile_user");
        }

        $user_exist->setEmail($email);
        // save to db
        $entityManager->persist($user_exist);
        $entityManager->flush();
        $this->addFlash("success","Email modifié avec succès");
        return $this->redirectToRoute("profile_user");
    }
    #[Route("/profile/password", name: "profile_password_user", methods: ["POST"])]
    public function profile_password(Request $request, MemberRepository $memberRepository, EntityManagerInterface $entityManager,UserPasswordHasherInterface $passwordHasher): Response
    {
        $user_data = $request->attributes->get("user");
        $password = $request->request->get("new_password");
        $old_password = $request->request->get("old_password");

        $user_exist = $memberRepository->findOneBy([
            "id"=> $user_data["id"],
        ]);

        
        if (!$password || !$old_password) {
            $this->addFlash("error","Veuillez remplir tous les champs");
            return $this->redirectToRoute("profile_user");
        }
        
        $password_valid = $passwordHasher->isPasswordValid($user_exist, $old_password);  

        if (!$password_valid) {
            $this->addFlash("error","Ancien mot de passe incorrect");
            return $this->redirectToRoute("profile_user");
        }

        $user_exist->setMdp($password);

        $entityManager->persist($user_exist);
        $entityManager->flush();

        $this->addFlash("success","Mot de passe modifié avec succès");
        return $this->redirectToRoute("profile_user");

    }


    #[Route("/profile", name: "profile_user")]
    public function profile(Request $request, MemberRepository $memberRepository, EntityManagerInterface $entityManager, CommandeRepository $commandeRepository): Response
    {
        $user_data = $request->attributes->get("user");
        $user = new Member();
        $user->setPseudo($user_data["pseudo"]);
        $user->setEmail($user_data["email"]);
        $user->setPrenom($user_data["prenom"]);
        $user->setCivilite($user_data["civilite"]);
        $user->setNom($user_data["nom"]);
        $user->setStatut($user_data["statut"]);
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        $mes_commades = $commandeRepository->findBy(["id_membre"=> $user_data["id"]]);
        

        if ($form->isSubmitted() && $form->isValid()) {
            $user_exist = $memberRepository->findOneBy(["id"=> $user_data["id"]]);
            $user_exist->setNom($form->get("nom")->getData());
            $user_exist->setPrenom($form->get("prenom")->getData());
            $user_exist->setCivilite($request->request->get("civilite"));
            $entityManager->persist($user_exist);
            $entityManager->flush();
            $this->addFlash("success","Profil modifié avec succès");
            return $this->redirectToRoute("profile_user");
            
       }

        return $this->render("security/profile.html.twig", [
            "user" => $user,
            "profileForm"=> $form->createView(),
            "error" => "",
            "mescommandes" => $mes_commades
        ]);
        
    }
}