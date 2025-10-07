<?php

namespace App\Entity;

use App\Repository\ComicAuthorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ComicAuthorRepository::class)]
#[ORM\Table(name: 'comic_author')]
#[ORM\UniqueConstraint(columns: ['comic_id', 'position_id', 'person_id'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class ComicAuthor
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['comic', 'comicAuthor'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic', 'comicAuthor'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'authors')]
    #[ORM\JoinColumn(name: 'comic_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Comic $comic = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'position_id', nullable: false)]
    #[Assert\NotNull]
    private ?ComicAuthorPosition $position = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'person_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Person $person = null;

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

    #[Serializer\Groups(['comicAuthor'])]
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

    public function getPosition(): ?ComicAuthorPosition
    {
        return $this->position;
    }

    #[Serializer\Groups(['comic', 'comicAuthor'])]
    public function getPositionCode(): ?string
    {
        if ($this->position == null) {
            return null;
        }

        return $this->position->getCode();
    }

    public function setPosition(?ComicAuthorPosition $type): static
    {
        $this->position = $type;

        return $this;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    #[Serializer\Groups(['comic', 'comicAuthor'])]
    public function getPersonCode(): ?string
    {
        if ($this->person == null) {
            return null;
        }

        return $this->person->getCode();
    }

    public function setPerson(?Person $person): static
    {
        $this->person = $person;

        return $this;
    }
}
