<?php

namespace rj\StreamBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class User
{

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="ip", type="string", length=255)
     */
    private $ip;


    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="saison", type="integer")
     */
    private $saison;

    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(name="episode", type="integer")
     */
    private $episode;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    

    /**
     * Set ip
     *
     * @param string $ip
     * @return User
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string 
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return User
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set saison
     *
     * @param integer $saison
     * @return User
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
     * Set episode
     *
     * @param integer $episode
     * @return User
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

    public function __construct($saison, $episode)
    {
        $this->ip = $_SERVER["REMOTE_ADDR"];
        $this->saison = $saison;
        $this->episode = $episode;
        $this->date = new \Datetime();
    }
}
