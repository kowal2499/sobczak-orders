<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserSecurityFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class SecurityController
 * @package App\Controller
 */
class SecurityController extends BaseController
{
    /**
     * @Route("/login", name="security_login")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
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
     * @isGranted("ROLE_ADMIN")
     * @Route("/users", name="security_users", options={"expose"=true}, methods={"GET"})
     * @return Response
     */
    public function users()
    {
        return $this->render('security/users.html.twig', []);
    }

    /**
     * @Route("/logout", name="security_logout")
     */
    public function logout()
    {}

    /**
     * @isGranted("ROLE_ADMIN")
     * @Route("/users/edit/{id}", name="security_user_edit", options={"expose"=true})
     * @param User $user
     * @return Response
     */
    public function editUserView(User $user)
    {
        return $this->render('security/single_user.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @isGranted("ROLE_ADMIN")
     * @Route("/users/add", name="view_security_user_new", options={"expose"=true})
     * @return Response
     */
    public function viewAddUser()
    {
        return $this->render('security/single_user.html.twig', [
            'user' => null
        ]);
    }

    /**
     * @isGranted("ROLE_ADMIN")
     * @Route("/users/fetch", name="users_fetch", options={"expose"=true}, methods={"GET"})
     * @param UserRepository $repository
     * @return JsonResponse
     *
     * Zwraca wszystkich użytkowników
     */
    public function fetchUsers(UserRepository $repository)
    {
        return $this->json($repository->findAll(), Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['user_main']
        ]);
    }

    /**
     * @isGranted("ROLE_ADMIN")
     * @Route("/users/fetch/{id}", name="user_fetch", options={"expose"=true}, methods={"GET"})
     * @param User $user
     * @return JsonResponse
     *
     * Zwraca jednego użytkownika
     */
    public function fetchUser(User $user): JsonResponse
    {
        return $this->json($user, Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['user_main', '_main']
        ]);
    }

    /**
     * @isGranted("ROLE_ADMIN")
     * @Route("/user/{id}", name="user_edit", options={"expose"=true}, methods={"PATCH"})
     * @param Request $request
     * @param User $user
     * @param EntityManagerInterface $em
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     * @param TranslatorInterface $t
     * @return JsonResponse
     *
     * Edytuje użytkownika
     */
    public function editUser(Request $request, User $user, EntityManagerInterface $em, UserPasswordEncoderInterface $userPasswordEncoder, TranslatorInterface $t): JsonResponse
    {
        $form = $this->createForm(UserSecurityFormType::class, $user);

        try {
            $this->processForm($request, $form);

            /** @var User $user */
            $user = $form->getData();

            // jeśli przesłano nowe hasło to zmieniamy
            if ($form['passwordPlain']->getData()) {
                if (!$userPasswordEncoder->isPasswordValid($user, $form['passwordOld']->getData())) {
                    throw new \Exception($t->trans('Stare hasło jest nieprawidłowe.', [], 'security'));
                }
                $user->setPassword($userPasswordEncoder->encodePassword(
                    $user,
                    $form['passwordPlain']->getData())
                );
            }

            $em->persist($user);
            $em->flush();
        } catch (\Exception $e) {
            return $this->composeErrorResponse($e);
        }

        return $this->json($user, Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['user_main']
        ]);
    }

    /**
     * @isGranted("ROLE_ADMIN")
     * @Route("/user", name="user_add", options={"expose"=true}, methods={"POST"})
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     * @return JsonResponse
     *
     * Tworzy nowego użytkownika
     */
    public function addUser(Request $request, EntityManagerInterface $em, UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $form = $this->createForm(UserSecurityFormType::class);

        try {
            $this->processForm($request, $form);

            $user = $form->getData();
            $user->setPassword($userPasswordEncoder->encodePassword(
                $user,
                $form['passwordPlain']->getData()
            ));

            $em->persist($user);
            $em->flush();
        } catch (\Exception $e) {
            return $this->composeErrorResponse($e);
        }

        return new JsonResponse(['id' => $user->getId()]);
    }
}
