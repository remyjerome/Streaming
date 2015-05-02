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
    public function nbepisodeAction()
    {
        $repository = $this->getDoctrine()
            ->getRepository('rjStreamBundle:Episode');
        $qb = $repository->createQueryBuilder('e');
        $qb->select('count(e.saison)');
        

        $episode = $qb->getQuery()->getSingleScalarResult();
        if(!$episode)
        {
            throw $this->createNotFoundException(
          'Aucun episode'
        );
        }
        $response = new JsonResponse();
        return $response->setData(array('nbepisode' => $episode));   
    }
    public function hebergeurAction()
    {
        $hebergeur = new hebergeur(1,1,"purevid");
        $hebergeur->setLien('');

        $em = $this->getDoctrine()->getManager();
        $em->persist($hebergeur);
        $em->flush();


    return new Response('Reussi');
    }
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
          throw $this->createNotFoundException(
          'Aucun episode trouvé'
          );
        }
        //SELECT * FROM Episode HAVING date = (SELECT MAX(date) FROM Episode)

       $repository = $this->getDoctrine()
            ->getRepository('rjStreamBundle:News'); 

        $qb = $repository->createQueryBuilder('n1');
        $query1 = $qb->select($qb->expr()->max('n1.date'))
            ->from('rjStreamBundle:News','n2')->getQuery();
        $date = $query1->getSingleResult();

        $qb2 = $repository->createQueryBuilder('n') ;
        $query2 = $qb2->having('n.date = :date')
            ->setParameter('date', $date)
            ->getQuery();
       
        $new = $query2->getSingleResult();

        if (!$new) 
        {
          throw $this->createNotFoundException(
          'Aucune news trouvée'
          );
        }


       return $this->render('rjStreamBundle:Home:index.html.twig', array('new' => $new,'episode'=>$episode));
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
          throw $this->createNotFoundException(
          'Aucun episode trouvé');
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
          throw $this->createNotFoundException(
          'Aucun episode trouvé');
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
            throw $this->createNotFoundException(
          'Aucun episode trouvé');        }
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
    

    //S1 E1
    $episode = new episode(1,1);
    $episode->setTitre("L'hiver vient");
    $episode->setDescription("Sur le continent de Westeros, un jeune patrouilleur de la Garde de Nuit, chargée de veiller sur le Mur, est condamné à mort pour désertion par Eddard Stark, seigneur de Winterfell et Gardien du Nord. Mais de sombres nouvelles arrivent de Port-Réal, la capitale des Sept Couronnes : Jon Arryn, ami intime d'Eddard et main du roi Robert Baratheon, vient de mourir. Le roi Robert et sa cour entreprennent alors un voyage vers Winterfell, pour gratifier son ami Eddard de la charge de main du roi. Pendant ce temps, les descendants des rois Targaryen en exil, renversés par Robert, fomentent leur retour en Westeros par un jeu d'alliance avec les Dothrakis, une tribu de guerriers nomades.
Alors qu'à Winterfell les festivités vont bon train durant le séjour du roi, Brandon, l'un des fils d'Eddard Stark, fait une découverte qui lui attire les foudres de la famille royale...");
    $episode->setLien('http://www.purevid.com/?m=embed&id=951FFgdgjusoobj1y7233');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S1 E2
    $episode = new episode(1,2);
    $episode->setTitre("La Route royale");
    $episode->setDescription("
Daenerys Targaryen, fraîchement mariée au khal Drogo, entreprend avec les Dothrakis et son frère le long voyage vers Vaes Dothrak. Afin de mieux satisfaire son mari et de prendre du plaisir à le faire, elle prend des leçons d’amour auprès d'une de ses caméristes.
À Winterfell, alors que Bran est toujours dans le coma suite à sa chute, Ned prépare son départ pour Port-Réal. Catelyn, dévastée par l'état de Bran, ne prend pas part aux adieux à sa famille : Arya et Sansa accompagnent leur père, Jon part vers le nord, sur le Mur, accompagné de son oncle Benjen et de Tyrion Lannister. Seuls restent à Winterfell Robb, Rickon et Bran. Lorsqu'un assassin tente de s'en prendre à Bran, toujours dans le coma, Catelyn décide de mener l'enquête et de partir à Port-Réal prévenir son mari des dangers qui le guettent.

Sur la longue route qui sépare Winterfell de la capitale des sept couronnes, les deux filles Stark et leurs louves vont se retrouver mêlées à un grave événement aux conséquences inattendues.

");
    $episode->setLien('http://www.purevid.com/?m=embed&id=881LOpt21jhjp10rt5823');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S1 E3
    $episode = new episode(1,3);
    $episode->setTitre("Lord Snow");
    $episode->setDescription("À peine arrivé à Port-Réal, Eddard Stark est convoqué à une session du conseil restreint et découvre alors que le royaume est fortement endetté et doit beaucoup d'argent aux Lannister. Il doit également faire face aux disputes de ses filles, Arya et Sansa, toujours rancunières des événements survenus sur la route royale. Lorsqu'on lui annonce l'arrivée de sa femme Catelyn dans la capitale, il s'empresse de la retrouver. Elle lui apprend la tentative de meurtre sur Bran, et lui montre la dague de l'assassin, immédiatement identifiée par Petyr Baelish comme étant celle du frère de la reine Tyrion Lannister.
À Winterfell, Bran, conscient depuis peu, essaie d'accepter son amnésie et son handicap ; pendant que sur le Mur, Jon Snow fait face à sa difficile condition de bâtard parmi ses futurs frères de la Garde de Nuit. Ce n'est que grâce à l'intervention du nain Tyrion Lannister qu'il échappe à un pugilat et qu'il comprend que ce n'est pas en prenant ses camarades de haut que sa condition s'améliorera. Tyrion quant à lui se fait alerter par mestre Aemon et le Lord Commandant de la Garde Jeor Mormont sur le manque de moyens humains et financiers du Mur, besoin d'autant plus urgent que les Autres semblent s'être réveillés.

Sur le continent Est, chevauchant la longue route qui les emmène elle et son khal vers Vaes Dothrak, Daenerys Targaryen commence à assumer son rôle de khaleesi, et annonce à son mari le khal Drogo qu'il sera bientôt père.

Afin d'apaiser les tensions au sein de sa famille, Ned demande à Syrio Forel, maître spadassin de Port-Réal, d'entraîner sa fille cadette au maniement des armes.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=492CECpmottv1y1vwwxvzKNM10574');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S1 E4
    $episode = new episode(1,4);
    $episode->setTitre("Infirmes, Bâtards et Choses brisées");
    $episode->setDescription("Après un bref passage à Winterfell où il constate le handicap de Bran et donne les plans d'une selle adaptée au jeune paralysé, Tyrion Lannister repart pour Port-Réal, déçu de l'accueil glacial qui lui a été prodigué chez les Stark.
Sur le Mur, à Châteaunoir, Jon Snow continue de surpasser les autres enrôlés de force dans la Garde de Nuit. Un nouveau venu, Samwell Tarly, est amené, mais il est loin d'avoir l'âme d'un combattant ; quand Snow prend sa défense, il s'attire encore les foudres du maître d'armes, qui fait tout pour que Samwell ne lâche plus Jon.

Les hommes de Khal Drogo continuent leur route vers Vaes Dothrak, et Daenerys Targaryen continue de s'affirmer comme reine, ce qui amuse son frère Viserys, celui-ci continuant de mépriser la tribu barbare.

Eddard Stark, officiellement Main du Roi, continue de gérer les affaires courantes de Port-Réal mais commence à envisager la possibilité que son prédécesseur ait été empoisonné. Ses deux filles, Sansa et Arya, commencent à voir de quoi sera fait leur avenir dans la capitale, ce qui ne leur plaît guère. Eddard finit par découvrir une des choses que Jon Arryn avait découvertes : Robert a un fils bâtard, aujourd'hui apprenti forgeron. Quant à la recherche sur les causes de la mort de Arryn, son ancien écuyer, mystérieusement adoubé après la mort de son maître, meurt dans un tournoi. De son côté, Catelyn Stark retrouve Tyrion dans une auberge et le fait capturer pour tentative de meurtre sur la personne de son fils.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=4726EDSWSHMF404sxzRLT2635743');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S1 E5
    $episode = new episode(1,5);
    $episode->setTitre("Le Loup et le Lion");
    $episode->setDescription("Catelyn Stark, qui a quitté brutalement Winterfell, mène Tyrion Lannister vers la demeure de sa sœur pour qu'il y soit jugé. Cependant, une attaque de brigands la force à le libérer pour qu'il se défende. Une fois arrivés aux Eyrié, tous constatent que la veuve de Jon Arryn sombre dans la folie et la peur du clan Lannister ; elle accepte cependant de mener le procès pour son fils de huit ans, qu'elle allaite encore.
Dans la capitale, le tournoi pour Eddard continue, et laisse exploser la brutalité de Ser Gregor Clegane. Mais la main du roi découvre en partie les secrets de la mort de son prédécesseur par Littlefinger : il est mort empoisonné, vraisemblablement par son écuyer, adoubé depuis, mais mort dans le tournoi. Arya, perdue dans le château, surprend une conversation secrète dans les oubliettes à propos de la guerre prochaine contre les Dothraki. En effet, la nouvelle de la grossesse de Daenerys est arrivée aux oreilles du conseil, et le roi Robert veut frapper fort en tuant la princesse et l'enfant qu'elle porte. Eddard refuse de cautionner cet acte et rend son poste.

Il continue cependant d'enquêter sur les derniers actes de Jon Arryn en retrouvant un autre bâtard du roi dans la maison close de Littlefinger. À la sortie, il se fait attaquer par Jaime Lannister, qui le provoque en duel pour la capture de son frère et parce que le roi l'a désigné comme traître ; Eddard est blessé à la cuisse durant le combat.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=2813A14z0qqsvurpq6413');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S1 E6
    $episode = new episode(1,6);
    $episode->setTitre("Une couronne dorée");
    $episode->setDescription("Winterfell a vent de la guerre à venir, et les fils Stark se préparent au combat. Bran est plus soucieux par ses rêves récurrents, mais il se console avec sa nouvelle selle.
Aux Eyrié, Tyrion Lannister parvient à obtenir un procès et fait en sorte que ce soit un duel judiciaire, finalement remporté par son champion, le mercenaire Bronn. Si Catelyn accepte stoïquement, Lysa est furieuse.

Au palais, Robert rétablit Eddard comme main du roi pour qu'il s'occupe du trône pendant qu'il part chasser, laissant Cersei ruminer sa colère contre Eddard ; tous sont conscients que tant que les Stark et les Lannister sont en conflit, les Sept Couronnes ne peuvent connaître la stabilité. Eddard, lors d'un conseil, prend cependant une décision drastique en nommant Ser Gregor Clegane traître à la couronne. Arya et Sansa sont troublées par la situation de leur père, mais semblent prêtes à faire ce qui doit être fait pour s'assurer une place dans le palais. C'est pendant une de ces discussions qu'Eddard se rend compte que tous les enfants Baratheon ont les cheveux bruns, sauf Joffrey, qui ne serait donc pas le fils du roi.

À Vaes Dothrak, Daenerys découvre que le feu ne la blesse pas, marque du sang de dragon. D'un autre côté, elle continue à se faire accepter par les Dothrakis en se montrant digne d'eux, au détriment de Viserys qui ne cherche qu'à retrouver la gloire qui lui est due. En provoquant la colère du Khal Drogo, celui-ci consent à lui offrir une couronne en or, mais sans préciser qu'il s'agira d'or en fusion versé directement sur son crâne. Viserys meurt brûlé sous les yeux de sa sœur, qui constate qu'il n'était pas un dragon, car le feu ne peut pas les atteindre.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=822A9AJILwpu502mqoAA95773');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S1 E7
    $episode = new episode(1,7);
    $episode->setTitre("Gagner ou mourir");
    $episode->setDescription("Robert a été grièvement blessé par un sanglier lors de sa partie de chasse et se meurt. La question de la succession pose problème à Eddard Stark, qui apprend de la bouche de Cersei Lannister que ses fils sont de Jaime, enfants de la consanguinité pour assurer la pérennité de leur sang comme la famille Targaryen. Eddard ne voit donc qu'un seul héritier légitime : Stannis Baratheon, frère de Robert. Littlefinger lui déconseille ce choix ; parfaitement au courant de la situation, il préfère voir un roi illégitime régner en paix à la guerre.
Au Mur, Jon Snow doit prononcer ses vœux de membre de la Garde de Nuit et s'attend à être désigné patrouilleur mais il est affecté à l'intendance personnelle du Lord Commandant ; s'il y voit une insulte à ses talents de combattant, Samwell Tarly croit qu'il veut le former à prendre le commandement de la garde. À peine devenu officiellement Garde, son loup, Fantôme, ramène une main humaine à Jon, laissant penser que son oncle est en danger.

À Vaes Dothrak, Daenerys fait tout pour convaincre son époux d'attaquer Port-Réal, mais celui-ci n'y voit aucun intérêt de régner. Il faudra que Daenerys échappe à une tentative d'empoisonnement, orchestrée par le Conseil de Port-Réal mais déjouée par Jorah Mormont, pour que Khal Drogo se décide à prendre les armes pour offrir à son fils à venir le trône qui lui revient.

Avant de mourir, Robert fait d'Eddard le Protecteur du royaume et régent du Trône en attendant la majorité de Joffrey. Ned tente alors de s'acheter la fidélité du Guet de la ville par l'intermédiaire de Littlefinger. Mais quand Ned tente de faire valoir ses droits auprès de Cersei et son fils, celle-ci déchire le parchemin et le fait arrêter par le Guet, Littlefinger l'ayant trahi.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=5719Bmiek773412zr4343');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S1 E8
    $episode = new episode(1,8);
    $episode->setTitre("Frapper d'estoc");
    $episode->setDescription("Alors que Lord Eddard Stark vient d'être envoyé aux cachots, Cersei Lannister ordonne l'assassinat de l'ensemble du clan Stark. Arya parvient à s'échapper du château, mais sa sœur, Sansa, est faite prisonnière par la reine qui l'oblige à reconnaître la culpabilité de son père, malgré ses suppliques. Prévenu de l'arrestation de son père, Robb Stark met en branle les armées du nord et décide de marcher sur Port-Réal, laissant la garde de Winterfell à son frère Bran. Toujours aux Eyrié, Catelyn Stark, échoue à rallier le Val d'Arryn à leur cause et rejoint Robb durant sa campagne.
Quant à Tyrion Lannister, il rejoint les armées de Lord Tywin, aidé de Bronn et des membres des Clans de la Lune rencontrés lors de sa fuite des Eyrié.

De l'autre côté du Détroit, Daenerys Targaryen décide de sauver plusieurs femmes d'un pillage. L'une d'elles se propose de la remercier en soignant son mari, le Khal Drogo, blessé lors des combats.

Dans un élan désespéré, Sansa implore pour son père la clémence du roi Joffrey Baratheon, que ce dernier semble disposé à accepter à l'unique condition qu'Eddard reconnaisse la légitimité du roi.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=691IMsrnpwv03t0vz6853');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S1 E9
    $episode = new episode(1,9);
    $episode->setTitre("Baelor");
    $episode->setDescription("
Robb Stark poursuit son avancée vers Port-Réal et décide d'organiser une diversion pour capturer Jaime Lannister pour l'utiliser comme monnaie d'échange contre son père et ses sœurs. Tyrion Lannister, après s'être épanché auprès d'une mystérieuse inconnue sur le tragique de son passé, participe aux combats, à la tête de hordes de sauvages, mais s'évanouit, blessé à la tête par l'un de ses hommes.
De l'autre côté du Détroit, Khal Drogo est mourant car sa blessure à la poitrine s'est infectée. Daenerys Targaryen décide de faire appel à une sorcière et à la magie du sang pour le sauver. Répudiée par son peuple et sur le point d'accoucher, elle s'évanouit après que son chevalier-lige a tué le frère de Khal Drogo.

A Port-Réal, lord Eddard Stark accepte de confesser ses péchés et reconnaît sa fille, Arya Stark, parmi la foule. Au dernier instant, le cruel Joffrey Baratheon décide de le faire décapiter pour le punir de sa traîtrise malgré les suppliques de sa mère, de Sansa et de ses conseillers.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=771FLyvhgjmz0oqtu7773');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S1 E10
    $episode = new episode(1,10);
    $episode->setTitre("De feu et de sang");
    $episode->setDescription("
La mort d'Eddard attise la haine dans le camp Stark. Robb et Catelyn crient vengeance contre les Lannister. Fatigués de voir autant de prétendus rois réclamer le Trône de Fer (Renly, Stannis et Joffrey), les bannerets du Nord décident de proclamer Robb roi du Nord et du Conflans.
Dans le camp de Lord Tywin, ce dernier charge son fils de se rendre à Port-Réal pour gouverner en tant que main du roi de Joffrey à la place d'Eddard.

Du côté des sœurs Stark, Arya a pris la fuite vers le Mur à l'aide de Yoren de la Garde de Nuit, en route vers le Nord avec des nouvelles recrues. Sansa, quant à elle, est condamnée à vivre aux côtés du jeune et tyrannique roi Joffrey, bien décidé à l'humilier.

Au Mur, le Lord commandant Jeor Mormont est ravi de voir que Jon a renoncé à déserter et informe son intendant qu'il souhaite partir en expédition au-delà du Mur, afin d'y voir une bonne fois pour toutes ce qui s'y trame, et faire face à la menace des Marcheurs Blancs.

Enfin, à l'Est, Daenerys est mortifiée de voir Drogo vivant mais plongé dans un coma éveillé. Elle met fin à ses souffrances en l'étouffant et incinère son corps, ainsi que la sorcière qui l'a manipulée. Elle rejoint aussi le gigantesque bûcher avec ses œufs de dragons. Le lendemain, c'est avec stupeur que Jorah Mormont et les Sang-coureurs de la Khaleesi découvrent que Daenerys a survécu au feu, entourée de trois dragons nouveaux-nés.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=522u0xABBB9DvzvHFM3600zz6963');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E1
    $episode = new episode(2,1);
    $episode->setTitre("Le Nord se souvient");
    $episode->setDescription("
Déterminé à venger la mort de son père, le nouveau roi du Nord, Robb Stark, continue sa guerre contre les Lannister et le jeune roi Joffrey. Mais il y a davantage de prétendants au trône de fer, dont Stannis Baratheon, soutenu par une prêtresse d'un étrange dieu. Pendant ce temps, Jon Snow et la Garde de Nuit continuent leur marche au-delà du Mur tandis que Daenerys et les restes de son khalasar sont contraints à traverser le Désert rouge. Plus tard, tous les bâtards sont exécutés, sûrement sur ordre de Joffrey (à ce moment-là, ce n'est pas précisé explicitement).");
    $episode->setLien('http://www.purevid.com/?m=embed&id=64137sssqjicehmrp7533');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E2
    $episode = new episode(2,2);
    $episode->setTitre("Les Contrées nocturnes");
    $episode->setDescription("
Theon Greyjoy retourne auprès de son père, qui compte retrouver son titre de roi. Tyrion, désormais main du Roi, a vent du massacre des bâtards du roi Robert et cherche le responsable. Arya est contrainte de partager son secret avec Gendry. Au nord, Samwell et Jon découvrent l'un des secrets de Craster. Daenerys est de plus en plus isolée, désormais ennemie de tous les autres Khals. À Peyredragon, Stannis et ses hommes se préparent à la conquête du trône, sous l'influence directe de Mélisandre.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=28133ggsw1807y0tm4833');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E3
    $episode = new episode(2,3);
    $episode->setTitre("Ce qui est mort ne saurait mourir");
    $episode->setDescription("Jon Snow découvre la vérité sur le pacte silencieux entre la Garde de Nuit et Craster. Theon Greyjoy doit choisir entre sa famille de sang et sa famille d'adoption quand son père prévoit d'attaquer Winterfell. Tyrion met en place un plan pour démasquer qui dans le conseil du Roi l'espionne pour le compte de la reine. Renly Baratheon devient un roi apprécié et sage, mais ses amours pourraient lui porter préjudice.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=18103nmkfhfrruuke7413');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E4
    $episode = new episode(2,4);
    $episode->setTitre('Le Jardin des os');
    $episode->setDescription("Les victoires de Robb Stark énervent le roi Joffrey, qui se venge sur Sansa, qui n'a que Tyrion et Bronn comme soutien dans la cour. Tyrion continue de constituer son réseau d'information au sein de Port-Réal. Catelyn essaie de réconcilier les deux frères Stannis et Renly pour s'allier contre les Lannister, or les deux veulent le Trône de Fer. Au loin, Daenerys parvient à rejoindre les portes de Qarth mais se heurte à la curiosité des dirigeants qui veulent voir les dragons.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=542046DBCyvyvyz96E673xvu7743');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E5
    $episode = new episode(2,5);
    $episode->setTitre('Le Fantôme d\'Harrenhal');
    $episode->setDescription("Alors que l'armée de Renly rejoint celle de Stannis, Tyrion découvre les intentions de Cersei pour défendre Port-Réal : le feu grégeois. Pendant ce temps, Daenerys reçoit une proposition de mariage liée à une somme d'argent considérable. Arya, désormais au service de Tywin, se fait un nouvel ami, tout comme sa mère sur le chemin du retour vers le campement de Robb. Snow a l'occasion de participer à une expédition avec le célèbre Qhorin Mimain. Théon espère obtenir le respect de son équipage et de son père en attaquant le Nord, tandis que Bran doit prendre les décisions stratégiques pour protéger le royaume du Nord.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=341uystvxvvfghmx48693');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E6
    $episode = new episode(2,6);
    $episode->setTitre('Les Anciens et les Nouveaux Dieux');
    $episode->setDescription("
Theon Greyjoy s'empare de Winterfell sans grand combat, et assoit son autorité en décapitant lui-même Ser Rodrik Cassel. La nouvelle arrive vite au camp de Robb Stark, qui doit y faire face alors que Tywin Lannister organise sa prochaine attaque. Tyrion doit faire front à son neveu et roi Joffrey lors d'une révolte populaire où eux deux, mais aussi Sansa et Cersei manquent de mourir sous les coups des badauds. Jon croise la route d'une sauvageonne, Ygritte, qui va mettre à l'épreuve ses valeurs. À Qarth, les dragons sont volés.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=612u20POO1x0pspxxwx0y9EE8533');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E7
    $episode = new episode(2,7);
    $episode->setTitre('Un homme sans honneur');
    $episode->setDescription("
Quand il apprend que Bran et Rickon ont fui Winterfell, Theon Greyjoy les traque tout en martyrisant les villageois alentour. Dans les montagnes du Nord, Jon Snow est mis à mal dans sa fierté quand Ygritte découvre que les Gardes de nuit ont fait vœu d'abstinence. À Port-Réal, Sansa a ses premières règles, et devient donc une possible mère pour les enfants de Joffrey. Dans le camp de Robb Stark, celui-ci s'absente et Jaime en profite pour tenter de s'enfuir. À Qarth, le Conseil des Treize est décimé par l'alliance entre la guilde des Conjurateurs et le négociant qui s'était porté garant pour la Khaleesi.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=682FFIswsJMH7E9BGHA77krl6873');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E8
    $episode = new episode(2,8);
    $episode->setTitre("Le Prince de Winterfell");
    $episode->setDescription("Yara vient chercher son frère Théon à Winterfell, de peur qu'il ne meure loin de son île natale. Catelyn relâche Jaime sans l'accord de son fils Robb, le roi du Nord. Arya termine son accord, apprenant l'attaque imminente du camp de Robb par les forces de Tywin Lannister. Tyrion met en place des tactiques militaires pour prévenir l'attaque de Stannis, mais celles-ci ne plaisent pas à Cersei.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=2720151x0sttABDwz2410oik6793');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E9
    $episode = new episode(2,9);
    $episode->setTitre("La Néra");
    $episode->setDescription("L'arrivée de l'armée de Stannis Baratheon aux portes de Port-réal est imminente. Grâce à Varys, Tyrion met en place sa stratégie pour repousser les nombreux hommes et la flotte chargée, pendant que la Reine se prépare à l'état de siège, plus dans la crainte de savoir son jeune fils Joffrey sur le champ de bataille.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=9319Bmjjn16uugekq6563');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S2 E10
    $episode = new episode(2,10);
    $episode->setTitre("Valar Morghulis");
    $episode->setDescription("Port-Réal célèbre sa victoire sur les hommes de Stannis Baratheon, qui rumine sa défaite, et le roi Joffrey récompense Tywin Lannister et la famille Tyrell, oubliant sciemment Tyrion, en vie mais défiguré. Theon Greyjoy est forcé d'abandonner Winterfell. Robb trahit sa promesse envers les Frey pour épouser la femme qu'il aime. Arya croise à nouveau la route de Jaqen, elle apprend qu'il est un assassin du groupe des Sans Visages et elle se voit offrir une pièce qui lui permettra de le retrouver. Au-delà du Mur, Jon Snow tue Mimain en duel et se prépare à rencontrer Mance Rayder. À Qarth, Daenerys Targaryen pénètre dans la tour de l'Immortel et récupère ses dragons. Après quoi, le retour des Marcheurs Blancs est officiellement signé.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=972LMS78544579C5B75x3y353693');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S3 E1
    $episode = new episode(3,1);
    $episode->setTitre("Valar Dohaeris");
    $episode->setDescription("
Jon Snow est amené devant Mance Rayder, le Roi d'au-delà du Mur, alors que les chevaliers de la Garde de Nuit entament leur retraite vers le Sud. À Port-Réal, Tyrion exige sa récompense, tandis que Petyr Baelish offre à Sansa Stark une porte de sortie. Cersei, elle, organise un banquet en l'honneur de la famille royale. Daenerys s'embarque pour la Baie des Serfs, où elle va recruter de nouveaux soldats.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=611uysrpqsreccdlj8703');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S3 E2
    $episode = new episode(3,2);
    $episode->setTitre("Noires Ailes, Noires Nouvelles");
    $episode->setDescription("Sansa fait l'objet de toutes les attentions des Tyrell qui cherchent les secrets de Joffrey. L'armée de Robb Stark fait route vers Vivesaigues pour les funérailles du père de Catelyn Stark. Brienne et Jaime Lannister continuent leur marche bon gré mal gré. Arya et ses compagnons croisent la route de la Fraternité sans Bannière.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=4613B471zjpz4y3tz5363');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S3 E3
    $episode = new episode(3,3);
    $episode->setTitre("Les Immaculés");
    $episode->setDescription("Tyrion fait face à de nouvelles responsabilités. Robb et sa mère assistent aux funérailles du père de Catelyn tandis que Brienne et Jaime sont désormais prisonniers de Locke. De son côté, Daenerys s'interroge sur les esclaves de la cité et accepte de faire échange du plus grand de ses dragons contre tous les Immaculés, ce qui plait peu à ses conseillers. Arya, quant à elle, se sépare d'un de ses amis. Quant à lui, Jaime Lannister, par son habileté à argumenter, empêche les hommes de Robb de violer Brienne, De plus, il essaie de manipuler les hommes de Robb qui l'ont capturé, mais Locke le menace et lui coupe la main finalement.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=791LLghvxvztwry457573');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S3 E4
    $episode = new episode(3,4);
    $episode->setTitre("Voici que son tour de garde est fini");
    $episode->setDescription("
Jaime et Brienne sont en mauvaise posture. Daenerys met à feu et à sang Astapor à l'aide de son immense armée et de ses dragons et libère les esclaves de la ville. Theon se fait trahir et est à nouveau torturé. Pendant ce temps, Margaery se fait de plus en plus apprécier par Joffrey, ce que Cersei redoute, voyant qu'elle sait le manipuler. Enfin, Arya fait connaissance avec Béric Dondarrion qui veut un combat comme justice envers le Limier. Puis, près du Mur, une mutinerie éclate où le Lord Commandant Jeor Mormont et Craster trouvent la mort. Sam s'enfuit avec une de ses filles et son bébé, né il y a peu.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=1923B8MLL131AEC56976Cz1x3873');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S3 E5
    $episode = new episode(3,5);
    $episode->setTitre("Baisée par le feu");
    $episode->setDescription("
Daenerys rencontre les officiers des Immaculés et en découvre le chef désigné. Robb fait face à la perte d'une moitié de son armée et remet en question sa stratégie face à la trahison et à de nombreuses complications. Jaime révèle une lourde vérité à Brienne tandis que lord Tywin décide de marier Sansa à Tyrion et Cersei à Loras pour contrer la stratégie des Tyrell, qui désirent éloigner Sansa, la clé du nord. Le Limier est remis en liberté et Arya s'y oppose tandis que Snow a sa première fois avec Ygritte et qu'il se découvre des sentiments envers elle.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=4819Cplgfmqtz11ij6593');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S3 E6
    $episode = new episode(3,6);
    $episode->setTitre("L'Ascension");
    $episode->setDescription("
Alors que les hommes de Mance Rayder se préparent à l'escalade du Mur, Ygritte réalise que Jon Snow n'a pas renié son allégeance à la Garde de Nuit. Robb est aux abois et entame les négociations avec la famille Frey. Tywin Lannister et Olenna Tyrell organisent l'avenir commun de leurs familles. Arya croise la route de Mélisandre venue chercher Gendry.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=891OUy11ygn28z72y5173');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S3 E7
    $episode = new episode(3,7);
    $episode->setTitre("L'Ours et la Belle");
    $episode->setDescription("
Tyrion et Sansa accusent le coup de l'annonce de leur mariage arrangé. Le roi Joffrey a vent du sac d'Astapor et craint l'arrivée des dragons. Jon Snow continue sa marche avec les Sauvageons mais a peur que leur attaque soit vaine. Jaime est séparé de Brienne et craint pour la vie et l'honneur de sa compagne de route.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=772FMIDEB455AED868klnrlq6173');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S3 E8
    $episode = new episode(3,8);
    $episode->setTitre("Les Puînés");
    $episode->setDescription("Sansa et Tyrion se marient. Sandor Clegane avoue à Arya ce qu'il veut faire d'elle. Gendry arrive dans le château de Stannis qui s'interroge sur l'avenir que lui réserve Mélisandre. De son côté, Daenerys rallie une nouvelle armée à sa cause. Quant à Sam, il continue son retranchement vers le « mur » et rencontre un « marcheur blanc ».");
    $episode->setLien('http://www.purevid.com/?m=embed&id=782IOPMPKAC7175HHGnvrH9A5683');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S3 E9
    $episode = new episode(3,9);
    $episode->setTitre("Les Pluies de Castamere");
    $episode->setDescription("Daenerys attaque Yunkai par surprise. Jon et les sauvageons commencent leur invasion, mais Jon remet en cause les méthodes de ses compagnons d'armes. Bran et son petit groupe continuent leur marche vers le Mur, Sam y arrive mais de l'autre côté. Arya atteint les jumeaux, alors qu'Edmure est présenté à sa fiancée. Le mariage est nommé « les noces pourpres » en raison du cruel massacre des Stark et de tous leurs hommes présents, orchestré par Walder Frey et Roose Bolton. Talisa Maegyr, enceinte, est éventrée, Catelyn et Robb sont touchés par des carreaux d'arbalète mais finissent poignardés et égorgés.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=252u2yJKG145PVQ8EDNTQ7835083');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S3 E10
    $episode = new episode(3,10);
    $episode->setTitre("Mhysa");
    $episode->setDescription("La famille Lannister se réjouit de la mort du roi Robb Stark, seul Tyrion montre de l'appréhension quant à la durée de la guerre et ce qu'il faudra faire pour l'achever. Bran croise la route de Sam alors que chacun traverse le Mur dans des sens contraires. La nouvelle de l'arrivée des marcheurs blancs arrive à Peyredragon.
De son côté, Daenerys libère les esclaves de Yunkaï en se faisant acclamer et porter en son honneur. Yara, la sœur de Théon, prend les 50 meilleurs tueurs des Iles-de-Fer pour aller libérer son petit-frère après avoir reçu un horrible cadeau et défié son propre père.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=172x00123GCDjqmHJFrsyB796463');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S4 E1
    $episode = new episode(4,1);
    $episode->setTitre("Deux Épées");
    $episode->setDescription("
Jaime refuse une offre humiliante de son père, qui pense qu'il n'est plus capable d'effectuer son devoir à la Garde Royale avec une main en moins. Oberyn Martell arrive à Port-Réal et déclare ses intentions à Tyrion, qui joue le rôle de diplomate.
Daenerys avance sur Meereen et découvre une vérité sur ses dragons en plus de devoir faire face à l'audace de Daario Naharis. Sansa, quant à elle, se remet peu à peu de la nouvelle de la mort de son frère et de sa mère. Le mariage royal se prépare, tandis que Jon Snow se remet peu à peu de son expérience au-delà du mur et tente d'avertir ses supérieurs.

De son côté, Arya découvre sa face sombre et aide le Limier dans un combat violent et sanguinaire, qui lui permet de récupérer Aiguille, son épée offerte par Jon.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=36103ps3543olhk344043');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S4 E2
    $episode = new episode(4,2);
    $episode->setTitre("Le Lion et la Rose");
    $episode->setDescription("Tyrion demande à Bronn de donner des leçons de combat à son frère Jaime et fait envoyer Shae à Pentos pour apaiser le courroux de son père.
Bran a de plus en plus de mal à contrôler ses visions de loup.

Ramsay se dispute avec son père au sujet du traitement qu'il a infligé à Théon, qui apprend par la même occasion la mort de Robb et Catelyn.

Le mariage de Margaery et Joffrey est célébré en grande pompe. Mais, alors que l'ambiance est déjà tendue, la cérémonie vire au drame et Tyrion se retrouve en grand danger.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=1810843rry234tv203793');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S4 E3
    $episode = new episode(4,3);
    $episode->setTitre("Briseuse de chaînes");
    $episode->setDescription("Tyrion est arrêté pour le meurtre de Joffrey et sera jugé dans deux semaines. Dontos aide Sansa à s'échapper de la capitale et la ramène à Petyr Baelish qui a tout planifié pour lui faire quitter la capitale. Tywin commence à préparer Tommen qui sera le prochain roi. Il demande au prince Oberyn de présider comme troisième juge au procès de Tyrion. Il lui propose, en échange de son aide, de lui livrer La Montagne, l'assassin de sa sœur, Elia. Arya et le limier continuent leur voyage et rencontrent un paysan et sa fille. Le paysan propose au limier de travailler pour lui mais celui-ci le trahit et lui vole son argent. Davos envoie une lettre aux bureaux de la Banque de Fer de Braavos pour demander un prêt pour acheter une nouvelle armée. Dans le nord, Sam envoie Vère à La Mole où il pense qu'elle sera plus en sécurité. Pendant ce temps, les sauvageons continuent d'attaquer les villages du nord. Daenerys arrive aux portes de la ville de Meereen et Daario bat leur champion. Elle demande ensuite aux esclaves de se soulever contre leurs maîtres.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=141orppuqmp02xyw07403');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S4 E4
    $episode = new episode(4,4);
    $episode->setTitre("Féale");
    $episode->setDescription("
Daenerys réussit à prendre le contrôle de Meereen en utilisant les égouts de la ville pour que les Immaculés infiltrent la ville et arment les esclaves. Petyr admet à Sansa son implication dans l'empoisonnement de Joffrey. Il l'informe qu'ils sont en route pour les Eyrié et qu'il va se marier avec sa tante Lysa. Jaime continue de s’entraîner avec Bronn qui le pousse à rendre visite à son frère Tyrion. Jaime est convaincu que son frère n'est pas impliqué dans l'assassinat de Joffrey. Olenna, qui a peur que Cersei le retourne contre elle, encourage Margaery à séduire Tommen. Margaery décide alors de s'introduire la nuit dans la chambre de celui-ci. Cersei demande à Jaime d'aller retrouver Sansa. Jaime donne l'épée que son père lui a offerte à Brienne et lui demande d'aller rechercher Sansa. Il l'oblige à prendre Podrick Payne avec elle pour le protéger.
Dans le nord, de nouvelles recrues arrivent au Mur dont Locke, un homme de Roose Bolton. Janos Slynt suggère à Alliser Thorne d'envoyer Jon Snow au Manoir de Craster pour ne pas lui laisser la chance de devenir le Lord Commandant. Locke décide d'y accompagner Jon. Bran et son groupe arrivent également au Manoir mais ont été rapidement capturés par les mutins et Bran est forcé de révéler son identité.

Plus au nord, un marcheur blanc prend le nouveau-né de Craster et l’emmène avec lui. Un autre marcheur blanc le touche au visage et les yeux de l'enfant deviennent bleus comme les leurs.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=881LQvrij13x002724163');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S4 E5
    $episode = new episode(4,5);
    $episode->setTitre("Premier du nom");
    $episode->setDescription("À Port-Réal, Tommen est proclamé seigneur des Sept Royaumes. Cersei décide que Tommen et Margaery vont se marier dans une quinzaine de jours. Petyr Baelish et Sansa Stark arrivent aux Eyrié et Petyr se marie avec Lysa. Lysa révèle à Sansa qu'elle souhaiterait que Sansa se marie avec son fils Robin. Il est révélé que c'est Lysa qui avait, avec l'aide de Petyr, empoisonné son mari Jon Arryn.
Daenerys apprend la mort de Joffrey. Daario Naharis lui annonce qu'ils ont capturé la flotte Meereenne et Daenerys exprime son désir de l'utiliser pour prendre Westeros. Ser Jorah l'informe que les maîtres ont repris le contrôle d'Astapor et Yunkai.

Brienne décide de se rendre au Mur, croyant que Sansa est allé chez son frère Jon Snow. Brienne est en colère contre Podrick qui ne sait ni monter sur son cheval, ni cuisiner.

Jon Snow et ses hommes arrivent au Fort de Craster et Locke est envoyé pour repérer les mutins. Il aperçoit Bran et son groupe retenus prisonniers dans une petite cabane. Jon décide d'attaquer et Locke s'éloigne de son groupe pour enlever Bran. Bran utilise ses capacités de Change-peau pour entrer dans l'esprit de Hodor et réussit à se libérer de Locke. Jojen déconseille à Bran d'aller voir son frère car ce dernier risque de l'emmener à Chateaunoir. Rast s’enfuit mais est attaqué par le loup de Jon.

Jon propose aux femmes de Craster de l'accompagner au Mur mais elles refusent et lui demandent de brûler le Fort avant de partir.

Arya ressasse sa haine des assassins des membres de sa famille.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=771FN43rmjlxxrr2u6663');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S4 E6
    $episode = new episode(4,6);
    $episode->setTitre("Les Lois des dieux et des hommes");
    $episode->setDescription("
À Braavos, Stannis et Davos demandent un prêt à la Banque de Fer. Au début, leur demande est refusée mais Davos réussit à convaincre la banque de soutenir Stannis en avançant l'argument de son honnêteté pour rembourser ses dettes.
À Meereen, Daenerys exerce ses fonctions de reine. Elle rembourse un éleveur de chèvres dont le troupeau a été attaqué par un de ses dragons et autorise l'enterrement des nobles crucifiés.

À Fort-Terreur, Yara mène une attaque pour sauver son frère Théon, mais celui-ci refuse de l'accompagner et reste fidèle à son maître Ramsay. Voyant l'état de son frère, Yara l’abandonne et annonce à ses hommes que Théon est mort. Ramsay récompense Théon pour lui être resté fidèle en lui permettant de prendre un bain. Ramsay lui annonce qu'il va l'utiliser pour prendre le Moat Cailin.

À Port-Réal, Tyrion est jugé pour l'assassinat du roi Joffrey. Sa sœur Cersei a convaincu beaucoup de monde de témoigner contre lui, y compris Varys et Shae. Jaime annonce à son père qu'il est prêt à quitter la garde royale s'il laisse la vie sauve à Tyrion. Tywin lui promet alors que Tyrion sera reconnu coupable mais que s'il fait repentance il sera exilé à la Garde de Nuit plutôt qu’exécuté. Mais, en voyant que même Shae l'avait trahi, Tyrion refuse de reconnaître sa culpabilité, insulte l'audience, et exige un duel judiciaire.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=582CHGAD9FGHSTR68BQIKotr6153');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S4 E7
    $episode = new episode(4,7);
    $episode->setTitre("L'Oiseau moqueur");
    $episode->setDescription("Tyrion est désespéré après avoir su que Cersei a fait appel à Gregor Clegane pour être son champion. Jaime refuse de se battre pour lui car il ne peut plus se battre correctement sans sa main droite.
Quelques jours plus tard, Bronn visite Tyrion et l'informe qu'il va épouser une dame de haute naissance, et ne pourra pas être son champion. Durant la nuit, en rendant visite à Tyrion, Oberyn Martell lui propose de se battre pour lui afin de venger la mort de sa sœur Elia et de ses enfants, tous assassinés par Ser Gregor.

Mélisandre et Selyse discutent de leur départ de Peyredragon. Mélisandre y informe Selyse que le Maitre de la lumière a besoin de sa fille.

Daenerys a une aventure avec Daario avant de l'envoyer avec les puînés reprendre Yunkaï. Elle lui ordonne de tuer tous les maîtres de la ville. En discutant avec Ser Jorah, Daenerys change d'avis : ils auront le choix entre se repentir ou mourir.

Jon Snow fait son compte-rendu à Châteaunoir sur le combat contre les rebelles du Manoir de Craster. Il met en garde Alliser sur la faille que représente le corridor menant au Nord du mur pour l'armée de Mance Rayder. Alliser n'en tient pas compte et attribue à Jon et à Sam la tâche de surveiller le Nord depuis le haut du Mur.

Le Limier et Arya se font attaquer par Rorge et un autre homme. Pendant cette attaque, le Limier se fait mordre au cou. Ce dernier refuse qu'Arya lui désinfecte sa blessure avec le feu. Elle se contente de laver la plaie à l'eau et de la recoudre.

Brienne et Podrick rencontrent Tourte-chaude et Brienne l'informe qu'ils sont à la recherche de Sansa Stark. Tourte-chaude leur précise qu'il n'a jamais vu Sansa mais qu'il a rencontré Arya. La dernière fois qu'il l'a vue, elle était prisonnière de la Fraternité sans bannière, tout comme le Limier. Podrick en conclut que la meilleure façon d'obtenir une rançon d'Arya serait de la livrer aux Eyrié, auprès de sa tante.

Dans les Eyrié, Sansa construit une réplique du château de Winterfell dans la neige. Robin ruine la réplique, Sansa le gifle et Robin s'enfuit. Petyr qui a vu la scène soutient Sansa puis l'embrasse par la suite. Lysa qui a assisté à l'échange du baiser, menace alors Sansa de la jeter par la Porte de la lune mais Petyr intervient pour la calmer. Il avoue alors à sa femme que Catelyn était son seul et unique amour, puis pousse Lysa par la porte de la lune.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=131lqvricghurkdgj8443');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S4 E8
    $episode = new episode(4,8);
    $episode->setTitre("La Montagne et la Vipère");
    $episode->setDescription("Les Sauvageons attaquent La Mole et massacrent tous les habitants, y compris trois hommes de la Garde de Nuit qui s'y trouvaient ; Vère et son enfant parviennent à échapper au carnage. À Châteaunoir, Jon Snow se rend à l'évidence : ils ne sont pas de taille face à l'armée de Mance Rayder.
À Meereen, Barristan Selmy reçoit une lettre de Port-Réal. Il s'agit d'une lettre d'amnistie pour Jorah Mormont, signée de la main du roi Robert Baratheon. Mormont confesse à Daenerys qu'il a travaillé comme espion pour Lord Varys mais qu'il a depuis abandonné cette cause. Daenerys reste inflexible et le bannit.

Ramsay Snow envoie Théon Greyjoy, héritier des îles de Fer, négocier la capitulation du Moat Cailin en échange de la possibilité pour les Fer-nés de retourner sains et saufs dans leurs foyers. Le marché accepté par les Fer-nés, Ramsay les fait tous écorcher. Lord Bolton, satisfait du travail de son fils, légitime celui-ci.

Aux Eyrié, Lord Baelish est interrogé par les notables quant aux circonstances de la mort de Lysa Arryn. Il affirme qu'il s'agit d'un suicide mais on préfère interroger Sansa Stark. À la surprise de Littlefinger, elle avoue son identité mais ment aussi, disant que Lysa s'est suicidée folle de jalousie. Aussitôt blanchi, Littlefinger prend en main les affaires du château en décidant de prendre Robin sous son aile.

Arya et le limier arrivent aux Eyrié et sont informés de la mort de Lady Arryn.

À Port-Réal, Tyrion se prépare à assister au duel entre Oberyn Martell et la Montagne. Si le combat tourne à l'avantage du prince de Dorne, plus agile et équipé d'une armure plus légère, celui-ci est trop occupé à faire avouer le meurtre de sa sœur Elia Martell et Clegane profite d'une distraction pour faire chuter Oberyn et lui faire exploser le crâne. Bien que blessé, Gregor Clegane est déclaré vainqueur et Tyrion est donc condamné à mort.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=8316E41ln40fftlhh5183');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S4 E9
    $episode = new episode(4,9);
    $episode->setTitre("Les Veilleurs au rempart");
    $episode->setDescription("Au Nord, Mance Rayder lance le premier assaut vers le Mur par les Sauvageons et l'annonce par un immense brasier. Du côté du Mur, il envoie de nombreux Sauvageons mais aussi des Géants. Châteaunoir est aussi attaqué par le sud par le petit groupe de Sauvageons composé entre autres de Ygritte. Après une lutte acharnée, au cours de laquelle beaucoup de combattants sont tués, comme Pyp, Ser Alliser et Ygritte, la Garde de nuit parvient à repousser ce premier assaut mais l'armée des Sauvageons est loin d'être défaite. Une prochaine attaque ne saurait tarder. Jon Snow part de l'autre côté du mur afin de tuer Mance Rayder et espère ainsi mettre l'armée des Sauvageons en déroute.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=711x3yvlgjgifkk887003');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S4 E10
    $episode = new episode(4,10);
    $episode->setTitre("Les Enfants");
    $episode->setDescription("Au Nord, Jon Snow rencontre Mance Rayder, pour négocier la fin de la guerre. Stannis Baratheon arrive alors avec son armée, détruit l'armée des Sauvageons et capture le Roi d'au-delà du Mur.
À Port-Réal, Cersei refuse toujours de se marier avec Ser Loras Tyrell et révèle la vérité à son père au sujet d'elle et son frère. Tyrion, quant à lui, s'évade de son cachot avec l'aide de Jaime et de Varys. Ce dernier cache le nain dans une caisse qui est transférée par bateau à Essos, et choisit de rester sur ce bateau pour échapper aux foudres de la famille royale. Sur son chemin dans le château pour s'enfuir, Tyrion retrouve Shae dans le lit de son père et la tue en l'étranglant ; il retrouve aussi son père qu'il abat de deux carreaux d'arbalète.

De l'autre coté du mur, Brandon Stark arrive enfin au barral qu'il apercevait dans ses rêves et rencontre la corneille à trois yeux et les Enfants de la forêt. Il obtiendra la capacité de voler.

Près de la Porte sanglante, Brienne de Torth rencontre Arya et Sandor Clegane, le Limier. Après une lutte acharnée entre les deux guerriers qui se termine au corps à corps, le Limier tombe de la falaise. Pendant ce temps Arya s'échappe, laissant le Limier agoniser. Elle trouve alors un bateau avec lequel elle espérait rejoindre le Nord du mur, mais grâce à la pièce que lui a donné Jaqen H'Ghar, elle convainc le capitaine bravoosi de l'amener à Braavos également.

Quant à Daenerys, la situation est grave : elle doit faire face à un esclave qui veut retourner à son maître et elle apprend que Drogon a brûlé une petite fille de trois ans. Déchirée et tourmentée, Daenerys enchaîne et enferme Viserion et Rhaegal, ses dragons, pour éviter qu'ils ne tuent eux aussi des innocents. Drogon, quant à lui, est en liberté et introuvable. Daenerys, en pleurs, referme le souterrain.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=3929CHGDJ978B79qzuIGI9AB5183');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S5 E1
    $episode = new episode(5,1);
    $episode->setTitre('The Wars to Come');
    $episode->setDescription("Dans un flashback, la jeune Cersei rend visite à une sorcière qui lui fait une prédiction de son avenir. À Port-Réal, la famille Lannister pleure la mort de Tywin ; Cersei blâme son frère Jaime pour avoir permis sa mort en libérant Tyrion. Dans l'ombre, les Tyrell commencent à envisager d'écarter la régente du pouvoir. Tyrion a fui à Pentos grâce à Varys, qui a déjà un plan : rejoindre Meereen et prendre la cause de Daenerys. Mais celle-ci voit la politique la rattraper alors qu'elle ne contrôle plus ses dragons : une faction rebelle commence à s'en prendre aux Immaculés et plusieurs seigneurs exigent qu'elle rouvre les arènes de combat en échange de leur soumission.
Sansa suit Petyr Baelish, qui confie Robin Arryn à un petit seigneur avant de partir loin de l'influence de Cersei. Au mur, Jon Snow reçoit une tâche délicate de la part de Stannis Baratheon : forcer Mance Rayder à le reconnaître comme roi et mener à ses côtés les Sauvageons vers Winterfell. Mance refuse et est donc condamné au bûcher. Peu avant que les flammes ne le tuent, Jon décide d'abréger ses souffrances en l'abattant d'une flèche.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=161uzvy3zjmtvy5237113');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S5 E2
    $episode = new episode(5,2);
    $episode->setTitre('The House of Black and White');
    $episode->setDescription("
À Port-Réal, Cersei Lannister organise sa régence, en nommant Mace Tyrell Grand Argentier et son oncle Kevan au poste de Maître de Guerre, mais celui-ci refuse. Elle reçoit un avertissement de Dorne, une menace contre sa fille Myrcella qui ne survit que grâce à la protection du frère d'Oberyn, Doran ; Jaime se propose d'aller la récupérer en secret avec l'aide de Bronn. Dans les terres de l'est, Brienne et Podrick retrouvent Sansa et Littlefinger dans une auberge où la femme chevalier offre ses services à la fille de Catelyn Stark. Lord Baelish la repousse et après la fuite, Brienne choisit de suivre Sansa à distance. Au Mur, Jon Snow se voit proposer d'être libéré de son crime de bâtardise en échange de l'allégeance des Sauvageons, qu'il serait capable de mener comme Roi du Nord. Jon refuse et alors que se prépare l'élection du 998e Lord Commandant de la Garde de Nuit, il est nommé candidat et finit élu.
Arya Stark atteint les côtes de Braavos mais elle se voit refuser l'entrée du palais des Sans-Visage. Après avoir erré dans les rues, elle est finalement reconnue et acceptée. À Meereen, le cas d'un des Fils de la Harpie divise la ville. Sur conseil de Barristan Selmy qui lui avise de ne pas devenir comme son père, Daenerys consent à lui accorder un procès juste mais il est exécuté par un ancien esclave. Daenerys le fait alors exécuter, ce qui lui attire les foudres du peuple. Son seul réconfort sera le retour de Drogon.");
    $episode->setLien('http://www.purevid.com/?m=embed&id=432uzzD9G64465743z374y014793');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S5 E3
    $episode = new episode(5,3);
    $episode->setTitre('High Sparrow');
    $episode->setDescription("Le mariage de Tommen et Margaery est célébré, et Cersei voit son fils sous le charme de sa nouvelle épouse qui lui suggère d'éloigner la régente de Port-Réal. Cersei s'occupe alors de l'humiliation qu'a subi le Grand Septon, surpris dans le bordel de Littlefinger par la secte des Moineaux ; à la surprise de leur chef spirituel, le Grand Moineau, Cersei prend leur parti et fait emprisonner le chef religieux licencieux. Près de Winterfell, Sansa découvre le plan que lui a réservé Littlefinger : il veut la marier à Ramsay Bolton, afin d'allier le Nord et les Eyrié à nouveau. Au Mur, Jon accepte sa charge et refuse l'offre de Stannis, qui le laisse donc avec les Sauvageons. Jon subit vite sa première épreuve quand Janos Slynt se rebelle contre son autorité, et malgré ses supplications une fois sur le billot, Jon le décapite.
Arya commence sa vie parmi les Sans-Visage, qui se montrent durs et la forcent à renoncer à tous ses biens personnels, y compris son épée. De leur côté, Tyrion et Varys se risquent à une sortie dans les rues de Volantis, le nain ne supportant plus l'enfermement. Mais en se rendant dans un bordel, ils croisent la route de Jorah Mormont, qui reconnait le Lannister et choisit de l'enlever pour le livrer à la « Reine ».");
    $episode->setLien('http://www.purevid.com/?m=embed&id=262xz3754urq00yBAD1wupmp8333');

    $em = $this->getDoctrine()->getManager();
    $em->persist($episode);
    $em->flush();

    //S5 E4
    $episode = new episode(5,4);
    $episode->setTitre("The Sons of the Harpy");
    $episode->setDescription('...');
    $episode->setLien('http://www.purevid.com/?m=embed&id=132lotGJGICGsxt37BMMI9DB7013');

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
