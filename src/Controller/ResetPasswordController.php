<?php

namespace App\Controller;

use App\Domain\ChangePasswordData;
use App\Entity\User;
use App\Services\UtilService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/reset-password")
 */
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    private $resetPasswordHelper;
    private $utilService;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper, UtilService $utilService)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->utilService = $utilService;
    }

    /**
     * @Route("/forgot", name="forgot", methods={"POST"})
     * @param Request $request
     * @param MailerInterface $mailer
     * @return Response
     */
    public function requestForgotPassword(Request $request, MailerInterface $mailer): Response
    {
        $email = $request->request->get("email");
        return $this->sendPasswordResetEmail($email, $mailer);
    }

    /**
     * @Route("/reset/{token}", name="check-token", methods={"GET"})
     * @param string|null $token
     * @return Response
     */
    public function checkToken(string $token = null): Response
    {
        if ($token == null) {
            return $this->json(['errors' => 'No reset password token found in the URL or in the session.'], 400);
        }
        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->json(['errors' => 'There was a problem validating your reset request - %s', $e->getReason()], 400);
        }
        return $this->json(['errors' => false, 'token' => $token, 'userId' => $user->getId()]);
    }

    /**
     * @Route("/change-password", name="change-password", methods={"PUT"})
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function changePassword(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $changePasswordData = new ChangePasswordData($request->request->get("password"),
            $request->request->get("passwordConfirmation"),
            $request->request->get("token"),
            $request->request->get("userId"));
        $errors = $this->utilService->checkPassword($changePasswordData);

        if (empty($errors)) {
            $this->resetPasswordHelper->removeResetRequest($changePasswordData->getToken());
            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => $changePasswordData->getUserId(),]);
            $encodedPassword = $passwordEncoder->encodePassword($user, $changePasswordData->getPassword());
            $user->setPassword($encodedPassword);
            $this->getDoctrine()->getManager()->flush();
            return $this->json(['errors' => $errors]);
        }
        return $this->json(['errors' => $errors], 400);
    }

    /**
     * @param string|null $emailFormData
     * @param MailerInterface $mailer
     * @return JsonResponse
     */
    private function sendPasswordResetEmail(?string $emailFormData, MailerInterface $mailer)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $emailFormData,]);
        if (!$user) {
            return $this->json(['errors' => 'User not found'], 400);
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->json(['errors' => $e->getMessage()], 400);
        }

        $email = (new TemplatedEmail())
            ->from(new Address('apilogin@apilogin.com', 'Api Login'))
            ->to($user->getEmail())
            ->subject('Your password reset request')
            ->htmlTemplate('emails/reset.html.twig')
            ->context([
                'resetToken' => $resetToken->getToken(),
                'tokenLifetime' => $this->resetPasswordHelper->getTokenLifetime() / 60 / 60,
            ]);

        try {
            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            return $this->json(['errors' => $e->getMessage()], 400);
        }

        return $this->json(['errors' => false]);
    }
}
