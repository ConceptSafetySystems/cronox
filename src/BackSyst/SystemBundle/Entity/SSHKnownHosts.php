<?php

namespace BackSyst\SystemBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SSHKnownHosts
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="BackSyst\SystemBundle\Entity\SSHKnownHostsRepository")
 */
class SSHKnownHosts
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="host_name", type="string", length=255, nullable=false)
     */
    private $hostName;

    /**
     * @var string
     *
     * @ORM\Column(name="rsa_public_key", type="text")
     */
    private $rsaPublicKey;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="date", nullable=true)
     */
    private $createdAt;

    /**
     *  Constructor for the class. Sets createdAt to Now
     *
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     *  Get value for data '$name'
     *  @param string $name
     *  @return Type of data '$name'
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     *  Set value for data '$name'
     *  @param string $name
     *  @param generic $value
     *  @return Type of data '$name'
     */
    public function __set($name, $value)
    {
        $this->$name = $value;
        return $this;
    }

    public function offsetGet($name)
    {
        return $this->$name;
    }
}
