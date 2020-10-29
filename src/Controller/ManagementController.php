<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class ManagementController extends AbstractController
{

    /**
     * @Route("/register", name="api_register", methods={"POST"})
     */
    public function register(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder, Request $request)
    {

        $user = new User();

        $email = $request->request->get("email");
        $password = $request->request->get("password");
        $passwordConfirmation = $request->request->get("password_confirmation");

        $errors = [];
        if (empty($password) || empty($passwordConfirmation) || empty($email)) {
            $errors[] = "The fields cannot be empty";
        }
        if ($password != $passwordConfirmation) {
            $errors[] = "Password confirmation does not match.";
        }
        if (strlen($password) < 6) {
            $errors[] = "Password must contain at least 6 characters.";
        }
        if (!$errors) {
            $encodedPassword = $passwordEncoder->encodePassword($user, $password);
            $user->setEmail($email);
            $user->setPassword($encodedPassword);

            try {
                $em->persist($user);
                $em->flush();

                return $this->json([
                    'user' => $user
                ]);
            } catch (UniqueConstraintViolationException $e) {
                $errors[] = "The email provided already has an account!";
            } catch (\Exception $e) {
                $errors[] = "Unable to save new user at this time.";
            }

        }

        return $this->json([
            'errors' => $errors
        ], 400);

    }

    /**
     * @Route("/login", name="api_login", methods={"POST"})
     */
    public function login()
    {
        return $this->json(['result' => true]);
    }

    /**
     * @Route("/profile", name="api_profile", methods={"GET"})
     * @IsGranted("ROLE_USER")
     */
    public function profile()
    {
        return $this->json([
            'user' => $this->getUser()
        ], 200, [], [
            'groups' => ['api']
        ]);
    }

    /**
     * @Route("/delete", name="delete_user", methods={"DELETE"})
     * @IsGranted("ROLE_USER")
     *
     */
    public function delete(EntityManagerInterface $em)
    {
        $em->remove($this->getUser());
        $em->flush();
        return $this->json(['result' => true]);
    }

}