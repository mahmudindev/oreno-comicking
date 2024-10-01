<?php

namespace App\Entity;

use App\Repository\ComicAuthorNoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ComicAuthorNoteRepository::class)]
#[ORM\Table(name: 'comic_author_note')]
#[ORM\UniqueConstraint(columns: ['author_id', 'ulid'])]
#[ORM\UniqueConstraint(columns: ['author_id', 'language_id'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class ComicAuthorNote
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['comic', 'comicAuthor', 'comicAuthorNote'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic', 'comicAuthor', 'comicAuthorNote'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[ORM\JoinColumn(name: 'author_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?ComicAuthor $author = null;

    #[ORM\Column(type: 'ulid')]
    #[Serializer\Groups(['comic', 'comicAuthor', 'comicAuthorNote'])]
    private ?Ulid $ulid = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'language_id', nullable: false)]
    #[Assert\NotNull]
    private ?Language $language = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank, Assert\Length(min: 1, max: 255)]
    #[Serializer\Groups(['comic', 'comicAuthor', 'comicAuthorNote'])]
    private ?string $content = null;

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

    public function getAuthor(): ?ComicAuthor
    {
        return $this->author;
    }

    #[Serializer\Groups(['comicAuthorNote'])]
    public function getAuthorComicCode(): ?string
    {
        if ($this->author == null) {
            return null;
        }

        return $this->author->getComicCode();
    }

    #[Serializer\Groups(['comicAuthorNote'])]
    public function getAuthorTypeCode(): ?string
    {
        if ($this->author == null) {
            return null;
        }

        return $this->author->getTypeCode();
    }

    #[Serializer\Groups(['comicAuthorNote'])]
    public function getAuthorPersonCode(): ?string
    {
        if ($this->author == null) {
            return null;
        }

        return $this->author->getPersonCode();
    }

    public function setAuthor(?ComicAuthor $author): static
    {
        $this->author = $author;

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

    #[Serializer\Groups(['comic', 'comicAuthor', 'comicAuthorNote'])]
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
}
