<?php


namespace App\Controller;


use App\System\Api\ApiProblem\ValidationApiProblem;
use App\System\Api\ApiProblemException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BaseController extends AbstractController
{
    protected $errors = [];

    /**
     * @param Request $request
     * @param FormInterface $form
     * @return void
     */
    protected function processForm(Request $request, FormInterface $form): void
    {
        $data = $request->request->all() + $request->files->all();
        $clearMissing = $request->getMethod() != 'PATCH';

        $form->submit($data, $clearMissing);

        if (!$form->isValid()) {
            $this->throwValidationException($form);
        }
    }

    /**
     * @param FormInterface $form
     * @return mixed[]
     */
    protected function getErrorsFromForm(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors() as $error) {
            if ($error instanceof FormError) {
                $errors[] = $error->getMessage();
            }
        }

        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }

        return $errors;
    }

    protected function throwValidationException(FormInterface $form): void
    {
        $this->errors[] = $this->getErrorsFromForm($form);
        throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'validation_error');
    }

    protected function composeErrorResponse(\Exception $e) {
        return new JsonResponse([
            'errors' => [
                'data' => $this->errors,
                'title' => $e->getMessage(),
            ]
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function apiResponse(array $data, int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'data' => $data,
            'status' => $status,
        ]);
    }
}