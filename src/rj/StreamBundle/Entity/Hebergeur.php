<?php

namespace rj\StreamBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Hebergeur
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Hebergeur
{

    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="episode", type="integer")
     */
    private $episode;

    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="saison", type="integer")
     */
    private $saison;

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="hebergeur", type="string", length=255)
     */
    private $hebergeur;

    /**
     * @var string
     *
     * @ORM\Column(name="lien", type="string", length=255)
     */
    private $lien;


    public function __construct($saison, $episode, $hebergeur)
    {
        $this->saison = $saison;
        $this->episode = $episode;
        $this->hebergeur = $hebergeur;
    }

    /**
     * Set episode
     *
     * @param integer $episode
     * @return Hebergeur
     */
    public function setEpisode($episode)
    {
        $this->episode = $episode;

        return $this;
    }

    /**
     * Get episode
     *
     * @return integer 
     */
    public function getEpisode()
    {
        return $this->episode;
    }

    /**
     * Set saison
     *
     * @param integer $saison
     * @return Hebergeur
     */
    public function setSaison($saison)
    {
        $this->saison = $saison;

        return $this;
    }

    /**
     * Get saison
     *
     * @return integer 
     */
    public function getSaison()
    {
        return $this->saison;
    }

    /**
     * Set hebergeur
     *
     * @param string $hebergeur
     * @return Hebergeur
     */
    public function setHebergeur($hebergeur)
    {
        $this->hebergeur = $hebergeur;

        return $this;
    }

    /**
     * Get hebergeur
     *
     * @return string 
     */
    public function getHebergeur()
    {
        return $this->hebergeur;
    }

    /**
     * Set lien
     *
     * @param string $lien
     * @return Hebergeur
     */
    public function setLien($lien)
    {
        $this->lien = $lien;

        return $this;
    }

    /**
     * Get lien
     *
     * @return string 
     */
    public function getLien()
    {
        return $this->lien;
    }
}
