<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\CommandFormType;
use App\Repository\MemberRepository;
use App\Repository\VehiculeRepository;
use DateTime;
use Knp\Component\Pager\PaginatorInterface;

class AdminCommandesController extends AbstractController
{
    #[Route('/admin/commandes', name: 'app_admin_commandes')]
    public function index(Request $request ,CommandeRepository $commandeRepository,PaginatorInterface $paginator,): Response
    {
        $searchTerm = $request->query->get('search');
        $queryBuilder = $commandeRepository->createQueryBuilder('c');

        if ($searchTerm) {
            $queryBuilder
                ->where('c.id LIKE :searchTerm')
                ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }

        $query = $queryBuilder->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );


        return $this->render('admin_commandes/index.html.twig', [
            'controller_name' => 'AdminCommandesController',
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
        ]);
    }


    // ajout command
    #[Route('/admin/commande', name: 'admin_add_command', methods: ['POST', 'GET'])]
    public function addCommand(Request $request, EntityManagerInterface $em, MemberRepository $memberRepository, VehiculeRepository $vehiculeRepository, CommandeRepository $commandeRepository): Response
    {
        $form = $this->createForm(CommandFormType::class);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $commande = new Commande();
            
            $commande->setPrixTotal($request->request->get('prix_total'));
            
            $membre = $memberRepository->findOneBy(['id' => $request->request->get('id_membre')]);
            $vehicule = $vehiculeRepository->findOneBy(['id' => $request->request->get('id_vehicule')]);

            $commande->setIdMembre($membre);
            $commande->setIdVehicule($vehicule);

            $date_debut = $request->request->get('date_debut');
            $temps_debut = $request->request->get('time_debut');

            $date_fin = $request->request->get('date_fin');
            $temps_fin = $request->request->get('time_fin');

            $dateHeureDepart = new \DateTime($date_debut . ' ' . $temps_debut);
            $commande->setDateHeureDepart($dateHeureDepart);

            $dateHeureFin = new \DateTime($date_fin . ' ' . $temps_fin);
            $commande->setDateHeureFin($dateHeureFin);
            
            $date_enregistrement = new \DateTime();
            $commande->setDateEnregistrement($date_enregistrement);

            $vehicule_dispo = $commandeRepository->isVehicleAvailable($vehicule->getId(), $dateHeureDepart, $dateHeureFin);

            if (!$vehicule_dispo) {
                $this->addFlash('error', 'Véhicule non disponible');
                return $this->redirectToRoute('app_admin_commandes');
            }

            $em->persist($commande);
            $em->flush();

            $this->addFlash('success','Commande ajoutée avec succès');

            return $this->redirectToRoute('app_admin_commandes');

        }


        return $this->render('admin_commandes/add.html.twig', [
            'controller_name' => 'AdminCommandesController',
            'form'=> $form->createView(),
        ]);   
    }

    #[Route('/admin/commande/edit/{id}', name: 'admin_edit_command', methods: ['POST', 'GET'])]
    public function editCommand(Request $request, EntityManagerInterface $em, MemberRepository $memberRepository, VehiculeRepository $vehiculeRepository, int $id, CommandeRepository $commandeRepository): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);

        if (!$commande) {
            $this->addFlash('danger', 'Commande introuvable');
            return $this->redirectToRoute('app_admin_commandes');
        }

        $form = $this->createForm(CommandFormType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commande->setPrixTotal($request->request->get('prix_total'));

            $membre = $memberRepository->findOneBy(['id' => $request->request->get('id_membre')]);
            $vehicule = $vehiculeRepository->findOneBy(['id' => $request->request->get('id_vehicule')]);

            $commande->setIdMembre($membre);
            $commande->setIdVehicule($vehicule);

            $date_debut = $request->request->get('date_debut');
            $temps_debut = $request->request->get('time_debut');

            $date_fin = $request->request->get('date_fin');
            $temps_fin = $request->request->get('time_fin');
            
            $dateHeureDepart = new \DateTime($date_debut . ' ' . $temps_debut);
            $commande->setDateHeureDepart($dateHeureDepart);

            $dateHeureFin = new \DateTime($date_fin . ' ' . $temps_fin);
            $commande->setDateHeureFin($dateHeureFin);

            $vehicule_dispo = $commandeRepository->isVehicleAvailable($vehicule->getId(), $dateHeureDepart, $dateHeureFin);

            if (!$vehicule_dispo) {
                $this->addFlash('error', 'Véhicule non disponible');
                return $this->redirectToRoute('app_admin_commandes');
            }

            $em->flush();

            $this->addFlash('success', 'Commande modifiée avec succès');

            return $this->redirectToRoute('app_admin_commandes');
        }

        return $this->render('admin_commandes/edit.html.twig', [
            'controller_name' => 'AdminCommandesController',
            'form' => $form->createView(),
            'commande' => $commande,
        ]);
    }

    //supprimer commande
    #[Route('/admin/commande/delete/{id}', name: 'admin_delete_command', methods: ['POST', 'GET'])]
    public function deleteCommand(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);

        if (!$commande) {
            $this->addFlash('danger', 'Commande introuvable');
            return $this->redirectToRoute('app_admin_commandes');
        }

        $em->remove($commande);
        $em->flush();

        $this->addFlash('success', 'Commande supprimée avec succès');

        return $this->redirectToRoute('app_admin_commandes');
    }

    //view commande

    #[Route('/admin/commande/{id}', name: 'admin_view_command', methods: ['POST', 'GET'])]
    public function viewCommand(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);

        if (!$commande) {
            $this->addFlash('danger', 'Commande introuvable');
            return $this->redirectToRoute('app_admin_commandes');
        }

        return $this->render('admin_commandes/view.html.twig', [
            'controller_name' => 'AdminCommandesController',
            'commande' => $commande,
        ]);
    }
}
