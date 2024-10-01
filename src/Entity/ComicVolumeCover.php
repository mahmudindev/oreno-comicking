<?php

namespace App\Entity;

use App\Repository\ComicVolumeCoverRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ComicVolumeCoverRepository::class)]
#[ORM\Table(name: 'comic_volume_cover')]
#[ORM\UniqueConstraint(columns: ['volume_id', 'ulid'])]
#[ORM\UniqueConstraint(columns: ['volume_id', 'link_id'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class ComicVolumeCover
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['comic', 'comicVolume', 'comicVolumeCover'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic', 'comicVolume', 'comicVolumeCover'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'covers')]
    #[ORM\JoinColumn(name: 'volume_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?ComicVolume $volume = null;

    #[ORM\Column(type: 'ulid')]
    #[Serializer\Groups(['comic', 'comicVolume', 'comicVolumeCover'])]
    private ?Ulid $ulid = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'link_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Link $link = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Assert\NotBlank(allowNull: true), Assert\Length(min: 1, max: 64)]
    #[Serializer\Groups(['comic', 'comicVolume', 'comicVolumeCover'])]
    private ?string $hint = null;

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

    #[Serializer\Groups(['comicVolumeCover'])]
    public function getVolumeComicCode(): ?string
    {
        if ($this->volume == null) {
            return null;
        }

        return $this->volume->getComicCode();
    }

    #[Serializer\Groups(['comicVolumeCover'])]
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

    public function getLink(): ?Link
    {
        return $this->link;
    }

    #[Serializer\Groups(['comic', 'comicVolume', 'comicVolumeCover'])]
    public function getLinkWebsiteHost(): ?string
    {
        if ($this->link == null) {
            return null;
        }

        return $this->link->getWebsiteHost();
    }

    #[Serializer\Groups(['comic', 'comicVolume', 'comicVolumeCover'])]
    public function getLinkRelativeReference(): ?string
    {
        if ($this->link == null) {
            return null;
        }

        return $this->link->getRelativeReference();
    }

    public function setLink(?Link $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getHint(): ?string
    {
        return $this->hint;
    }

    public function setHint(?string $hint): static
    {
        $this->hint = $hint;

        return $this;
    }
}
