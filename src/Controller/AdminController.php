<?php

namespace App\Controller;

use App\Entity\Admin;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin')]
    public function index(Request $request, ManagerRegistry $myDoctrine): Response
    {

        $session = $request->getSession();

        $form = $this->createFormBuilder()
            ->add('login', TextType::class)
            ->add('password', TextType::class)
            ->add('Login', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $details = $form->getData();
            $login = $details['login'];
            $password = $details['password'];

            $user = $myDoctrine->getRepository(Admin::class)->findOneBy(['login' => $login]);

            if (!$user) {
                return $this->redirect('/');
            }

            if ($user->getPassword() == $password) {
                $session->set('user_role',  $user->getRole());
                return $this->redirect('/news');
            }

            dd($details);
        }


        return $this->render('admin/index.html.twig', [
            'form' => $form
        ]);
    }
}
