<?php

namespace App\Controller;

use App\Entity\Vehicule;
use App\Form\VehiculeFormType;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminVehiculesController extends AbstractController
{
    #[Route('/admin/vehicules', name: 'app_admin_vehicules')]
public function index(Request $request, VehiculeRepository $vehiculeRepository, PaginatorInterface $paginator): Response
{
    $searchTerm = $request->query->get('search');
    $queryBuilder = $vehiculeRepository->createQueryBuilder('v');

    if ($searchTerm) {
        $queryBuilder
            ->where('v.titre LIKE :searchTerm OR v.marque LIKE :searchTerm OR v.modele LIKE :searchTerm')
            ->setParameter('searchTerm', '%'.$searchTerm.'%');
    }

    $query = $queryBuilder->getQuery();

    $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        5
    );

    return $this->render('admin_vehicules/index.html.twig', [
        'controller_name' => 'AdminVehiculesController',
        'pagination' => $pagination,
        'searchTerm' => $searchTerm,
    ]);
}

    // Ajout d'un véhicule

    #[Route('/admin/vehicule', name:'admin_add_vehicule', methods: ['POST','GET'])]
    public function addVehicule(Request $request, EntityManagerInterface $em): Response
    {

        $form = $this->createForm(VehiculeFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $vehicule = new Vehicule();
            $file = $request->files->get('photo');
            
            $vehicule->setTitre($form->get('titre')->getData());
            $vehicule->setMarque($form->get('marque')->getData());
            $vehicule->setModele($form->get('modele')->getData());
            $vehicule->setDescription($form->get('description')->getData());
            $vehicule->setPrixJournalier($form->get('prix_journalier')->getData());
            if ($file) {
                $fileName = md5(uniqid()) . '.' . $file->guessExtension();
                $file->move($this->getParameter('uploads_directory'), $fileName);
                $vehicule->setPhoto('/uploads/'.$fileName);
            }
            $em->persist($vehicule);
            $em->flush();
            $this->addFlash('success','Véhicule ajouté avec succès !');
            return $this->redirectToRoute('app_admin_vehicules');
            
        }
            
        return $this->render('admin_vehicules/add.html.twig', [
            'controller_name' => 'AdminVehiculesController',
            'form'=> $form->createView(),
        ]);
    }


    // Modification d'un véhicule

    #[Route('/admin/vehicule/{id}/edit', name:'admin_edit_vehicule', methods: ['GET','POST'])]
    public function editVehicule(Request $request, EntityManagerInterface $em, int $id): Response
    {

        $vehicule = $em->getRepository(Vehicule::class)->find($id);

        if (!$vehicule) {
            $this->addFlash('error','Véhicule introuvable !');
            return $this->redirectToRoute('app_admin_vehicules');
        }

        $form = $this->createForm(VehiculeFormType::class, $vehicule);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $request->files->get('photo');
            $vehicule->setTitre($form->get('titre')->getData());
            $vehicule->setMarque($form->get('marque')->getData());
            $vehicule->setModele($form->get('modele')->getData());
            $vehicule->setDescription($form->get('description')->getData());
            $vehicule->setPrixJournalier($form->get('prix_journalier')->getData());

            if ($file) {
                $fileName = md5(uniqid()) . '.' . $file->guessExtension();
                $file->move($this->getParameter('uploads_directory'), $fileName);
                $vehicule->setPhoto('/uploads/'.$fileName);
            }


            $em->persist($vehicule);
            $em->flush();

            $this->addFlash('success','Véhicule modifié avec succès !');
        }


        return $this->render('admin_vehicules/modifier.html.twig', [
            'controller_name' => 'AdminVehiculesController',
            'form'=> $form->createView(),
            'vehicule'=> $vehicule,
        ]);
    }


    // Suppression d'un véhicule

    #[Route('/admin/vehicule/{id}/remove', name:'admin_remove_vehicule', methods: ['GET'])]
    public function removeVehicule(Request $request, EntityManagerInterface $em, int $id): Response
    {

        $vehicule = $em->getRepository(Vehicule::class)->find($id);
        if (!$vehicule) {
            $this->addFlash('error',' Véhicule introuvable !');
            return $this->redirectToRoute('app_admin_vehicules');
        }

        $em->remove($vehicule);
        $em->flush();
        $this->addFlash('success','Véhicule supprimé avec succès !');

        return $this->redirectToRoute('app_admin_vehicules');
    }
    // view d'un véhicule

    #[Route('/admin/vehicule/{id}/view', name:'admin_view_vehicule', methods: ['GET'])]
    public function viewVehicule(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $vehicule = $em->getRepository(Vehicule::class)->find($id);

        if (!$vehicule) {
            $this->addFlash('error',' Véhicule introuvable !');
            return $this->redirectToRoute('app_admin_vehicules');
        }

        return $this->render(
            'admin_vehicules/view.html.twig',
            [
                'vehicule' => $vehicule,
            ]
        );
        
    }
}
