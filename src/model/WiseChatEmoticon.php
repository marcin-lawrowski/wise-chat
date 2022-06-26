<?php

/**
 * WiseChat emoticon model.
 */
class WiseChatEmoticon {
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $attachmentId;

    /**
     * @var integer
     */
    private $alias;

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
     * @return integer
     */
    public function getAttachmentId() {
        return $this->attachmentId;
    }

    /**
     * @param integer $attachmentId
     */
    public function setAttachmentId($attachmentId) {
        $this->attachmentId = $attachmentId;
    }

    /**
     * @return integer
     */
    public function getAlias() {
        return $this->alias;
    }

    /**
     * @param integer $alias
     */
    public function setAlias($alias) {
        $this->alias = $alias;
    }
}