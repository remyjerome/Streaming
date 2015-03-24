<?php

// src/rj/StreamBundle/Controller/StreamController.php

namespace rj\StreamBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class StreamController extends Controller
{
    public function indexAction()
    {
       return $this->render('rjStreamBundle:Home:index.html.twig');
    }
    public function saisonAction($s)
    {
       return $this->render('rjStreamBundle:Saisons:index.html.twig',array('s' => $s));
    }
    public function episodeAction($s,$e)
    {
       return $this->render('rjStreamBundle:Episodes:index.html.twig',array('s' => $s,'e'=> $e));
    }
    public function newsAction($date,$id)
    {
       return $this->render('rjStreamBundle:News:index.html.twig',array('date' => $date,'id'=> $id));
    }
}
