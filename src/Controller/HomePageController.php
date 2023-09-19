<?php

// Define the namespace for this file
namespace App\Controller;

// Import the necessary classes
use App\Service\FsService;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// Define the HomePageController class, which extends the AbstractController class
class HomePageController extends AbstractController
{
    // Define the index method, which is mapped to the '/' route
    #[Route('/', name: 'app_home_page')]
    public function index(FsService $fsService, Request $request): Response
    {
        // Get the visitor instance from the FsService
        $visitor = $fsService->getVisitor();

        // Do other things with visitor instance if needed

        // Render the 'home_page/index.html.twig' template with the specified parameters
        $response = $this->render('home_page/index.html.twig', [
            'controller_name' => 'HomePageController',
            'fsVisitorFlags' => json_encode($visitor->getFlagsDTO()) // Pass Flags in the params of the template
        ]);

        // Create a new cookie for the visitor ID
        $cookie = new Cookie("fsVisitorId", $visitor->getVisitorId());
        // Set the HttpOnly flag to false
        $cookie = $cookie->withHttpOnly(false);
        // Add the cookie to the response headers
        $response->headers->setCookie($cookie);
        // Return the response
        return $response;
    }
}
