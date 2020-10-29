<?php

namespace App\Controller;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * @Route("/reset-password")
 */
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    private ResetPasswordHelperInterface $resetPasswordHelper;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
    }

    /**
     * @Route("/forgot", name="forgot", methods={"POST"})
     * @param Request $request
     * @param MailerInterface $mailer
     * @return Response
     */
    public function request(Request $request, MailerInterface $mailer): Response
    {
        $email = $request->request->get("email");

        return $this->sendPasswordResetEmail(
            $email,
            $mailer
        );
    }

    /**
     * @Route("/reset/{token}", name="reset_password", methods={"GET"})
     * @param string|null $token
     * @return Response
     */
    public function reset(string $token = null): Response
    {

        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->json([
                'error' => 'There was a problem validating your reset request - %s',
                $e->getReason()
            ], 400);
        }
        return $this->json(['result' => true, 'token' => $token, 'userId' => $user->getId()]);
    }

    /**
     * @Route("/change_password", name="change_password", methods={"POST"})
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function change(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {

        $password = $request->request->get("password");
        $token = $request->request->get("token");
        $userId = $request->request->get("userId");
        $passwordConfirmation = $request->request->get("password_confirmation");
        $errors = [];

        if (empty($password) || empty($passwordConfirmation)) {
            $errors[] = "The fields cannot be empty";
        }
        if ($password != $passwordConfirmation) {
            $errors[] = "Password confirmation does not match.";
        }
        if (strlen($password) < 6) {
            $errors[] = "Password must contain at least 6 characters.";
        }

        if (empty($errors)) {
            $this->resetPasswordHelper->removeResetRequest($token);
            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
                'id' => $userId,
            ]);

            $encodedPassword = $passwordEncoder->encodePassword(
                $user[0],
                $password
            );

            $user->setPassword($encodedPassword);
            $this->getDoctrine()->getManager()->flush();

            return $this->json(['result' => true]);
        }

        return $this->json(['errors' => $errors], 400);
    }

    private function sendPasswordResetEmail(string $emailFormData, MailerInterface $mailer)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);
        if (!$user) {
            return $this->redirectToRoute('api_login');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->json(['error' => $e->getReason()], 400);
        }

        $email = (new TemplatedEmail())
            ->from(new Address('juanjose.garciabeza@hotmail.com', 'Api Login'))
            ->to($user->getEmail())
            ->subject('Your password reset request')
            ->htmlTemplate('emails/reset.html.twig')
            ->context([
                'resetToken' => $resetToken->getToken(),
                'tokenLifetime' => $this->resetPasswordHelper->getTokenLifetime()/60/60,
            ]);

        $mailer->send($email);

        return $this->forward('App\Controller\ManagementController::login');
    }
}
