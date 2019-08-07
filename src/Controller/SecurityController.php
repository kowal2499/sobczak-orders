<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="security_login")
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("/users", name="security_users")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function users(Request $request)
    {
        return $this->render('security/users.html.twig', []);
    }

    /**
     * @Route("/logout", name="security_logout")
     */
    public function logout()
    {
    }

    /**
     * @Route("/fetch_users", name="security_fetch", options={"expose"=true})
     */
    public function fetchUsers(UserRepository $repository)
    {
        return new JsonResponse(array_map(function(User $user) {
            return [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ];
            }, $repository->findAll())
        );
    }

    /**
     * @Route("/edit_user/{id}", name="security_user_edit", options={"expose"=true})
     * @param $id
     * @param User $user
     * @param UserRepository $repository
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editUser($id, User $user, UserRepository $repository)
    {
        return $this->render('security/single_user.html.twig', [
            'user' => $user
        ]);
    }
}
