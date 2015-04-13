<?php

// src/rj/StreamBundle/Controller/StreamController.php

namespace rj\StreamBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use rj\StreamBundle\Entity\Episode;

class StreamController extends Controller
{
    public function indexAction()
    {
       return $this->render('rjStreamBundle:Home:index.html.twig');
    }
    public function saisonAction($s)
    {
      if($s<1)
      {
        $s=1;
      }
      if($s>5)
      {
        $s=5;
      }
       $episodes = $this->getDoctrine()
        ->getRepository('rjStreamBundle:Episode')
        ->findBySaison($s);

        if (!$episodes) 
        {
          throw $this->createNotFoundException(
          'Aucun episode pour : s'.$s
        );
        }
       return $this->render('rjStreamBundle:Saisons:index.html.twig',array('s' => $s,'episodes'=>$episodes));
    }
    public function episodeAction($s,$e)
    {
      if($e > 10)
      {
        $s = $s +1;
        $e=1;
      }
      if($e <= 0)
      {
        $s = $s-1;
        $e = 10;
      }
      if($s <= 0)
      {
        $s=1;
        $e=1;
      }
      if($s>5)
      {
        $s =5;
        $e =10;
      }

        $episode = $this->getDoctrine()
        ->getRepository('rjStreamBundle:Episode')
        ->findOneBy(array('saison' => $s, 'episode' => $e));

        if (!$episode) 
        {
          /*throw $this->createNotFoundException(
          'Aucun episode pour : s'.$e.'e'.$s);*/
          return $this->render('rjStreamBundle:Home:index.html.twig');
        }
       return $this->render('rjStreamBundle:Episodes:index.html.twig',array('s' => $s,'e'=> $e, 'episode'=>$episode));
    }
    public function newsAction()
    {
       return $this->render('rjStreamBundle:News:index.html.twig');
    }
    public function createAction()
    {
    
    //S2 E1
    $episode = new episode(2,1);
    $episode->setTitre('Le nord se souvient');
    $episode->setDescription('...');
    $episode->setLien('http://mystream.la/external/8UL7AC2JDBZJ');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E2
    $episode = new episode(2,2);
    $episode->setTitre('Les Contrées nocturnes');
    $episode->setDescription('...');
    $episode->setLien('http://mystream.la/external/MUI97WBVQQR2');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E3
    $episode = new episode(2,3);
    $episode->setTitre('Ce qui est mort ne saurait mourir');
    $episode->setDescription('...');
    $episode->setLien('http://mystream.la/external/9A1Y1XLC8GU9');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E4
    $episode = new episode(2,4);
    $episode->setTitre('Le Jardin des os');
    $episode->setDescription('...');
    $episode->setLien('http://mystream.la/external/AZD1SWAHNNSP');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E5
    $episode = new episode(2,5);
    $episode->setTitre('Le Fantôme d\'Harrenhal');
    $episode->setDescription('...');
    $episode->setLien('http://mystream.la/external/U040SA2S8IZD');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E6
    $episode = new episode(2,6);
    $episode->setTitre('Les Anciens et les Nouveaux Dieux');
    $episode->setDescription('...');
    $episode->setLien('http://mystream.la/external/0XQV47SVEARD');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E7
    $episode = new episode(2,7);
    $episode->setTitre('Un homme sans honneur');
    $episode->setDescription('...');
    $episode->setLien('http://mystream.la/external/UZ3PP07UAW7K');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S5 E1
    $episode = new episode(5,1);
    $episode->setTitre('The Wars to Come');
    $episode->setDescription('...');
    $episode->setLien('http://mystream.la/external/F65SS7FHON6D');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S5 E2
    $episode = new episode(5,2);
    $episode->setTitre('The House of Black and White');
    $episode->setDescription('...');
    $episode->setLien('http://mystream.la/external/5TWD2ZBXWIXN');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S5 E3
    $episode = new episode(5,3);
    $episode->setTitre('High Sparrow');
    $episode->setDescription('...');
    $episode->setLien('http://mystream.la/external/XXDZCBI1EL5G');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();


    return new Response('Reussi');
    }
}
