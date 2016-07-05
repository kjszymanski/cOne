<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HomeController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        /** @var \FOS\OAuthServerBundle\Entity\ClientManager $clientManager */
        $clientManager = $this->container->get('fos_oauth_server.client_manager');
        $firstClient = $clientManager->findClientBy([]);

        /** @var \Symfony\Bundle\FrameworkBundle\Routing\Router $router */
        $router = $this->container->get('router');
        $homePageUrl = $router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);

        if (!$firstClient) {
            $client = $clientManager->createClient();
            $client->setRedirectUris([$homePageUrl . 'api']);
            $client->setAllowedGrantTypes(['token', 'authorization_code']);
            $clientManager->updateClient($client);

            $firstClient = $clientManager->findClientBy([]);
        }

        return $this->render('default/index.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..'),
            'client_id' => $firstClient->getPublicId(),
        ));
    }
}
