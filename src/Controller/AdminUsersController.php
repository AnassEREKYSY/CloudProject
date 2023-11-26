<?php

namespace App\Controller;

use App\Entity\Member;
use App\Form\MemberFormType;
use App\Repository\MemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminUsersController extends AbstractController
{
    #[Route('/admin/users', name: 'app_admin_users')]
    public function index(Request $request, PaginatorInterface $paginator, MemberRepository $memberRepository): Response 
    {
        $searchTerm = $request->query->get('search');
        $queryBuilder = $memberRepository->createQueryBuilder('u');

        if ($searchTerm) {
            $queryBuilder
                ->where('u.pseudo LIKE :searchTerm OR u.email LIKE :searchTerm OR u.nom LIKE :searchTerm OR u.prenom LIKE :searchTerm')
                ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }

        $query = $queryBuilder->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );

        $methods = get_class_methods($pagination);
        dump($methods);


        return $this->render('admin_users/index.html.twig', [
            'controller_name' => 'AdminUsersController',
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
        ]);
    }

     // Ajout d'un véhicule

     #[Route('/admin/user', name:'admin_add_user', methods: ['POST','GET'])]
    public function addUser(Request $request, EntityManagerInterface $em,UserPasswordHasherInterface $passwordEncoder): Response
    {

        $form = $this->createForm(MemberFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = new Member();
            
            $user->setPseudo($form->get('pseudo')->getData());
            $user->setNom($form->get('nom')->getData());
            $user->setEmail($form->get('email')->getData());
            $user->setPrenom($form->get('prenom')->getData());
            $user->setStatut($request->request->get('statut'));
            $user->setCivilite($request->request->get('civilite'));
            $hashed_password = $passwordEncoder->hashPassword($user, $form->get("mdp")->getData());
            $user->setMdp($hashed_password);

            $em->persist($user);
            $em->flush();
            $this->addFlash('success',' Utilisateur ajouté avec succès');
            return $this->redirectToRoute('app_admin_users');
            
        }
            
        return $this->render('admin_users/add.html.twig', [
            'controller_name' => 'AdminUsersController',
            'form'=> $form->createView(),
        ]);
    }


    // Modification d'un utilisateur

    #[Route('/admin/user/{id}/edit', name:'admin_edit_user', methods: ['POST','GET'])]
    public function editUser(Request $request, int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(Member::class)->find($id);
        $form = $this->createForm(MemberFormType::class, $user);
        $form->handleRequest($request);
        
        
        if ($form->isSubmitted() && $form->isValid()) {
            
            $email_exist = $em->getRepository(Member::class)->findOneBy(['email' => $form->get('email')->getData()]);
            
            if ($email_exist && $email_exist->getId() != $user->getId()) {
                $this->addFlash('error','Cet email existe déjà');
                return $this->redirectToRoute('app_admin_users');
            }
           
            $user->setPseudo($form->get('pseudo')->getData());
            $user->setNom($form->get('nom')->getData());
            $user->setEmail($form->get('email')->getData());
            $user->setPrenom($form->get('prenom')->getData());
            $user->setStatut($request->request->get('statut'));
            $user->setCivilite($request->request->get('civilite'));

            $em->persist($user);
            $em->flush();
            $this->addFlash('success',' Utilisateur modifié avec succès');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render(
            'admin_users/edit.html.twig',
            [
                'controller_name' => 'AdminUsersController',
                'form' => $form->createView(),
                'user' => $user 
            ]
        );
    }


    // modifier mot de pass

    #[Route('/admin/user/{id}/edit-password', name:'admin_edit_password_user', methods: ['POST'])]
    public function edit_password(Request $request, int $id, EntityManagerInterface $em, UserPasswordHasherInterface $passwordEncoder ): Response
    {
        $user = $em->getRepository(Member::class)->find($id);

        $requestData = json_decode($request->getContent(), true);
        $password = $requestData['password'];

        if(!$password){
            return new JsonResponse(['error'=> 'Mot de passe non renseigné'], Response::HTTP_BAD_REQUEST);
        }

        $password = $passwordEncoder->hashPassword($user, $password);
        $user->setMdp($password);
        $em->persist($user);
        $em->flush();

        return new JsonResponse(['message' => 'Password updated successfully'], Response::HTTP_OK);




    }


     // Suppression d'un véhicule

     #[Route('/admin/user/{id}/remove', name:'admin_remove_user', methods: ['GET'])]
    public function removeUser(Request $request, EntityManagerInterface $em, int $id): Response
    {

        $user = $em->getRepository(Member::class)->find($id);
        if (!$user) {
            $this->addFlash('error',' Utilisateur introuvable !');
            return $this->redirectToRoute('app_admin_users');
        }

        $em->remove($user);
        $em->flush();
        $this->addFlash('success','Utilisateur supprimé avec succès !');

        return $this->redirectToRoute('app_admin_users');
    }

     // detail d'un véhicule

     #[Route('/admin/user/{id}/view', name:'admin_view_user', methods: ['GET'])]
    public function viewUser(Request $request, EntityManagerInterface $em, int $id): Response
    {

        $user = $em->getRepository(Member::class)->find($id);
        if (!$user) {
            $this->addFlash('error',' Utilisateur introuvable !');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render( 
            'admin_users/view.html.twig',
            [
                'controller_name' => 'AdminUsersController',
                'user' => $user 
            ]);

    }


}
