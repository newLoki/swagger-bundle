<?php

namespace Draw\SwaggerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class SwaggerController extends Controller
{
    public function apiDocAction(Request $request)
    {
        if($request->getRequestFormat() != 'json') {
            $currentRoute = $request->attributes->get('_route');
            $currentUrl = $this->get('router')
                ->generate($currentRoute, array('_format' => 'json'), true);
            return new RedirectResponse('http://petstore.swagger.io/?url=' . $currentUrl);
        }
        
        $swagger = $this->get("draw.swagger");
        $schema = $swagger->extract(json_encode($this->getParameter("draw_swagger.schema")));
        $schema = $swagger->extract($this->container, $schema);

        $jsonSchema = $swagger->dump($schema);

        return new JsonResponse(json_decode($jsonSchema));
    }
}
