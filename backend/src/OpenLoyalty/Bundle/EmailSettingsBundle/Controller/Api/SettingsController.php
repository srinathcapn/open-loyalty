<?php
/*
 * This file is part of the "OpenLoyalty" package.
 *
 * (c) Divante Sp. z o. o.
 *
 * Author: Cezary Olejarczyk
 * Date: 31.01.17 08:56
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpenLoyalty\Bundle\EmailSettingsBundle\Controller\Api;

use OpenLoyalty\Bundle\EmailSettingsBundle\Form\Type\EmailFormType;
use OpenLoyalty\Domain\Email\Command\UpdateEmail;
use OpenLoyalty\Domain\Email\Email;
use OpenLoyalty\Domain\Email\EmailId;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Route;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SettingsController.
 */
class SettingsController extends FOSRestController
{
    /**
     * @Route(name="oloy.email_settings.list", path="/settings/emails")
     * @Method("GET")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @ApiDoc(
     *     name="System e-mail list",
     *     section="Settings"
     * )
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getListAction()
    {
        $emailRepository = $this->get('oloy.email.read_model.repository');
        $emails = $emailRepository->getAll();

        return $this->view($emails);
    }

    /**
     * @Route(name="oloy.email_settings.get", path="/settings/emails/{emailId}")
     * @Method("GET")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @ApiDoc(
     *     name="Get single system e-mail",
     *     section="Settings"
     * )
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getAction(Request $request)
    {
        $emailRepository = $this->get('oloy.email.read_model.repository');

        try {
            $emailEntity = $emailRepository->getById(new EmailId($request->get('emailId')));
        } catch (\Exception $e) {
            return $this->view(null, Response::HTTP_BAD_REQUEST);
        }

        return $this->view(
            [
                'entity' => $emailEntity,
                'additional' => $this->get('oloy.email.settings')->getAdditionalParams($emailEntity),
            ]
        );
    }

    /**
     * @Route(name="oloy.email_settings.update", path="/settings/emails/{email}")
     * @Method("PUT")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @ApiDoc(
     *     name="Update single system e-mail",
     *     section="Settings",
     *     input={"class" = "OpenLoyalty\Bundle\EmailSettingsBundle\Form\Type\EmailFormType", "name" = "email"}
     * )
     *
     * @param Request $request
     * @param Email   $email
     *
     * @return \FOS\RestBundle\View\View
     */
    public function updateAction(Request $request, Email $email)
    {
        $form = $this->get('form.factory')->createNamed('email', EmailFormType::class, null, ['method' => 'PUT']);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $command = new UpdateEmail($email->getEmailId(), $data);
            $commandBus = $this->get('broadway.command_handling.command_bus');
            $commandBus->dispatch($command);

            return $this->view($email->getEmailId());
        }

        return $this->view($form->getErrors(), Response::HTTP_BAD_REQUEST);
    }
}
