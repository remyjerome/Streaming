<?php

// src/rj/StreamBundle/Controller/StreamController.php

namespace rj\StreamBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use rj\StreamBundle\Entity\Episode;
use rj\StreamBundle\Entity\User;

class StreamController extends Controller
{
    public function notationAction($saison, $episode, $note)
    {
        /********** On regarde si l'utilisateur n'a pas deja voté aujourd'hui **********/
        $em = $this->getDoctrine()->getManager();
        $newUser = new User($saison,$episode);  //Création d'un nouvel utilisateur (ip(auto),saison,episode,date(auto))
        $user = $em->getRepository('rjStreamBundle:User')
        ->findOneBy(array('saison' => $saison, 'episode' => $episode, 'ip' => $newUser->getIp())); //On cherche dans la BDD si l'utilisateur a deja vote pour cet episode
        if($user)   //Si on trouve un utilisateur dans la BDD
        {
    
            $interval = $user->getDate()->diff($newUser->getDate());
            if((int)$interval->format('%a') >= 1)   //On regarde son dernier vote remonte a plus de 24h
            {
                $user->setDate(new \Datetime());    //+de 24h on réaffecte une nouvelle date
                $em->flush();

            }
            else    //sinon on return null l'utilisateur a deja voté
            {

                $response1 = new JsonResponse();
                return $response1->setData(array('note' => ''));
            }
        }
        else //Sinon l'utilisateur vote pour la première fois pour cet episode
        {
            $em->persist($newUser); //On enregistre donc cet utilisateur dans la BDD
            $em->flush();
        }
        /********** Application de la note **********/
        $em1 = $this->getDoctrine()->getManager();
        $episode = $em1->getRepository('rjStreamBundle:Episode')
        ->findOneBy(array('saison' => $saison, 'episode' => $episode));

        if($episode)
        {
            if($note)
            {
                $noteplus = $episode->getNbnoteplus() +1;
                $episode->setNbnoteplus($noteplus);
            }
            else
            {
                $notemoins = $episode->getNbnotemoins() +1;
                $episode->setNbnotemoins($notemoins);
            }

            $newnote = ($episode->getNbnoteplus() / ( $episode->getNbnoteplus() + $episode->getNbnotemoins() ))*100;
            $episode->setNote($newnote);
        }
        else
        {
            return null;
        }
        $em1->flush();
        $response = new JsonResponse();
        return $response->setData(array('note' => $episode->getNote()));

    }

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
    public function episodeallAction()
    {
        $s=0;
       $episodes = $this->getDoctrine()
        ->getRepository('rjStreamBundle:Episode')
        ->findAll();

        if (!$episodes) 
        {
          throw $this->createNotFoundException(
          'Aucun episode'
        );
        }
       return $this->render('rjStreamBundle:Saisons:index.html.twig',array('s' => $s,'episodes'=>$episodes));
    }
    public function episodevueAction()
    {
        $episode = $this->getDoctrine()
        ->getRepository('rjStreamBundle:Episode')
        ->findOneBy(array('saison' => 5, 'episode' => 3));

        if (!$episode) 
        {
          return $this->render('rjStreamBundle:Home:index.html.twig');
        }
       return $this->render('rjStreamBundle:Episodes:index.html.twig',array('s' => 5,'e'=> 3, 'episode'=>$episode));
    }
    public function episodenoteAction()
    {
        $episode = $this->getDoctrine()
        ->getRepository('rjStreamBundle:Episode')
        ->findOneBy(array('saison' => 5, 'episode' => 3));

        if (!$episode) 
        {
          return $this->render('rjStreamBundle:Home:index.html.twig');
        }
       return $this->render('rjStreamBundle:Episodes:index.html.twig',array('s' => 5,'e'=> 3, 'episode'=>$episode));
    }
    public function episodelastAction()
    {
        $episode = $this->getDoctrine()
        ->getRepository('rjStreamBundle:Episode')
        ->findOneBy(array('saison' => 5, 'episode' => 3));

        if (!$episode) 
        {
          return $this->render('rjStreamBundle:Home:index.html.twig');
        }
       return $this->render('rjStreamBundle:Episodes:index.html.twig',array('s' => 5,'e'=> 3, 'episode'=>$episode));
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
