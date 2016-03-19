<?php

/**
 * WiseChat actions service.
 */
class WiseChatActions {
    /**
     * @var WiseChatActionsDAO
     */
    private $actionsDAO;

    /**
     * WiseChatActions constructor.
     */
    public function __construct() {
        $this->actionsDAO = WiseChatContainer::get('dao/WiseChatActionsDAO');
    }

    /**
     * Publishes the action in the queue. If the user is not specified the action is public.
     * Otherwise it is directed to the specified user.
     *
     * @param string $name Name of the action
     * @param array $commandData Data of the action
     * @param WiseChatUser $user Recipient of the action

     * @throws Exception
     */
    public function publishAction($name, $commandData, $user = null) {
        $name = trim($name);
        if (strlen($name) === 0) {
            throw new Exception('Action name cannot be empty');
        }

        $action = new WiseChatAction();
        $action->setCommand(array(
            'name' => $name,
            'data' => $commandData
        ));
        $action->setTime(time());
        if ($user !== null) {
            $action->setUserId($user->getId());
        }
        $this->actionsDAO->save($action);
    }

    /**
     * Returns actions of the user and beginning from specified ID and (optionally) by user.
     * The result array is JSON ready. Some of the fields are hidden and command is decoded to array.
     *
     * @param integer $fromId Offset
     * @param WiseChatUser $user Actions directed to the specific user
     *
     * @return array
     */
    public function getJSONReadyActions($fromId, $user) {
        $actions = $this->actionsDAO->getBeginningFromIdAndByUser($fromId, $user !== null ? $user->getId() : null);
        $actionsCommands = array();
        foreach ($actions as $action) {
            $actionsCommands[] = array(
                'id' => $action->getId(),
                'command' => $action->getCommand()
            );
        }

        return $actionsCommands;
    }
}