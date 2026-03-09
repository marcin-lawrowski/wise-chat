<?php

namespace Kainex\WiseChat\Model\Channel;

/**
 * WiseChat channel model.
 */
class Channel {

	const TYPE_PUBLIC = 1;
	const TYPE_PRIVATE = 2;
	const TYPE_DIRECT = 3;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $password;

    /** @var integer|null */
    private $type;

	/**
	 * @var array
	 */
	private $configuration = [];

	public function __toString() {
		return '#'.$this->id.' '.$this->name;
	}

	/**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param integer $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password) {
        $this->password = $password;
    }

	/**
	 * @return int|null
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param int|null $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	public function getConfiguration() {
		return $this->configuration;
	}

	public function setConfiguration($configuration) {
		$this->configuration = $configuration;
	}

}