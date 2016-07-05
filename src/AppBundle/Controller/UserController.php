<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @RouteResource("user")
 */
class UserController extends FOSRestController
{
    /**
     * Get users list.
     *
     * @return array
     *
     * @View()
     * @ApiDoc()
     */
    public function cgetAction()
    {
        $users = $this
            ->container
            ->get('fos_user.user_manager')
            ->findUsers();

        return $this->view(array('users' => $users));
    }

    /**
     * Get the user.
     *
     * @param int $id user id
     *
     * @return User
     *
     * @View()
     * @ApiDoc()
     */
    public function getAction($id)
    {
        $user = $this
            ->container
            ->get('fos_user.user_manager')
            ->findUserBy([
                'id' => $id
            ]);

        if (!$user) {
            throw new HttpException(404, "User not found.");
        }

        return $this->view($user);
    }

    /**
     * Patch the user.
     * Format:
     * [
     *   {"operation": "change-password", "value": "abc"},
     *   {"operation": "change-email",    "value": "def"}
     * ]
     *
     * @param int $id user id
     * @param Request $request HTTP Request
     *
     * @return User
     *
     * @View()
     * @ApiDoc()
     */
    public function patchAction($id, Request $request)
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($currentUser->getId() != $id) {
            throw new AccessDeniedHttpException('You can change only your password or email.');
        }

        /** @var User $user */
        $user = $this
            ->container
            ->get('fos_user.user_manager')
            ->findUserBy([
                'id' => $id
            ]);

        if (!$user) {
            throw new HttpException(404, "User not found.");
        }

        $operations = json_decode($request->getContent(), true);

        if (!$operations || !is_array($operations)) {
            throw new HttpException(400, "No operations specified.");
        }

        //todo: wyciagnac poza kontroler
        foreach ($operations as $operation) {
            if (!isset($operation['operation']) || !isset($operation['value'])) {
                throw new HttpException(400, "Bad operations format.");
            }

            switch ($operation['operation']) {
                case 'change-password':
                    $user->setPlainPassword($operation['value']);
                    break;

                case 'change-email':
                    if (!filter_var($operation['value'], FILTER_VALIDATE_EMAIL)) {
                        throw new HttpException(400, "Invalid email format.");
                    }
                    $user->setEmail($operation['value']);
                    break;

                default:
                    throw new HttpException(
                        400,
                        sprintf("Operation %s unknown.", $operation['operation']
                    ));
            }
        }

        $this
            ->container
            ->get('fos_user.user_manager')
            ->updateUser($user, true);

        return $this->view($user);
    }
}
