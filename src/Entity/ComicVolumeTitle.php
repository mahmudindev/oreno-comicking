<?php

namespace App\Entity;

use App\Repository\ComicVolumeTitleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ComicVolumeTitleRepository::class)]
#[ORM\Table(name: 'comic_volume_title')]
#[ORM\UniqueConstraint(columns: ['volume_id', 'ulid'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class ComicVolumeTitle
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['comic', 'comicVolume', 'comicVolumeTitle'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic', 'comicVolume', 'comicVolumeTitle'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'titles')]
    #[ORM\JoinColumn(name: 'volume_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?ComicVolume $volume = null;

    #[ORM\Column(type: 'ulid')]
    #[Serializer\Groups(['comic', 'comicVolume', 'comicVolumeTitle'])]
    private ?Ulid $ulid = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'language_id', nullable: false)]
    #[Assert\NotNull]
    private ?Language $language = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank, Assert\Length(min: 1, max: 255)]
    #[Serializer\Groups(['comic', 'comicVolume', 'comicVolumeTitle'])]
    private ?string $content = null;

    #[ORM\Column(nullable: true)]
    #[Serializer\Groups(['comic', 'comicVolume', 'comicVolumeTitle'])]
    private ?bool $isSynonym = null;

    #[ORM\Column(nullable: true)]
    #[Serializer\Groups(['comic', 'comicVolume', 'comicVolumeTitle'])]
    private ?bool $isLatinized = null;

    #[ORM\PrePersist]
    public function onPrePersist(PrePersistEventArgs $args)
    {
        $this->setCreatedAt(new \DateTimeImmutable());
        $this->setUlid(new Ulid());
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(PreUpdateEventArgs $args)
    {
        $this->setUpdatedAt(new \DateTimeImmutable());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getVolume(): ?ComicVolume
    {
        return $this->volume;
    }

    #[Serializer\Groups(['comicVolumeTitle'])]
    public function getVolumeComicCode(): ?string
    {
        if ($this->volume == null) {
            return null;
        }

        return $this->volume->getComicCode();
    }

    #[Serializer\Groups(['comicVolumeTitle'])]
    public function getVolumeNumber(): ?float
    {
        if ($this->volume == null) {
            return null;
        }

        return $this->volume->getNumber();
    }

    public function setVolume(?ComicVolume $volume): static
    {
        $this->volume = $volume;

        return $this;
    }

    public function getUlid(): ?Ulid
    {
        return $this->ulid;
    }

    public function setUlid(Ulid $ulid): static
    {
        $this->ulid = $ulid;

        return $this;
    }

    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    #[Serializer\Groups(['comic', 'comicVolume', 'comicVolumeTitle'])]
    public function getLanguageLang(): ?string
    {
        if ($this->language == null) {
            return null;
        }

        return $this->language->getLang();
    }

    public function setLanguage(?Language $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function isSynonym(): ?bool
    {
        return $this->isSynonym;
    }

    public function setSynonym(?bool $isSynonym): static
    {
        $this->isSynonym = $isSynonym;

        return $this;
    }

    public function isLatinized(): ?bool
    {
        return $this->isLatinized;
    }

    public function setLatinized(?bool $isLatinized): static
    {
        $this->isLatinized = $isLatinized;

        return $this;
    }
}
