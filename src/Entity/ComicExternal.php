<?php

namespace App\Entity;

use App\Repository\ComicExternalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ComicExternalRepository::class)]
#[ORM\Table(name: 'comic_external')]
#[ORM\UniqueConstraint(columns: ['comic_id', 'ulid'])]
#[ORM\UniqueConstraint(columns: ['comic_id', 'link_id'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class ComicExternal
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['comic', 'comicExternal'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic', 'comicExternal'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'externals')]
    #[ORM\JoinColumn(name: 'comic_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Comic $comic = null;

    #[ORM\Column(type: 'ulid')]
    #[Serializer\Groups(['comic', 'comicExternal'])]
    private ?Ulid $ulid = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'link_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Link $link = null;

    #[ORM\Column(nullable: true)]
    #[Serializer\Groups(['comic', 'comicExternal'])]
    private ?bool $isOfficial = null;

    #[ORM\Column(nullable: true)]
    #[Serializer\Groups(['comic', 'comicExternal'])]
    private ?bool $isCommunity = null;

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

    public function getComic(): ?Comic
    {
        return $this->comic;
    }

    #[Serializer\Groups(['comicExternal'])]
    public function getComicCode(): ?string
    {
        if ($this->comic == null) {
            return null;
        }

        return $this->comic->getCode();
    }

    public function setComic(?Comic $comic): static
    {
        $this->comic = $comic;

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

    #[Serializer\Groups(['comic', 'comicExternal'])]
    public function getLinkWebsiteHost(): ?string
    {
        if ($this->link == null) {
            return null;
        }

        return $this->link->getWebsiteHost();
    }

    #[Serializer\Groups(['comic', 'comicExternal'])]
    public function getLinkWebsiteName(): ?string
    {
        if ($this->link == null) {
            return null;
        }

        return $this->link->getWebsiteName();
    }

    #[Serializer\Groups(['comic', 'comicExternal'])]
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

    public function isOfficial(): ?bool
    {
        return $this->isOfficial;
    }

    public function setOfficial(?bool $isOfficial): static
    {
        $this->isOfficial = $isOfficial;

        return $this;
    }

    public function isCommunity(): ?bool
    {
        return $this->isCommunity;
    }

    public function setCommunity(?bool $isCommunity): static
    {
        $this->isCommunity = $isCommunity;

        return $this;
    }
}
