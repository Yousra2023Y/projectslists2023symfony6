<?php

namespace App\Controller;
use App\Entity\Project;
use App\Form\ProjectFormType;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class ListController extends AbstractController
{
    #[Route('/list', name: 'app_list')]
    public function index(ProjectRepository $projectRepository): Response
    {
        
        $projects = $projectRepository->findAll();
        
        
        return $this->render('list/index.html.twig', [
            'controller_name' => 'ListController',
            'projects'=>$projects
        ]);
    }
     #[Route('/list/create/', name: 'create_project')]
    public function create(Request $request,SluggerInterface $slugger, EntityManagerInterface $entityManager): Response
    {
        $projectt = new Project();
        $form = $this->createForm(ProjectFormType::class, $projectt);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $projectt = $form->getData();
            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            $imageProject = $form->get('image')->getData();

            if ($imageProject) {
                $originalFilename = pathinfo($imageProject->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageProject->guessExtension(); 
                try {
                    $imageProject->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
            $projectt->setImage($newFilename);
            $url = $this->getParameter('images_directory').$newFilename;
            //var_dump($url);die();
            $projectt->setUrl($url);
            }
            // ... perform some action, such as saving the task to the database
            $entityManager->persist($projectt);
            $entityManager->flush();
            return $this->redirectToRoute('app_list');
        }
        return $this->render('list/create.html.twig', [
            'controller_name' => 'ListController',
            'projectForm' => $form->createView(),
        ]);
    }
    
        /*Function edit a project*/
    #[Route("/edit/{id<\d+>}", name: "app_edit_project")]
    public function editProject(Request $request, Project $projet, EntityManagerInterface $em)
    {
        $form = $this->createForm(ProjectFormType::class, $projet);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $em->flush();
            return $this->redirectToRoute('app_list');
        }
        return $this->render('list/edit.html.twig',array(
            'projectForm'=>$form->createView()
        ));
    }

    /*Function delete projet*/
    #[Route("/delete/{id<\d+>}", name: "app_delete_project")]
    public function deleteProject(Project $projet, EntityManagerInterface $em)
    {
        $em ->remove($project);
        $em ->flush();
        return $this->redirectToRoute('app_list'); 
    }
}
