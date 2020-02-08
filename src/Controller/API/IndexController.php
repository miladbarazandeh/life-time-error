<?php
namespace App\Controller\API;

use App\Entity\Mood;
use App\Entity\Reason;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @Route("/new-reason", methods={"POST"})
     */
    public function newReasonAction(Request $request, ValidatorInterface $validator)
    {
        /** @var User $user */
        $user = $this->getUser();
        $body = $request->getContent();
        $data = json_decode($body, true);

        $em = $this->getDoctrine()->getManager();

        $errors = $this->validateNewReasonData($data, $validator);

        if ($errors) {
            return new JsonResponse(['errors'=> $errors], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $reason = new Reason();
            $reason
                ->setUser($user)
                ->setTitle($data['title'])
                ->setIsPositive($data['is_positive']);

            $em->persist($reason);

            $em->flush();

            return new JsonResponse(['message'=>'reason created'], JsonResponse::HTTP_CREATED);
        } catch (\Exception $exception) {
            return new JsonResponse(['errors'=> $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    private function validateNewReasonData(array $data, ValidatorInterface $validator)
    {
        $constraints = new Assert\Collection([
            'title' => [new Assert\Length(['max'=>100])],
            'is_positive' => [new Assert\Choice([0, 1])]
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

    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @Route("/reasons", methods={"GET"})
     */
    public function getReasonsAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $reasons = $user->getReasons();

            $reasonsData = [];

            foreach ($reasons as $reason) {
                $reasonsData[] = [
                    'id'=> $reason->getId(),
                    'title'=>$reason->getTitle(),
                    'is_positive'=>$reason->getIsPositive()
                ];
            }

            return new JsonResponse($reasonsData);
        } catch (\Exception $exception) {
            return new JsonResponse(['errors'=> $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @Route("/new-mood", methods={"POST"})
     */
    public function newMoodAction(Request $request, ValidatorInterface $validator)
    {
        /** @var User $user */
        $user = $this->getUser();
        $body = $request->getContent();
        $data = json_decode($body, true);

        $em = $this->getDoctrine()->getManager();

        $errors = $this->validateNewMoodData($data, $validator);

        if ($errors) {
            return new JsonResponse(['errors'=> $errors], JsonResponse::HTTP_BAD_REQUEST);
        }

        $repository = $this->getDoctrine()->getRepository(Reason::class);


        try {
            $mood = new Mood();
            $mood
                ->setUser($user)
                ->setScore($data['score'])
                ->setDescription($data['description']);
            $reasonIds = $data['reasons'];

            foreach ($reasonIds as $reasonId) {
                $reason = $repository->find($reasonId);

                if (!$reason) {
                    throw new NotFoundHttpException('reason '. $reasonId. ' not found');
                }
                $mood
                    ->addReason($reason);
            }

            $em->persist($mood);

            $em->flush();

            return new JsonResponse(['message'=>'reason created'], JsonResponse::HTTP_CREATED);
        } catch (\Exception $exception) {
            return new JsonResponse(['errors'=> $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    private function validateNewMoodData(array $data, ValidatorInterface $validator)
    {
        $constraints = new Assert\Collection([
            'description' => [new Assert\Length(['min'=>1, 'max'=>144])],
            'score' => [new Assert\Type(['type'=>'integer']), new Assert\Range(['min'=>1, 'max'=>10])],
            'reasons'=> [new Assert\Type(['type' =>'array']), new Assert\All(['constraints' => new Assert\Type(['type' =>'integer'])])]
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

    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @Route("/moods", methods={"GET"})
     */
    public function getMoodsAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $moods = $user->getMoods();

            $moodsData = [];

            foreach ($moods as $mood) {
                $reasons = $mood->getReason();
                $reasonsData = [];
                foreach ($reasons as $reason) {
                    $reasonsData[] = [
                        'id'=> $reason->getId(),
                        'title'=>$reason->getTitle(),
                        'is_positive'=>$reason->getIsPositive()
                    ];
                }
                $moodsData[] = [
                    'id'=> $mood->getId(),
                    'description'=>$mood->getDescription(),
                    'score'=>$mood->getScore(),
                    'reasons'=>$reasonsData
                ];
            }

            return new JsonResponse($moodsData);
        } catch (\Exception $exception) {
            return new JsonResponse(['errors'=> $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}


