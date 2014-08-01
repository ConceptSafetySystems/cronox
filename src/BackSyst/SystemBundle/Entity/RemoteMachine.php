<?php

namespace BackSyst\SystemBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RemoteMachine
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="BackSyst\SystemBundle\Entity\RemoteMachineRepository")
 */
class RemoteMachine
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
     * @ORM\Column(name="uuid", type="string", length=255)
     */
    private $uuid;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="user", type="string", length=255)
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="host", type="string", length=255)
     */
    private $host;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_tested", type="boolean")
     */
    private $isTested;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_test_success", type="boolean")
     */
    private $isTestSuccess;

    /**
     * @var backups[]
     *
     * $ORM\OneToMany(targetEntity="Backup", mappedBy="machine", indexBy="id", cascade={"persist", "remove"})
     */
    private $backups;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="date", nullable=false)
     */
    private $createdAt;

    /**
     *  Constructor for the class. Sets createdAt to Now
     *
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->uuid = md5(uniqid(rand(), true));
        $this->isTested = false;
        $this->isTestSuccess = false;
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
