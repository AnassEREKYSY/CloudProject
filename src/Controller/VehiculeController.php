<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use App\Repository\MemberRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VehiculeController extends AbstractController
{
    #[Route('/', name: 'app_vehicules')]
    public function index(): Response
    {
        return $this->render('vehicule/index.html.twig', [
            'controller_name' => 'VehiculeController',
        ]);
    }

    #[Route('/vehicules', name: 'app_vehicule_list')]
    public function vehicules(Request $request, CommandeRepository $commandeRepository, VehiculeRepository $vehiculeRepository): Response
    {


       
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');

        $heureDebut = $request->query->get('heureDebut');
        $heureFin = $request->query->get('heureFin');

        $dateDebut = new \DateTime($dateDebut . ' ' . $heureDebut);
        $dateFin = new \DateTime($dateFin . ' ' . $heureFin);

        $user =$request->attributes->get("user");

        if (!$user) {
            return $this->redirectToRoute('login_form', [
                'redirect' => "dateDebut=".$dateDebut->format('Y-m-d H:i:s')."&dateFin=".$dateFin->format('Y-m-d H:i:s')
                ]);
        }


        $reserved_vehicules = $commandeRepository->findReservedVehiculeIds($dateDebut, $dateFin);


        $vehicules = $vehiculeRepository->findAll();

        $vehicule_array = [];
        foreach ($vehicules as $key => $vehicule) {
            if (!in_array($vehicule->getId(), $reserved_vehicules)) {
                $prixTotal = $vehicule->getPrixJournalier() * $dateDebut->diff($dateFin)->days;
                $vehicule_array[] = [
                    "id" => $vehicule->getId(),
                    "marque" => $vehicule->getMarque(),
                    "modele" => $vehicule->getModele(),
                    "description" => $vehicule->getDescription(),
                    "titre" => $vehicule->getTitre(),
                    "prixJournalier" => $vehicule->getPrixJournalier(),
                    "prixTotal" => $prixTotal,
                    "image" => $vehicule->getPhoto(),
                ];
            }
        }

        return $this->render('vehicule/vehicules.html.twig', [
            'controller_name' => 'VehiculeController',
            "vehicules" => $vehicule_array,
            "dateDebut" => $dateDebut,
            "dateFin" => $dateFin,
        ]);
    }

    #[Route('/reserver/{id}', name: 'app_vehicule_reserver')]
    public function reserver($id, Request $request, CommandeRepository $commandeRepository, VehiculeRepository $vehiculeRepository, EntityManagerInterface $em, MemberRepository $memberRepository): Response
    {
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');

        $dateDebut = new \DateTime($dateDebut);
        $dateFin = new \DateTime($dateFin);

        $vehicule = $vehiculeRepository->findOneBy(['id' => $id]);

        $prixTotal = $vehicule->getPrixJournalier() * $dateDebut->diff($dateFin)->days;
        $user =$request->attributes->get("user");
        $membre = $memberRepository->findOneBy(['id' => $user["id"]]);
        $commande = new Commande();
        $commande->setIdMembre($membre);
        $commande->setIdVehicule($vehicule);
        $commande->setDateHeureDepart($dateDebut);
        $commande->setDateHeureFin($dateFin);
        $commande->setPrixTotal($prixTotal);
        $commande->setDateEnregistrement(new \DateTime());

        $vehicule_dispo = $commandeRepository->isVehicleAvailable($vehicule->getId(), $dateDebut, $dateFin);

        if (!$vehicule_dispo) {
            $this->addFlash('error', 'Véhicule non disponible pour cette période');
            return $this->redirectToRoute('app_vehicule_list');
        }

        $em->persist($commande);
        $em->flush();

        return $this->redirectToRoute('profile_user');
    }
}
