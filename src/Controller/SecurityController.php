<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/inscription", name="security_registration")
     */
    public function registration(Request $request, ObjectManager $manager, UserPasswordEncoderInterface $encoder)
    {
        $user = new User();

        $form = $this->createForm(RegistrationType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hash = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($hash);
            $manager->persist($user);
            $manager->flush();

            return $this->redirectToRoute('security_login');
        }

        return $this->render('security/registration.html.twig', [
            'registrationForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/connexion", name="security_login")
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * @Route("/deconnexion", name="security_logout")
     */
    public function logout()
    { }

    /**
     * @Route("/admin/userModification", name="userModification")
     */
    public function userModification(Request $request, ObjectManager $manager)
    {
        // Récupération de tous les utilisateurs en BDD
        $repo = $this->getDoctrine()->getRepository(User::class);
        $user = $repo->findAll();

        foreach ($user as $users) {

            // Formulaire modification utilisateur
            $form = $this->createFormBuilder($users)
                ->add('username')
                ->add('email')
                ->add('roles')
                ->getForm();
        }
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($form);
            $manager->flush();

            return $this->redirectToRoute('userModification');
        }
        //  dd($form);
        //  dd($users);
        return $this->render('admin/userChange.html.twig', [
            'controller_name' => 'SecurityController',
            'users' => $users,
            'userChangeForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin", name="admin")
     */
    public function admin(AuthorizationCheckerInterface $authChecker)
    {
        if ($authChecker->isGranted('ROLE_ADMIN')) {
            return $this->render('admin/index.html.twig', [
                'controller_name' => 'SecurityController',
            ]);
        }
        return $this->redirectToRoute('exception');
    }
}
