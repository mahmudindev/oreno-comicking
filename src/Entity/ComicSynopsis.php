<?php

namespace App\Entity;

use App\Repository\ComicSynopsisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ComicSynopsisRepository::class)]
#[ORM\Table(name: 'comic_synopsis')]
#[ORM\UniqueConstraint(columns: ['comic_id', 'ulid'])]
#[ORM\UniqueConstraint(columns: ['comic_id', 'language_id', 'source'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class ComicSynopsis
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['comic', 'comicSynopsis'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic', 'comicSynopsis'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'synopses')]
    #[ORM\JoinColumn(name: 'comic_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Comic $comic = null;

    #[ORM\Column(type: 'ulid')]
    #[Serializer\Groups(['comic', 'comicSynopsis'])]
    private ?Ulid $ulid = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'language_id', nullable: false)]
    #[Assert\NotNull]
    private ?Language $language = null;

    #[ORM\Column(length: 2040)]
    #[Assert\NotBlank, Assert\Length(min: 1, max: 2040)]
    #[Serializer\Groups(['comic', 'comicSynopsis'])]
    private ?string $content = null;

    #[ORM\Column(length: 32, options: ['default' => ''])]
    #[Assert\Length(min: 1, max: 32)]
    #[Serializer\Groups(['comic', 'comicSynopsis'])]
    private ?string $source = null;

    #[ORM\PrePersist]
    public function onPrePersist(PrePersistEventArgs $args)
    {
        $this->setCreatedAt(new \DateTimeImmutable());
        $this->setUlid(new Ulid());

        if ($this->source == null) $this->setSource('');
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(PreUpdateEventArgs $args)
    {
        $this->setUpdatedAt(new \DateTimeImmutable());

        if ($this->source == null) $this->setSource('');
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

    #[Serializer\Groups(['comicSynopsis'])]
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

    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    #[Serializer\Groups(['comic', 'comicSynopsis'])]
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

    public function getSource(): ?string
    {
        if ($this->source == '') {
            return null;
        }

        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }
}
