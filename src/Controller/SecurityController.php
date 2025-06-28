<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserSecurityFormType;
use App\Module\Authorization\Service\GrantsResolver;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class SecurityController
 * @package App\Controller
 */
class SecurityController extends BaseController
{
    /**
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    #[Route(path: '/login', name: 'security_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
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
     * @return Response
     */
    #[Route(path: '/users', name: 'security_users', options: ['expose' => true], methods: ['GET'])]
    public function users(): Response
    {
        return $this->render('security/users.html.twig', []);
    }

    #[Route(path: '/logout', name: 'security_logout')]
    public function logout()
    {}

    /**
     * @isGranted("ROLE_ADMIN")
     * @param User $user
     * @return Response
     */
    #[Route(path: '/users/edit/{id}', name: 'security_user_edit', options: ['expose' => true])]
    public function editUserView(User $user): Response
    {
        return $this->render('security/single_user.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @isGranted("ROLE_ADMIN")
     * @return Response
     */
    #[Route(path: '/users/add', name: 'view_security_user_new', options: ['expose' => true])]
    public function viewAddUser(): Response
    {
        return $this->render('security/single_user.html.twig', [
            'user' => null
        ]);
    }

    /**
     * @isGranted("ROLE_ADMIN")
     * @param UserRepository $repository
     * @return JsonResponse
     *
     * Zwraca wszystkich użytkowników
     */
    #[Route(path: '/users/fetch', name: 'users_fetch', options: ['expose' => true], methods: ['GET'])]
    public function fetchUsers(UserRepository $repository): JsonResponse
    {
        return $this->json($repository->findAll(), Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['user_main']
        ]);
    }

    /**
     * @isGranted("ROLE_ADMIN")
     * @param User $user
     * @return JsonResponse
     *
     * Zwraca jednego użytkownika
     */
    #[Route(path: '/users/fetch/{id}', name: 'user_fetch', options: ['expose' => true], methods: ['GET'])]
    public function fetchUser(User $user): JsonResponse
    {
        return $this->json($user, Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['user_main', '_main']
        ]);
    }

    /**
     * @isGranted("ROLE_ADMIN")
     * @param Request $request
     * @param User $user
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @param TranslatorInterface $t
     * @return JsonResponse
     *
     * Edytuje użytkownika
     */
    #[Route(path: '/user/{id}', name: 'user_edit', options: ['expose' => true], methods: ['PATCH'])]
    public function editUser(Request $request, User $user, EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasher, TranslatorInterface $t): Response
    {
        $form = $this->createForm(UserSecurityFormType::class, $user);

        try {
            $this->processForm($request, $form);

            /** @var User $user */
            $user = $form->getData();

            // jeśli przesłano nowe hasło to zmieniamy
            if ($form['passwordPlain']->getData()) {
                if (!$userPasswordHasher->isPasswordValid($user, $form['passwordOld']->getData())) {
                    throw new \Exception($t->trans('Stare hasło jest nieprawidłowe.', [], 'security'));
                }
                $user->setPassword($userPasswordHasher->hashPassword(
                    $user,
                    $form['passwordPlain']->getData())
                );
            }

            $em->persist($user);
            $em->flush();
        } catch (\Exception $e) {
            return $this->composeErrorResponse($e);
        }

        return new Response(null);
    }

    #[Route(path: '/user/grants', options: ['expose' => true], methods: ['GET'])]
    public function grants(Security $security, GrantsResolver $grantsResolver): JsonResponse
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('Authenticated user is not an instance of App\Entity\User.');
        }

        return new JsonResponse($grantsResolver->getGrants($user));
    }

    /**
     * @isGranted("ROLE_ADMIN")
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @return JsonResponse
     *
     * Tworzy nowego użytkownika
     */
    #[Route(path: '/user', name: 'user_add', options: ['expose' => true], methods: ['POST'])]
    public function addUser(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasher)
    {
        $form = $this->createForm(UserSecurityFormType::class);

        try {
            $this->processForm($request, $form);

            $user = $form->getData();

            $user->setPassword($userPasswordHasher->hashPassword(
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
