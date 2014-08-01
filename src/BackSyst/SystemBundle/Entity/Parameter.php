<?php

namespace BackSyst\SystemBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Parameter
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="BackSyst\SystemBundle\Entity\ParameterRepository")
 */
class Parameter
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
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="parameter", type="text")
     */
    private $parameter;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text")
     */
    private $value;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Script", inversedBy="parameters")
     * @ORM\JoinColumn(name="script_id", referencedColumnName="id")
     */
    private $script;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean")
     */
    private $isDefault;


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
