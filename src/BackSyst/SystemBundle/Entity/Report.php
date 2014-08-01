<?php

namespace BackSyst\SystemBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Report
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="BackSyst\SystemBundle\Entity\ReportRepository")
 */
class Report
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
     * @ORM\Column(name="machine__orig_id", type="string", length=255)
     */
    private $machineOriginalId;

    /**
     * @var string
     *
     * @ORM\Column(name="machine_name", type="string", length=255)
     */
    private $machineName;

    /**
     * @var string
     *
     * @ORM\Column(name="machine_user", type="string", length=255)
     */
    private $machineUser;

    /**
     * @var string
     *
     * @ORM\Column(name="machine_host", type="string", length=255)
     */
    private $machineHost;

    /**
     * @var string
     *
     * @ORM\Column(name="machine_description", type="text", nullable=true)
     */
    private $machineDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="backup_orig_id", type="string", length=255)
     */
    private $backupOriginalId;

    /**
     * @var string
     *
     * @ORM\Column(name="backup_name", type="string", length=255, nullable=true)
     */
    private $backupName;

    /**
     * @var string
     *
     * @ORM\Column(name="script", type="text", nullable=true)
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
     * @ORM\Column(name="path_script_file", type="text", nullable=true)
     */
    private $pathScriptFile;    

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="started_at", type="datetime")
     */
    private $startedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ended_at", type="datetime", nullable=true)
     */
    private $endedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=32, nullable=true)
     */
    private $status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_secure_parameter", type="boolean", nullable=true)
     */
    private $isSecureParameters;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_success", type="boolean", nullable=true)
     */
    private $isSuccess;

    /**
     * @var string
     *
     * @ORM\Column(name="output", type="text", nullable=true)
     */
    private $output;

    /**
     * @var string
     *
     * @ORM\Column(name="details", type="text", nullable=true)
     */
    private $details;

    /**
     * @var bigint
     *
     * @ORM\Column(name="size", type="bigint", nullable=true)
     */
    private $size;

    /**
     * @var string
     *
     * @ORM\Column(name="destination", type="string", length=255, nullable=true)
     */
    private $destination;

    /**
     * @var processes[]
     *
     * $ORM\OneToMany(targetEntity="WatchProcess", mappedBy="report", indexBy="id")
     */
    private $processes;

    /**
     * @var integer
     *
     * @ORM\Column(name="notification", type="integer", nullable=true)
     */
    private $notification;

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
        $this->notification = 0;
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
