<?php
namespace NaxCrmBundle\Controller\v1\open;

use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\UserLogItem;
use NaxCrmBundle\Modules\Email\Triggers\Client\ResetPassword;
use Firebase\JWT\JWT;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class ClientController extends BaseController
{
    /**
     * @Get("/clients/reset-password-link", name="open_v1_get_reset_password_link")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/client",
     *    authentication = false,
     *    description = "Send to client reset password link",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        404={
     *          "Returned when client was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="email", "dataType"="string", "required"=true, "format"="email format", "description" = "set email for reset password"},
     *    },
     * )
     */
    public function getResetLink()
    {
        $email = $this->getRequiredParam('email');
        $client = $this->getRepository(Client::class())->findOneBy(['email' => $email]);
        if (empty($client)) {
            return $this->getFailedJsonResponse(null, 'Client was not found', 404);
        }
        $this->getTriggerService()->sendEmails(ResetPassword::class(), compact('client'), $client->getLangcode());
        return $this->getJsonResponse(null, 'You will receive email with reset password link soon');
    }

    /**
     * @Put("/clients/password", name="open_v1_update_clients_password")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/client",
     *    authentication = false,
     *    description = "Set client password",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned when client email doesn't match",
     *          "Returned when password and confirmation doesn`t match",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when you are not allowed to change client`s password",
     *        },
     *        404={
     *          "Returned when client was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="token",        "dataType"="string", "required"=true, "format"=".+", "description" = "get jwt token"},
     *        {"name"="password",     "dataType"="string", "required"=true, "format"=".+", "description" = "set new password"},
     *        {"name"="confirmation", "dataType"="string", "required"=true, "format"=".+", "description" = "set new password again"},
     *    },
     * )
     */
    public function updatePasswordAction()
    {
        $jwt  = $this->getRequiredParam('token');

        $secretKey = base64_decode($this->getParameter('jwt_salt'));
        $token = JWT::decode($jwt, $secretKey, array('HS256'));

        $clientId = isset($token->data->clientId) ? $token->data->clientId : $token->data->id;

        $client = $this->getRepository(Client::class())->find($clientId);
        if (empty($client)) {
            return $this->getFailedJsonResponse('Client not found', 404);
        }
        if ($client->getEmail() != $token->data->email) {
            return $this->getFailedJsonResponse('Client email doesn\'t match');
        }
        if ($client->getOneTimeJwt() != $jwt) {
            return $this->getFailedJsonResponse('You are not allowed to change client\'s password', 403);
        }

        $password = $this->getRequiredParam('password');
        $confirmation = $this->getRequiredParam('confirmation');
        if ($password != $confirmation) {
            return $this->getFailedJsonResponse([], 'Password and confirmation doesn`t match');
        }

        $passwordHash = $this->get('security.password_encoder')->encodePassword($client, $password);
        $client->setPassword($passwordHash);
        $client->setOneTimeJwt(null);
        $this->getEm()->flush();

        $this->createLog($client, UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_CLIENTS, [
            'message' => 'Client password was changed',
            'id' => $client->getId(),
        ], 'client');
        return $this->getJsonResponse($client->export());
    }

    /**
     * @Get("/clients/confirm-email", name="open_v1_confirm_email")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/client",
     *    authentication = false,
     *    description = "Confirm client email",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Client email doesn`t match",
     *        },
     *        404={
     *          "Returned when client was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="token", "dataType"="string", "required"=true, "format"=".+", "description" = "get JWT token"},
     *    },
     * )
     */
    public function confirmEmailAction()
    {
        $jwt  = $this->getRequiredParam('token');
        $secretKey = base64_decode($this->getParameter('jwt_salt'));
        $token = JWT::decode($jwt, $secretKey, array('HS256'));

        $client = $this->getRepository(Client::class())->find($token->data->clientId);
        if (!($client instanceof Client)) {
            return $this->getFailedJsonResponse('Client not found', 404);
        }
        if ($client->getEmail() != $token->data->email) {
            return $this->getFailedJsonResponse('Client email doesn`t match');
        }
        $client->setIsEmailConfirmed(true);
        $em = $this->getEm();
        $em->flush();

        //return fresh JWT for client, for login purpose
        $freshJWT = $this->get('nax.jwt_service')->generateClientsJWT($client);
        $response = $this->getJsonResponse($client->export());
        $response->headers->set('Authorization', $freshJWT);

        return $response;
    }
}