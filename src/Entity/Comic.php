<?php

namespace App\Entity;

use App\Repository\ComicRepository;
use App\Util\StringUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ComicRepository::class)]
#[ORM\Table(name: 'comic')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class Comic
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['comic'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 12, unique: true, options: ['collation' => 'utf8mb4_bin'])]
    #[Assert\NotBlank(allowNull: true), Assert\Length(12)]
    #[Serializer\Groups(['comic'])]
    private ?string $code = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic'])]
    private ?\DateTimeImmutable $publishedFrom = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic'])]
    private ?\DateTimeImmutable $publishedTo = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    #[Serializer\Groups(['comic'])]
    private ?int $totalChapter = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    #[Serializer\Groups(['comic'])]
    private ?int $totalVolume = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: -1, max: 1)]
    #[Serializer\Groups(['comic'])]
    private ?int $nsfw = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: -1, max: 1)]
    #[Serializer\Groups(['comic'])]
    private ?int $nsfl = null;

    /**
     * @var Collection<int, ComicTitle>
     */
    #[ORM\OneToMany(targetEntity: ComicTitle::class, mappedBy: 'comic', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $titles;

    /**
     * @var Collection<int, ComicCover>
     */
    #[ORM\OneToMany(targetEntity: ComicCover::class, mappedBy: 'comic', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $covers;

    /**
     * @var Collection<int, ComicSynopsis>
     */
    #[ORM\OneToMany(targetEntity: ComicSynopsis::class, mappedBy: 'comic', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $synopses;

    /**
     * @var Collection<int, ComicCharacter>
     */
    #[ORM\OneToMany(targetEntity: ComicCharacter::class, mappedBy: 'comic', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $characters;

    /**
     * @var Collection<int, ComicAuthor>
     */
    #[ORM\OneToMany(targetEntity: ComicAuthor::class, mappedBy: 'comic', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $authors;

    /**
     * @var Collection<int, ComicSerialization>
     */
    #[ORM\OneToMany(targetEntity: ComicSerialization::class, mappedBy: 'comic', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $serializations;

    /**
     * @var Collection<int, ComicExternal>
     */
    #[ORM\OneToMany(targetEntity: ComicExternal::class, mappedBy: 'comic', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $externals;

    /**
     * @var Collection<int, ComicChapter>
     */
    #[ORM\OneToMany(targetEntity: ComicChapter::class, mappedBy: 'comic', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $chapters;

    /**
     * @var Collection<int, ComicVolume>
     */
    #[ORM\OneToMany(targetEntity: ComicVolume::class, mappedBy: 'comic', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $volumes;

    /**
     * @var Collection<int, ComicCategory>
     */
    #[ORM\OneToMany(targetEntity: ComicCategory::class, mappedBy: 'comic', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $categories;

    /**
     * @var Collection<int, ComicTag>
     */
    #[ORM\OneToMany(targetEntity: ComicTag::class, mappedBy: 'comic', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $tags;

    /**
     * @var Collection<int, ComicRelation>
     */
    #[ORM\OneToMany(targetEntity: ComicRelation::class, mappedBy: 'parent', fetch: 'EXTRA_LAZY')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $relations;

    public function __construct()
    {
        $this->titles = new ArrayCollection();
        $this->covers = new ArrayCollection();
        $this->synopses = new ArrayCollection();
        $this->characters = new ArrayCollection();
        $this->authors = new ArrayCollection();
        $this->serializations = new ArrayCollection();
        $this->externals = new ArrayCollection();
        $this->chapters = new ArrayCollection();
        $this->volumes = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->relations = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(PrePersistEventArgs $args)
    {
        $this->setCreatedAt(new \DateTimeImmutable());
        if ($this->getCode() == null) {
            $this->setCode(StringUtil::randomString(12));
        }
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getPublishedFrom(): ?\DateTimeImmutable
    {
        return $this->publishedFrom;
    }

    public function setPublishedFrom(?\DateTimeImmutable $publishedFrom): static
    {
        $this->publishedFrom = $publishedFrom;

        return $this;
    }

    public function getPublishedTo(): ?\DateTimeImmutable
    {
        return $this->publishedTo;
    }

    public function setPublishedTo(?\DateTimeImmutable $publishedTo): static
    {
        $this->publishedTo = $publishedTo;

        return $this;
    }

    public function getTotalChapter(): ?int
    {
        return $this->totalChapter;
    }

    public function setTotalChapter(?int $totalChapter): static
    {
        $this->totalChapter = $totalChapter;

        return $this;
    }

    public function getTotalVolume(): ?int
    {
        return $this->totalVolume;
    }

    public function setTotalVolume(?int $totalVolume): static
    {
        $this->totalVolume = $totalVolume;

        return $this;
    }

    public function getNsfw(): ?int
    {
        return $this->nsfw;
    }

    public function setNsfw(?int $nsfw): static
    {
        $this->nsfw = $nsfw;

        return $this;
    }

    public function getNsfl(): ?int
    {
        return $this->nsfl;
    }

    public function setNsfl(?int $nsfl): static
    {
        $this->nsfl = $nsfl;

        return $this;
    }

    /**
     * @return Collection<int, ComicTitle>
     */
    public function getTitles(): Collection
    {
        return $this->titles;
    }

    #[Serializer\Groups(['comic'])]
    public function getTitleCount(): ?int
    {
        return $this->titles->count();
    }

    public function addTitle(ComicTitle $title): static
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
            $title->setComic($this);
        }

        return $this;
    }

    public function removeTitle(ComicTitle $title): static
    {
        if ($this->titles->removeElement($title)) {
            // set the owning side to null (unless already changed)
            if ($title->getComic() === $this) {
                $title->setComic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ComicCover>
     */
    public function getCovers(): Collection
    {
        return $this->covers;
    }

    #[Serializer\Groups(['comic'])]
    public function getCoverCount(): ?int
    {
        return $this->covers->count();
    }

    public function addCover(ComicCover $cover): static
    {
        if (!$this->covers->contains($cover)) {
            $this->covers->add($cover);
            $cover->setComic($this);
        }

        return $this;
    }

    public function removeCover(ComicCover $cover): static
    {
        if ($this->covers->removeElement($cover)) {
            // set the owning side to null (unless already changed)
            if ($cover->getComic() === $this) {
                $cover->setComic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ComicSynopsis>
     */
    public function getSynopses(): Collection
    {
        return $this->synopses;
    }

    #[Serializer\Groups(['comic'])]
    public function getSynopsisCount(): ?int
    {
        return $this->synopses->count();
    }

    public function addSynopsis(ComicSynopsis $synopsis): static
    {
        if (!$this->synopses->contains($synopsis)) {
            $this->synopses->add($synopsis);
            $synopsis->setComic($this);
        }

        return $this;
    }

    public function removeSynopsis(ComicSynopsis $synopsis): static
    {
        if ($this->synopses->removeElement($synopsis)) {
            // set the owning side to null (unless already changed)
            if ($synopsis->getComic() === $this) {
                $synopsis->setComic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ComicCharacter>
     */
    public function getCharacters(): Collection
    {
        return $this->characters;
    }

    #[Serializer\Groups(['comic'])]
    public function getCharacterCount(): ?int
    {
        return $this->characters->count();
    }

    public function addCharacter(ComicCharacter $character): static
    {
        if (!$this->characters->contains($character)) {
            $this->characters->add($character);
            $character->setComic($this);
        }

        return $this;
    }

    public function removeCharacter(ComicCharacter $character): static
    {
        if ($this->characters->removeElement($character)) {
            // set the owning side to null (unless already changed)
            if ($character->getComic() === $this) {
                $character->setComic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ComicAuthor>
     */
    public function getAuthors(): Collection
    {
        return $this->authors;
    }

    #[Serializer\Groups(['comic'])]
    public function getAuthorCount(): ?int
    {
        return $this->authors->count();
    }

    public function addAuthor(ComicAuthor $author): static
    {
        if (!$this->authors->contains($author)) {
            $this->authors->add($author);
            $author->setComic($this);
        }

        return $this;
    }

    public function removeAuthor(ComicAuthor $author): static
    {
        if ($this->authors->removeElement($author)) {
            // set the owning side to null (unless already changed)
            if ($author->getComic() === $this) {
                $author->setComic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ComicSerialization>
     */
    public function getSerializations(): Collection
    {
        return $this->serializations;
    }

    #[Serializer\Groups(['comic'])]
    public function getSerializationCount(): ?int
    {
        return $this->serializations->count();
    }

    public function addSerialization(ComicSerialization $serialization): static
    {
        if (!$this->serializations->contains($serialization)) {
            $this->serializations->add($serialization);
            $serialization->setComic($this);
        }

        return $this;
    }

    public function removeSerialization(ComicSerialization $serialization): static
    {
        if ($this->serializations->removeElement($serialization)) {
            // set the owning side to null (unless already changed)
            if ($serialization->getComic() === $this) {
                $serialization->setComic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ComicExternal>
     */
    public function getExternals(): Collection
    {
        return $this->externals;
    }

    #[Serializer\Groups(['comic'])]
    public function getExternalCount(): ?int
    {
        return $this->externals->count();
    }

    public function addExternal(ComicExternal $external): static
    {
        if (!$this->externals->contains($external)) {
            $this->externals->add($external);
            $external->setComic($this);
        }

        return $this;
    }

    public function removeExternal(ComicExternal $external): static
    {
        if ($this->externals->removeElement($external)) {
            // set the owning side to null (unless already changed)
            if ($external->getComic() === $this) {
                $external->setComic(null);
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

    #[Serializer\Groups(['comic'])]
    public function getChapterCount(): ?int
    {
        return $this->chapters->count();
    }

    public function addChapter(ComicChapter $chapter): static
    {
        if (!$this->chapters->contains($chapter)) {
            $this->chapters->add($chapter);
            $chapter->setComic($this);
        }

        return $this;
    }

    public function removeChapter(ComicChapter $chapter): static
    {
        if ($this->chapters->removeElement($chapter)) {
            // set the owning side to null (unless already changed)
            if ($chapter->getComic() === $this) {
                $chapter->setComic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ComicVolume>
     */
    public function getVolumes(): Collection
    {
        return $this->volumes;
    }

    #[Serializer\Groups(['comic'])]
    public function getVolumeCount(): ?int
    {
        return $this->volumes->count();
    }

    public function addVolume(ComicVolume $volume): static
    {
        if (!$this->volumes->contains($volume)) {
            $this->volumes->add($volume);
            $volume->setComic($this);
        }

        return $this;
    }

    public function removeVolume(ComicVolume $volume): static
    {
        if ($this->volumes->removeElement($volume)) {
            // set the owning side to null (unless already changed)
            if ($volume->getComic() === $this) {
                $volume->setComic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ComicCategory>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    #[Serializer\Groups(['comic'])]
    public function getCategoryCount(): ?int
    {
        return $this->categories->count();
    }

    public function addCategory(ComicCategory $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->setComic($this);
        }

        return $this;
    }

    public function removeCategory(ComicCategory $category): static
    {
        if ($this->categories->removeElement($category)) {
            // set the owning side to null (unless already changed)
            if ($category->getComic() === $this) {
                $category->setComic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ComicTag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    #[Serializer\Groups(['comic'])]
    public function getTagCount(): ?int
    {
        return $this->tags->count();
    }

    public function addTag(ComicTag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->setComic($this);
        }

        return $this;
    }

    public function removeTag(ComicTag $tag): static
    {
        if ($this->tags->removeElement($tag)) {
            // set the owning side to null (unless already changed)
            if ($tag->getComic() === $this) {
                $tag->setComic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ComicRelation>
     */
    public function getRelations(): Collection
    {
        return $this->relations;
    }

    #[Serializer\Groups(['comic'])]
    public function getRelationCount(): ?int
    {
        return $this->relations->count();
    }

    public function addRelation(ComicRelation $relation): static
    {
        if (!$this->relations->contains($relation)) {
            $this->relations->add($relation);
            $relation->setParent($this);
        }

        return $this;
    }

    public function removeRelation(ComicRelation $relation): static
    {
        if ($this->relations->removeElement($relation)) {
            // set the owning side to null (unless already changed)
            if ($relation->getParent() === $this) {
                $relation->setParent(null);
            }
        }

        return $this;
    }
}
