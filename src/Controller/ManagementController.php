<?php

namespace App\Controller;

use App\Domain\RegisterData;
use App\Services\UtilService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class ManagementController extends AbstractController
{

    private $utilService;

    public function __construct(UtilService $utilService)
    {
        $this->utilService = $utilService;
    }

    /**
     * @Route("/register", name="api_register", methods={"POST"})
     * @param EntityManagerInterface $em
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param Request $request
     * @return JsonResponse
     */
    public function register(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder, Request $request)
    {

        $registerData = new RegisterData($request->request->get("email"),
            $request->request->get("password"),
            $request->request->get("confirmPassword"));
        $errors = $this->utilService->checkPassword($registerData);

        if (!$errors) {
            $user = new User();
            $encodedPassword = $passwordEncoder->encodePassword($user, $registerData->getPassword());
            $user->setEmail($registerData->getEmail());
            $user->setPassword($encodedPassword);

            try {
                $em->persist($user);
                $em->flush();
                return $this->json(['user' => $user]);
            } catch (UniqueConstraintViolationException $e) {
                $errors[] = "The email provided already has an account!";
            } catch (\Exception $e) {
                $errors[] = "Unable to save new user at this time.";
            }

        }

        return $this->json(['errors' => $errors], 400);

    }

    /**
     * @Route("/login", name="api_login", methods={"POST"})
     */
    public function login()
    {
        return $this->json(['errors' => false]);
    }

    /**
     * @Route("/profile", name="api_profile", methods={"GET"})
     * @IsGranted("ROLE_USER")
     */
    public function profile()
    {
        return $this->json(['user' => $this->getUser()], 200, [], ['groups' => ['api']]);
    }

    /**
     * @Route("/delete", name="delete_user", methods={"DELETE"})
     * @IsGranted("ROLE_USER")
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function delete(EntityManagerInterface $em)
    {
        $em->remove($this->getUser());
        $em->flush();
        return $this->json(['errors' => false]);
    }

}