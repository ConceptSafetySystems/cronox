<?php

namespace BackSyst\SystemBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WatchProcess
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="BackSyst\SystemBundle\Entity\WatchProcessRepository")
 */
class WatchProcess
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
     * @var integer
     *
     * @ORM\Column(name="pid", type="integer")
     */
    private $pid;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Report", inversedBy="processes")
     * @ORM\JoinColumn(name="report_id", referencedColumnName="id")
     */
    private $report;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Backup", inversedBy="processes")
     * @ORM\JoinColumn(name="backup_id", referencedColumnName="id")
     */
    private $backup;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_done", type="boolean", nullable=false)
     */
    private $isDone;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_success", type="boolean", nullable=true)
     */
    private $isSuccess;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="date", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endedAt", type="date", nullable=true)
     */
    private $endedAt;

    /**
     *  Constructor for the class. Sets createdAt to Now
     *
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->isDone = true;
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
