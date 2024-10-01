<?php

namespace App\Entity;

use App\Repository\ComicChapterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ComicChapterRepository::class)]
#[ORM\Table(name: 'comic_chapter')]
#[ORM\UniqueConstraint(columns: ['comic_id', 'number', 'version'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class ComicChapter
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['comic', 'comicChapter'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic', 'comicChapter'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'chapters')]
    #[ORM\JoinColumn(name: 'comic_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Comic $comic = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Serializer\Groups(['comic', 'comicChapter'])]
    private ?string $number = null;

    #[ORM\Column(length: 64, options: ['default' => ''])]
    #[Assert\Length(min: 1, max: 64)]
    #[Serializer\Groups(['comic', 'comicChapter'])]
    private ?string $version = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic', 'comicChapter'])]
    private ?\DateTimeImmutable $releasedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'thumbnail_link_id')]
    private ?Link $thumbnailLink = null;

    #[ORM\ManyToOne(inversedBy: 'chapters')]
    #[ORM\JoinColumn(name: 'volume_id')]
    private ?ComicVolume $volume = null;

    /**
     * @var Collection<int, ComicChapterTitle>
     */
    #[ORM\OneToMany(targetEntity: ComicChapterTitle::class, mappedBy: 'chapter', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $titles;

    public function __construct()
    {
        $this->titles = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(PrePersistEventArgs $args)
    {
        $this->setCreatedAt(new \DateTimeImmutable());

        if ($this->version == null) $this->setVersion('');
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(PreUpdateEventArgs $args)
    {
        $this->setUpdatedAt(new \DateTimeImmutable());

        if ($this->version == null) $this->setVersion('');
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

    #[Serializer\Groups(['comicChapter'])]
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

    public function getNumber(): ?float
    {
        return $this->number;
    }

    public function setNumber(float $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getVersion(): ?string
    {
        if ($this->version == '') {
            return null;
        }

        return $this->version;
    }

    public function setVersion(?string $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getReleasedAt(): ?\DateTimeImmutable
    {
        return $this->releasedAt;
    }

    public function setReleasedAt(?\DateTimeImmutable $releasedAt): static
    {
        $this->releasedAt = $releasedAt;

        return $this;
    }

    public function getThumbnailLink(): ?Link
    {
        return $this->thumbnailLink;
    }

    #[Serializer\Groups(['comic', 'comicChapter'])]
    public function getThumbnailLinkWebsiteHost(): ?string
    {
        if ($this->thumbnailLink == null) {
            return null;
        }

        return $this->thumbnailLink->getWebsiteHost();
    }

    #[Serializer\Groups(['comic', 'comicChapter'])]
    public function getThumbnailLinkRelativeReference(): ?string
    {
        if ($this->thumbnailLink == null) {
            return null;
        }

        return $this->thumbnailLink->getRelativeReference();
    }

    public function setThumbnailLink(?Link $thumbnailLink): static
    {
        $this->thumbnailLink = $thumbnailLink;

        return $this;
    }

    public function getVolume(): ?ComicVolume
    {
        return $this->volume;
    }

    #[Serializer\Groups(['comic', 'comicChapter'])]
    public function getVolumeNumber(): ?string
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

    /**
     * @return Collection<int, ComicChapterTitle>
     */
    public function getTitles(): Collection
    {
        return $this->titles;
    }

    #[Serializer\Groups(['comic', 'comicChapter'])]
    public function getTitleCount(): ?int
    {
        return $this->titles->count();
    }

    public function addTitle(ComicChapterTitle $title): static
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
            $title->setChapter($this);
        }

        return $this;
    }

    public function removeTitle(ComicChapterTitle $title): static
    {
        if ($this->titles->removeElement($title)) {
            // set the owning side to null (unless already changed)
            if ($title->getChapter() === $this) {
                $title->setChapter(null);
            }
        }

        return $this;
    }
}
