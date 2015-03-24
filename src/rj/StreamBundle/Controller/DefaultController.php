<?php

namespace rj\StreamBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('rjStreamBundle:Default:index.html.twig', array('name' => $name));
    }
}
