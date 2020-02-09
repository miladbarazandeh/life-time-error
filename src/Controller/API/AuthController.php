<?php
namespace App\Controller\API;

use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    /**
     * @Route("/register", methods={"POST"})
     */
    public function register(Request $request, UserPasswordEncoderInterface $encoder, ValidatorInterface $validator)
    {

        $body = $request->getContent();
        $data = json_decode($body, true);

        $em = $this->getDoctrine()->getManager();
        try {
            $password = $data['password'];
            $email = $data['email'];
        } catch (\Exception $exception) {
            return new JsonResponse(['errors' => 'ERROR'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $errorMessages = $this->validateData($data, $validator);
        if ($errorMessages) {
            return new JsonResponse(['errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        } else {
            try {
                $user = new User();
                $user
                    ->setEmail($email)
                    ->setPassword($encoder->encodePassword($user, $password));
                $em->persist($user);
                $em->flush();
            } catch (UniqueConstraintViolationException $exception) {
                return new JsonResponse(['errors' => 'Already exist!'], JsonResponse::HTTP_CONFLICT);
            } catch (\Exception $exception) {
                return new JsonResponse(['errors' => 'Internal Error'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
            return new JsonResponse(['message'=>'User created'], JsonResponse::HTTP_CREATED);
        }
    }

    private function validateData(array $data, ValidatorInterface $validator)
    {
        $constraints = new Assert\Collection([
            'password' => [new Assert\Length(['min' => 6])],
            'email' => [new Assert\Email()],
        ]);

        $violations = $validator->validate($data, $constraints);
        $accessor = PropertyAccess::createPropertyAccessor();

        $errorMessages = [];

        foreach ($violations as $violation) {

            $accessor->setValue($errorMessages,
                $violation->getPropertyPath(),
                $violation->getMessage());
        }

        return $errorMessages;
    }
}

