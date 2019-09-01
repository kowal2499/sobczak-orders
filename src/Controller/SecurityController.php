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
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SecurityController extends AbstractController
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
     * @Route("/users", name="security_users")
     * @param Request $request
     * @return Response
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
     * @param UserRepository $repository
     * @return JsonResponse
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
     * @Route("/fetch_user/{id}", name="security_fetch_user", options={"expose"=true})
     * @param User $user
     * @return JsonResponse
     */
    public function fetchSingleUser(User $user): JsonResponse
    {
        return $this->json($user, Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['public_attr']
        ]);
    }

    /**
     * @Route("/store_user", name="security_store_user", options={"expose"=true})
     * @param Request $request
     * @param UserRepository $repository
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function storeUser(Request $request, UserRepository $repository, EntityManagerInterface $em, UserPasswordEncoderInterface $userPasswordEncoder): JsonResponse
    {

        try {
            $userId = $request->request->getInt('id');
            $newFirstName = $request->request->get('firstName');
            $newLastName = $request->request->get('lastName');
            $newEmail = $request->request->get('email');
            $password = $request->request->get('password');
            $passwordOld = $request->request->get('passwordOld');

            if (count(array_filter([$newFirstName, $newLastName, $newEmail])) !== 3) {
                throw new \Exception('Brak wymaganych danych.');
            }

            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Niepoprawny adres email.');
            }

            if ($userId) {
                $user = $repository->find($userId);
                if (!$user) {
                    throw new \Exception('Nieprawidłowy identyfikator użytkownika.');
                }

                if ($password && !$userPasswordEncoder->isPasswordValid($user, $passwordOld)) {
                    throw new \Exception('Stare hasło jest nieprawidłowe.');
                };

            } else {
                $user = new User();
                if (!$password) {
                    throw new \Exception('Brak hasła.');
                }
            }

            $user->setFirstName($newFirstName);
            $user->setLastName($newLastName);
            $user->setEmail($newEmail);

            if ($password) {
                $user->setPassword($userPasswordEncoder->encodePassword($user, $password));
            }

            $em->persist($user);
            $em->flush();

        } catch (\Exception $e) {
            return $this->json([$e->getMessage()], RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($user, Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['public_attr']
        ]);
    }

    /**
     * @Route("/edit_user/{id}", name="security_user_edit", options={"expose"=true})
     * @param User $user
     * @return Response
     */
    public function editUser(User $user)
    {
        return $this->render('security/single_user.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/new_user/", name="view_security_user_new", options={"expose"=true})
     * @return Response
     */
    public function viewAddUser()
    {
        return $this->render('security/single_user.html.twig', [
            'user' => null
        ]);
    }

    /**
     * @Route("/add_user", name="security_user_add", options={"expose"=true})
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     * @return JsonResponse
     */
    public function ApiAddUser(Request $request, EntityManagerInterface $em, UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $user = new User();

        $user
            ->setFirstName($request->request->get('first_name'))
            ->setLastName($request->request->get('last_name'))
            ->setEmail($request->request->get('email'))
            ->setPassword($userPasswordEncoder->encodePassword($user, $request->request->get('password')));
        ;

        $response = new JsonResponse();


        $em->persist($user);
        $em->flush();

        return $response;
    }
}
