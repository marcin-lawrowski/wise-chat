<?php

namespace Kainex\WiseChat\Services\ClientSide;

class ClientChannel {

	private string $id;
	private string $name;
	private ?int $type;
	private bool $readOnly = false;
	private bool $own = false;
	private bool $canEdit = false;
	private bool $canRemove = false;
	private bool $online = false;
	private bool $muted = false;
	private bool $protected;
	private bool $authorized;
	private array $configuration = [];
	private string $avatar;
	private ?string $url = null;
	private ?string $textColor = null;
	private ?string $classes = null;
	private ?string $countryCode = null;
	private ?string $country = null;
	private ?string $city = null;
	private ?string $countryFlagSrc = null;
	private ?array $infoWindow = null;
	private ?string $intro = null;

	public function getId(): string {
		return $this->id;
	}

	public function setId(string $id): void {
		$this->id = $id;
	}

	public function getName(): string {
		return $this->name;
	}

	public function setName(string $name): void {
		$this->name = $name;
	}

	public function getType(): ?int {
		return $this->type;
	}

	public function setType(?int $type): void {
		$this->type = $type;
	}

	public function isReadOnly(): bool {
		return $this->readOnly;
	}

	public function setReadOnly(bool $readOnly): void {
		$this->readOnly = $readOnly;
	}

	public function getConfiguration(): array {
		return $this->configuration;
	}

	public function setConfiguration(array $configuration): void {
		$this->configuration = $configuration;
	}

	public function getAvatar(): string {
		return $this->avatar;
	}

	public function setAvatar(string $avatar): void {
		$this->avatar = $avatar;
	}

	public function isProtected(): bool {
		return $this->protected;
	}

	public function setProtected(bool $protected): void {
		$this->protected = $protected;
	}

	public function isAuthorized(): bool {
		return $this->authorized;
	}

	public function setAuthorized(bool $authorized): void {
		$this->authorized = $authorized;
	}

	public function isCanEdit(): bool {
		return $this->canEdit;
	}

	public function setCanEdit(bool $canEdit): void {
		$this->canEdit = $canEdit;
	}

	public function isCanRemove(): bool {
		return $this->canRemove;
	}

	public function setCanRemove(bool $canRemove): void {
		$this->canRemove = $canRemove;
	}

	public function getUrl(): ?string {
		return $this->url;
	}

	public function setUrl(?string $url): void {
		$this->url = $url;
	}

	public function getTextColor(): ?string {
		return $this->textColor;
	}

	public function setTextColor(?string $textColor): void {
		$this->textColor = $textColor;
	}

	public function getClasses(): ?string {
		return $this->classes;
	}

	public function setClasses(?string $classes): void {
		$this->classes = $classes;
	}

	public function getCountryCode(): ?string {
		return $this->countryCode;
	}

	public function setCountryCode(?string $countryCode): void {
		$this->countryCode = $countryCode;
	}

	public function getCountry(): ?string {
		return $this->country;
	}

	public function setCountry(?string $country): void {
		$this->country = $country;
	}

	public function getCity(): ?string {
		return $this->city;
	}

	public function setCity(?string $city): void {
		$this->city = $city;
	}

	public function getCountryFlagSrc(): ?string {
		return $this->countryFlagSrc;
	}

	public function setCountryFlagSrc(?string $countryFlagSrc): void {
		$this->countryFlagSrc = $countryFlagSrc;
	}

	public function getInfoWindow(): ?array {
		return $this->infoWindow;
	}

	public function setInfoWindow(?array $infoWindow): void {
		$this->infoWindow = $infoWindow;
	}

	public function getIntro(): ?string {
		return $this->intro;
	}

	public function setIntro(?string $intro): void {
		$this->intro = $intro;
	}

	public function isOnline(): bool {
		return $this->online;
	}

	public function setOnline(bool $online): void {
		$this->online = $online;
	}

	public function isMuted(): bool {
		return $this->muted;
	}

	public function setMuted(bool $muted): void {
		$this->muted = $muted;
	}

	public function isOwn(): bool {
		return $this->own;
	}

	public function setOwn(bool $own): void {
		$this->own = $own;
	}

}