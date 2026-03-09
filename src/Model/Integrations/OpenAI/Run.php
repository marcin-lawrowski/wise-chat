<?php

namespace Kainex\WiseChat\Model\Integrations\OpenAI;

/**
 * Open AI current jobs
 */
class Run {

    private ?int $id = null;
    private string $runId;
    private int $userId;
    private int $botUserId;
    private \DateTime $created;
    private string $threadId;
    private string $status;
    private int $messageId;

	public function getId(): ?int {
		return $this->id;
	}

	public function setId(?int $id): void {
		$this->id = $id;
	}

	public function getRunId(): string {
		return $this->runId;
	}

	public function setRunId(string $runId): void {
		$this->runId = $runId;
	}

	public function getUserId(): int {
		return $this->userId;
	}

	public function setUserId(int $userId): void {
		$this->userId = $userId;
	}

	public function getBotUserId(): int {
		return $this->botUserId;
	}

	public function setBotUserId(int $botUserId): void {
		$this->botUserId = $botUserId;
	}

	public function getCreated(): \DateTime {
		return $this->created;
	}

	public function setCreated(\DateTime $created): void {
		$this->created = $created;
	}

	public function getThreadId(): string {
		return $this->threadId;
	}

	public function setThreadId(string $threadId): void {
		$this->threadId = $threadId;
	}

	public function getStatus(): string {
		return $this->status;
	}

	public function setStatus(string $status): void {
		$this->status = $status;
	}

	public function getMessageId(): int {
		return $this->messageId;
	}

	public function setMessageId(int $messageId): void {
		$this->messageId = $messageId;
	}

}