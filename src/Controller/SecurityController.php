<?php

namespace App\Controller;

use App\Entity\Customers2Users;
use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\Customers2UsersRepository;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class SecurityController
 * @package App\Controller
 */
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
     * @isGranted("ROLE_ADMIN")
     * @Route("/users", name="security_users", options={"expose"=true})
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
    {
    }

    /**
     * @isGranted("ROLE_ADMIN")
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
     * @isGranted("ROLE_ADMIN")
     * @Route("/fetch_user/{id}", name="security_fetch_user", options={"expose"=true})
     * @param User $user
     * @return JsonResponse
     */
    public function fetchSingleUser(User $user): JsonResponse
    {

        $connectedCustomers = $user->getCustomers();
        $c2u = [];
        if (!empty($connectedCustomers)) {
            foreach ($connectedCustomers as $connection) {
                $c2u[] = $connection->getId();
            }
        }

        $response = [
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'id' => $user->getId(),
            'roles' => $user->getRoles(),
            'customers' => $c2u
        ];
        return $this->json($response);
    }

    /**
     * @isGranted("ROLE_ADMIN")
     * @Route("/store_user", name="security_store_user", options={"expose"=true})
     * @param Request $request
     * @param UserRepository $repository
     * @param CustomerRepository $customerRepository
     * @param Customers2UsersRepository $c2uRepository
     * @param EntityManagerInterface $em
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     * @param TranslatorInterface $t
     * @return JsonResponse
     */
    public function storeUser(Request $request, UserRepository $repository, CustomerRepository $customerRepository, Customers2UsersRepository $c2uRepository, EntityManagerInterface $em, UserPasswordEncoderInterface $userPasswordEncoder, TranslatorInterface $t): JsonResponse
    {

        try {
            $userId = $request->request->getInt('id');
            $newFirstName = $request->request->get('firstName');
            $newLastName = $request->request->get('lastName');
            $newEmail = $request->request->get('email');
            $password = $request->request->get('password');
            $passwordOld = $request->request->get('passwordOld');

            $roles = $request->request->get('roles');
            $connectedCustomers = $request->request->get('customers');

            if (count(array_filter([$newFirstName, $newLastName, $newEmail])) !== 3) {
                throw new \Exception($t->trans('Należy podać imię, nazwisko i email.', [], 'security'));
            }

            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception($t->trans('Niepoprawny adres email.', [], 'security'));
            }

            if ($userId) {
                $user = $repository->find($userId);
                if (!$user) {
                    throw new \Exception($t->trans('Nieprawidłowy identyfikator użytkownika.', [], 'security'));
                }

                if ($password && !$userPasswordEncoder->isPasswordValid($user, $passwordOld)) {
                    throw new \Exception($t->trans('Stare hasło jest nieprawidłowe.', [], 'security'));
                };

            } else {
                $user = new User();
                if (!$password) {
                    throw new \Exception($t->trans('Brak hasła.', [], 'security'));
                }
            }

            $user->setFirstName($newFirstName);
            $user->setLastName($newLastName);
            $user->setEmail($newEmail);
            $user->setRoles((array)$roles);

            if ($password) {
                $user->setPassword($userPasswordEncoder->encodePassword($user, $password));
            }

            // usunięcie bieżących dowiązań
            foreach ((array)$c2uRepository->findBy(['owner' => $user]) as $connection) {
                $em->remove($connection);
            }
            $em->persist($user);

            // aktualizacja dowiązań klientów
            if (in_array('ROLE_CUSTOMER', $roles)) {
                if (empty($connectedCustomers)) {
                    throw new \Exception($t->trans('Użytkownik z rolą \'Klient\' musi mieć przypisanego przynajmniej jednego klienta.', [], 'security'));
                }

                foreach ($connectedCustomers as $customerId) {
                    $customer = $customerRepository->find($customerId);
                    $c2u = new Customers2Users();
                    $c2u->setCustomer($customer)
                        ->setOwner($user);
                    $em->persist($c2u);
                }
            } elseif ($connectedCustomers) {

            }


            $em->flush();

            if (!$userId) {
                $this->addFlash('success', $t->trans('Dodano użytkownika.', [], 'security'));
            }

        } catch (\Exception $e) {
            return $this->json([$e->getMessage()], RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($user, Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['public_attr']
        ]);
    }

    /**
     * @isGranted("ROLE_ADMIN")
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
     * @isGranted("ROLE_ADMIN")
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
     * @isGranted("ROLE_ADMIN")
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
