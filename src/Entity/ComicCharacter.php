<?php

namespace App\Entity;

use App\Repository\ComicCharacterRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ComicCharacterRepository::class)]
#[ORM\Table(name: 'comic_character')]
#[ORM\UniqueConstraint(columns: ['comic_id', 'character_id'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class ComicCharacter
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['comic', 'comicCharacter'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic', 'comicCharacter'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'characters')]
    #[ORM\JoinColumn(name: 'comic_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Comic $comic = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'character_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Character $character = null;

    #[ORM\Column(nullable: true)]
    #[Serializer\Groups(['comic', 'comicCharacter'])]
    private ?bool $isMain = null;

    #[ORM\PrePersist]
    public function onPrePersist(PrePersistEventArgs $args)
    {
        $this->setCreatedAt(new \DateTimeImmutable());
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

    #[Serializer\Groups(['comicCharacter'])]
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

    public function getCharacter(): ?Character
    {
        return $this->character;
    }

    #[Serializer\Groups(['comic', 'comicCharacter'])]
    public function getCharacterCode(): ?string
    {
        if ($this->character == null) {
            return null;
        }

        return $this->character->getCode();
    }

    public function setCharacter(?Character $character): static
    {
        $this->character = $character;

        return $this;
    }

    public function isMain(): ?bool
    {
        return $this->isMain;
    }

    public function setMain(?bool $isMain): static
    {
        $this->isMain = $isMain;

        return $this;
    }
}
