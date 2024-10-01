<?php

namespace App\Entity;

use App\Repository\ComicVolumeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ComicVolumeRepository::class)]
#[ORM\Table(name: 'comic_volume')]
#[ORM\UniqueConstraint(columns: ['comic_id', 'number'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class ComicVolume
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['comic', 'comicVolume'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic', 'comicVolume'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'volumes')]
    #[ORM\JoinColumn(name: 'comic_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Comic $comic = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Serializer\Groups(['comic', 'comicVolume'])]
    private ?string $number = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic', 'comicVolume'])]
    private ?\DateTimeImmutable $releasedAt = null;

    /**
     * @var Collection<int, ComicVolumeTitle>
     */
    #[ORM\OneToMany(targetEntity: ComicVolumeTitle::class, mappedBy: 'volume', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $titles;

    /**
     * @var Collection<int, ComicVolumeCover>
     */
    #[ORM\OneToMany(targetEntity: ComicVolumeCover::class, mappedBy: 'volume', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $covers;

    /**
     * @var Collection<int, ComicChapter>
     */
    #[ORM\OneToMany(targetEntity: ComicChapter::class, mappedBy: 'volume', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $chapters;

    public function __construct()
    {
        $this->titles = new ArrayCollection();
        $this->covers = new ArrayCollection();
        $this->chapters = new ArrayCollection();
    }

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

    #[Serializer\Groups(['comicVolume'])]
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

    public function getReleasedAt(): ?\DateTimeImmutable
    {
        return $this->releasedAt;
    }

    public function setReleasedAt(\DateTimeImmutable $releasedAt): static
    {
        $this->releasedAt = $releasedAt;

        return $this;
    }

    /**
     * @return Collection<int, ComicVolumeTitle>
     */
    public function getTitles(): Collection
    {
        return $this->titles;
    }

    #[Serializer\Groups(['comic', 'comicVolume'])]
    public function getTitleCount(): ?int
    {
        return $this->titles->count();
    }

    public function addTitle(ComicVolumeTitle $title): static
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
            $title->setVolume($this);
        }

        return $this;
    }

    public function removeTitle(ComicVolumeTitle $title): static
    {
        if ($this->titles->removeElement($title)) {
            // set the owning side to null (unless already changed)
            if ($title->getVolume() === $this) {
                $title->setVolume(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ComicVolumeCover>
     */
    public function getCovers(): Collection
    {
        return $this->covers;
    }

    #[Serializer\Groups(['comic', 'comicVolume'])]
    public function getCoverCount(): ?int
    {
        return $this->covers->count();
    }

    public function addCover(ComicVolumeCover $cover): static
    {
        if (!$this->covers->contains($cover)) {
            $this->covers->add($cover);
            $cover->setVolume($this);
        }

        return $this;
    }

    public function removeCover(ComicVolumeCover $cover): static
    {
        if ($this->covers->removeElement($cover)) {
            // set the owning side to null (unless already changed)
            if ($cover->getVolume() === $this) {
                $cover->setVolume(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ComicChapter>
     */
    public function getChapters(): Collection
    {
        return $this->chapters;
    }

    #[Serializer\Groups(['comic', 'comicVolume'])]
    public function getChapterCount(): ?int
    {
        return $this->chapters->count();
    }

    public function addChapter(ComicChapter $chapter): static
    {
        if (!$this->chapters->contains($chapter)) {
            $this->chapters->add($chapter);
            $chapter->setVolume($this);
        }

        return $this;
    }

    public function removeChapter(ComicChapter $chapter): static
    {
        if ($this->chapters->removeElement($chapter)) {
            // set the owning side to null (unless already changed)
            if ($chapter->getVolume() === $this) {
                $chapter->setVolume(null);
            }
        }

        return $this;
    }
}
