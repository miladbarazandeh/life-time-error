<?php
namespace App\Controller\API;

use App\Entity\User;
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
            $username = $data['username'];
            $password = $data['password'];
            $firstName = $data['first_name'];
            $lastName = $data['last_name'];
            $phone = $data['phone_number'];
            $email = $data['email'];
            $gender = $data['gender'];
        } catch (\Exception $exception) {
            return new JsonResponse(['errors' => 'ERROR'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $errorMessages = $this->validateData($data, $validator);

        if ($errorMessages) {
            return new JsonResponse(['errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        } else {
            $user = new User($username);
            $user
                ->setFirstName($firstName)
                ->setLastName($lastName)
                ->setPhoneNumber($phone)
                ->setEmail($email)
                ->setGender($gender)
                ->setPassword($encoder->encodePassword($user, $password));
            $em->persist($user);
            $em->flush();

            return new JsonResponse(['message'=>'User created'], JsonResponse::HTTP_CREATED);
        }
    }

    private function validateData(array $data, ValidatorInterface $validator)
    {
        $constraints = new Assert\Collection([
            'username' => [new Assert\NotBlank, new Assert\Length(['min' => 5])],
            'password' => [new Assert\Length(['min' => 6]), new Assert\notBlank],
            'first_name' => [new Assert\notBlank],
            'last_name' => [new Assert\notBlank],
            'phone_number' => [new Assert\notBlank],
            'email' => [new Assert\notBlank, new Assert\Email()],
            'gender'=> [new Assert\Choice(['choices'=>['male', 'female']]), new Assert\notBlank]
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

