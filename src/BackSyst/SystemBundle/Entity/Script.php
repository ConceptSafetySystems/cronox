<?php

namespace BackSyst\SystemBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Script
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="BackSyst\SystemBundle\Entity\ScriptRepository")
 */
class Script
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
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="script", type="text", nullable=false)
     */
    private $script;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var backups[]
     *
     * $ORM\OneToMany(targetEntity="Backup", mappedBy="script", indexBy="id", cascade={"persist", "remove"})
     */
    private $backups;

    /**
     * @var parameters[]
     *
     * $ORM\OneToMany(targetEntity="Parameter", mappedBy="script", indexBy="id")
     */
    private $parameters;

    /**
     * @var string
     *
     * @ORM\Column(name="command_line", type="text", nullable=true)
     */
    private $commandLine;

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
