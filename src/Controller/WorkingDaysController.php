<?php

namespace App\Controller;

use App\Service\WorkingScheduleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class WorkingDaysController extends BaseController
{
    /**
     * @isGranted("ROLE_ADMIN")
     * @Route("/working_days/api/{day}", name="get_days_in_month", methods="GET", options={"expose"=true})
     * @param $day
     * @param WorkingScheduleService $workingScheduleService
     * @param EntityManagerInterface $em
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getDaysInMonth($day, WorkingScheduleService $workingScheduleService)
    {
        try {
            $workingScheduleService->initialize($day);
            if (false === $workingScheduleService->hasHolidaysInitialized()) {
                $workingScheduleService->initializeHolidays();
            }
            $daysNum = $workingScheduleService->getWorkingDaysCount();

        } catch (\Exception $e) {
            return $this->composeErrorResponse($e);
        }
        return $this->json([$daysNum]);
    }
}
