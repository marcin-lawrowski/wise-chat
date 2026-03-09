<?php

namespace Kainex\WiseChat\Services\User;

use Exception;
use Kainex\WiseChat\DAO\ActionsDAO;
use Kainex\WiseChat\Model\Action;
use Kainex\WiseChat\Model\User;

/**
 * WiseChat actions service.
 */
class ActionsService {
    /**
     * @var ActionsDAO
     */
    private $actionsDAO;

	/**
	 * @param ActionsDAO $actionsDAO
	 */
	public function __construct(ActionsDAO $actionsDAO) {
		$this->actionsDAO = $actionsDAO;
	}

	/**
	 * @return int|null
	 */
    public function getLastActionId() {
	    $lastAction = $this->actionsDAO->getLast();

	    return $lastAction !== null ? $lastAction->getId() : null;
    }

    /**
     * Publishes the action in the queue. If the user is not specified the action is public.
     * Otherwise it is directed to the specified user.
     *
     * @param string $name Name of the action
     * @param array $commandData Data of the action
     * @param integer $user Recipient of the action

     * @throws Exception
     */
    public function publishAction($name, $commandData, $userId = null) {
        $name = trim($name);
        if (strlen($name) === 0) {
            throw new Exception('Action name cannot be empty');
        }

        $action = new Action();
        $action->setCommand(array(
            'name' => $name,
            'data' => $commandData
        ));
        $action->setTime(time());
        if ($userId !== null) {
            $action->setUserId($userId);
        }
        $this->actionsDAO->save($action);
    }

    /**
     * Returns actions of the user and beginning from specified ID and (optionally) by user.
     * The result array is JSON ready. Some of the fields are hidden and command is decoded to array.
     *
     * @param integer $fromId Offset
     * @param User $user Actions directed to the specific user
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