<?php

namespace BackSyst\SystemBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Backup
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="BackSyst\SystemBundle\Entity\BackupRepository")
 */
class Backup
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
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="schedule", type="string", length=255, nullable=true)
     */
    private $schedule;

    /**
     * @var BackSyst\SystemBundle\Entity\RemoteMachine
     *
     * @ORM\ManyToOne(targetEntity="RemoteMachine", inversedBy="backups", cascade={"persist"})
     * @ORM\JoinColumn(name="machine_id", referencedColumnName="id", onDelete="cascade")
     */
    private $machine;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_recurrent", type="boolean", nullable=true)
     */
    private $isRecurrent;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_at", type="datetime", nullable=true)
     */
    private $startAt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @var BackSyst\SystemBundle\Entity\Script
     *
     * @ORM\ManyToOne(targetEntity="Script", inversedBy="backups", cascade={"persist"})
     * @ORM\JoinColumn(name="script_id", referencedColumnName="id", onDelete="cascade")
     */
    private $script;

    /**
     * @var string
     *
     * @ORM\Column(name="command_line", type="text", nullable=true)
     */
    private $commandLine;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var processes[]
     *
     * $ORM\OneToMany(targetEntity="WatchProcess", mappedBy="report", indexBy="id")
     */
    private $processes;

    /**
     * @var \integer
     *
     * @ORM\Column(name="notification", type="integer", nullable=true)
     */
    private $notification;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_secure_parameter", type="boolean", nullable=true)
     */
    private $isSecureParameters;

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
        $this->isActive = true;
        $this->createdAt = new \DateTime();
        $this->notification = 0;
        $this->isSecureParameters = false;
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
