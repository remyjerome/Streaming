<?php

// src/rj/StreamBundle/Controller/StreamController.php

namespace rj\StreamBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use rj\StreamBundle\Entity\Episode;
use rj\StreamBundle\Entity\User;
use rj\StreamBundle\Entity\vue;
use rj\StreamBundle\Entity\News;
use Doctrine\ORM\Query\ResultSetMapping;


class StreamController extends Controller
{
    public function voirplusAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $new = $em->getRepository('rjStreamBundle:News')
        ->find($id);
        if(!$new)
        {
            throw $this->createNotFoundException(
          'Aucune news'
        );
        }
        $response = new JsonResponse();
        return $response->setData(array('description' => $new->getDescription()));

    }
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
        return $response->setData(array('note' => $episode->getNote(), 'nbplus' => $episode->getNbnoteplus(), 'nbmoins' => $episode->getNbnotemoins() ));

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
        //SELECT * FROM Episode HAVING vue = (SELECT MAX(vue) FROM Episode)
        $repository = $this->getDoctrine()
            ->getRepository('rjStreamBundle:Episode'); //Entité Episode

        $qb = $repository->createQueryBuilder('e1');

        $query1 = $qb->select($qb->expr()->max('e1.vue'))
            ->from('rjStreamBundle:Episode','e2')->getQuery();
        $vue = $query1->getSingleResult();
        $qb2 = $repository->createQueryBuilder('e') ;
        $query2 = $qb2->having('e.vue = :vue')
            ->setParameter('vue', $vue)
            ->getQuery();
        $episode = $query2->getSingleResult();

        if (!$episode) 
        {
          return $this->render('rjStreamBundle:Home:index.html.twig');
        }
       return $this->render('rjStreamBundle:Episodes:index.html.twig',array('s' => $episode->getSaison(),'e'=> $episode->getEpisode(), 'episode'=>$episode));
    }
    public function episodenoteAction()
    {

        //SELECT * FROM Episode HAVING note = (SELECT MAX(note) FROM Episode)
        $repository = $this->getDoctrine()
            ->getRepository('rjStreamBundle:Episode'); //Entité Episode

        $qb = $repository->createQueryBuilder('e1');

        $query1 = $qb->select($qb->expr()->max('e1.note'))
            ->from('rjStreamBundle:Episode','e2')->getQuery();
        $note = $query1->getSingleResult();
        $qb2 = $repository->createQueryBuilder('e') ;
        $query2 = $qb2->having('e.note = :note')
            ->setParameter('note', $note)
            ->getQuery();
        $episode = $query2->getSingleResult();
        if (!$episode) 
        {
          return $this->render('rjStreamBundle:Home:index.html.twig');
        }
       return $this->render('rjStreamBundle:Episodes:index.html.twig',array('s' => $episode->getSaison(),'e'=> $episode->getEpisode(), 'episode'=>$episode));
    }
    public function episodelastAction()
    {
        
        //SELECT * FROM Episode HAVING date = (SELECT MAX(date) FROM Episode)

       $repository = $this->getDoctrine()
            ->getRepository('rjStreamBundle:Episode'); //Entité Episode

        $qb = $repository->createQueryBuilder('e1');
        $query1 = $qb->select($qb->expr()->max('e1.date'))
            ->from('rjStreamBundle:Episode','e2')->getQuery();
        $date = $query1->getSingleResult();

        $qb2 = $repository->createQueryBuilder('e') ;
        $query2 = $qb2->having('e.date = :date')
            ->setParameter('date', $date)
            ->getQuery();
       
        $episode = $query2->getSingleResult();

        if (!$episode) 
        {
          return $this->render('rjStreamBundle:Home:index.html.twig');
        }
       return $this->render('rjStreamBundle:Episodes:index.html.twig',array('s' => $episode->getSaison(),'e'=> $episode->getEpisode(), 'episode'=>$episode));
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
        
      /********** Recherche episode BDD et affichage **********/
        $em = $this->getDoctrine()->getManager();
        $episode = $em
        ->getRepository('rjStreamBundle:Episode')
        ->findOneBy(array('saison' => $s, 'episode' => $e));

        if (!$episode) 
        {
          return $this->render('rjStreamBundle:Home:index.html.twig');
        }
        /********** Compteur de vues **********/
        $em = $this->getDoctrine()->getManager();
        $newVue = new vue($s,$e);  //Création d'un nouvel utilisateur (ip(auto),saison,episode,date(auto))
        $vue = $em->getRepository('rjStreamBundle:vue')
        ->findOneBy(array('saison' => $s, 'episode' => $e, 'ip' => $newVue->getIp())); //On cherche dans la BDD si l'utilisateur a deja vote pour cet episode
        if($vue)   //Si on trouve un utilisateur dans la BDD
        {
    
            $interval = $vue->getDate()->diff($newVue->getDate());
            if((int)$interval->format('%a') >= 1)   //On regarde son dernier vote remonte a plus de 24h
            {
                $vue->setDate(new \Datetime());    //+de 24h on réaffecte une nouvelle date
                $em->flush();

                $episode->setVue($episode->getVue()+1);

            }
        }
        else //Sinon l'utilisateur vote pour la première fois pour cet episode
        {
            $em->persist($newVue); //On enregistre donc cet utilisateur dans la BDD
            $em->flush();
            $episode->setVue($episode->getVue()+1);
        }
        /**************************************/
        $em->flush();
       return $this->render('rjStreamBundle:Episodes:index.html.twig',array('s' => $s,'e'=> $e, 'episode'=>$episode));
    }
    public function newsAction()
    {
        $news = $this->getDoctrine()
        ->getRepository('rjStreamBundle:News')
        ->findAll();

        if (!$news) 
        {
          throw $this->createNotFoundException(
          'Aucune news'
        );
        }
       return $this->render('rjStreamBundle:News:index.html.twig', array('news'=>$news));
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
    public function addnewsAction()
    {
        //NEWS 1
        $news = new News();
        $news->setTitre('FUITE DES QUATRE PREMIERS ÉPISODES DE LA SAISON 5');
        $news->setDescription("Les quatre premiers épisodes de la saison 5 de Game of Thrones ont été mis en ligne dans la nuit de samedi à dimanche, quelques heures avant la diffusion simultanée du season premiere sur tous les réseaux partenaires du producteur, HBO.

HBO, producteur de la très populaire série Game of Thrones, avait organisé un lancement de grande ampleur pour l'ouverture de la saison 5, dimanche 12 avril. Le premier épisode, baptisé The War to Come, devait ainsi débuter simultanément sur l'ensemble des réseaux de distribution partenaires, de façon à ce qu'aucun pays n'ait la primeur des révélations liées à cette cinquième saison.

Un grain de sable est cependant venu perturber cette mécanique bien rodée : dans la nuit de samedi à dimanche, les quatre premiers épisode de la saison 5 ont été mis à disposition des internautes, d'abord par l'intermédiaire d'un tracker BitTorrent privé. Les fichiers se sont ensuite très rapidement propagés, jusqu'à devenir référencés sur la plupart des sites et annuaires consacrés au téléchargement.

Pour le site spécialisé TorrentFreak, qui s'est le premier fait l'écho de cette fuite, plus de 135 000 internautes partageaient le fichier correspondant à l'épisode 1 dimanche, pour un total estimé à plus d'un million de téléchargements en moins de 18 heures.

Les fichiers concernés ne brillent cependant pas par leur qualité : la vidéo est encodée en 480p, et comporte un watermark flouté.
");
        $news->setImage('news2.jpg');
        $news->setAuteur('Admin');
        $news->setTag1('Game of Thrones');
        $news->setTag2('HBO');
        $news->setTag3('Saison 5');


        $em = $this->getDoctrine()->getManager();
        $em->persist($news);
        $em->flush();
        return new Response('Reussi');
    }

}
