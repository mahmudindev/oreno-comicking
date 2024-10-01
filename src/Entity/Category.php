<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'category')]
#[ORM\UniqueConstraint(columns: ['type_id', 'code'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['category'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['category'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'type_id', nullable: false)]
    #[Assert\NotNull]
    private ?CategoryKind $type = null;

    #[ORM\Column(length: 32)]
    #[Assert\NotBlank, Assert\Length(min: 1, max: 32)]
    #[Serializer\Groups(['category'])]
    private ?string $code = null;

    #[ORM\Column(length: 32)]
    #[Assert\NotBlank, Assert\Length(min: 1, max: 32)]
    #[Serializer\Groups(['category'])]
    private ?string $name = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'link_id', onDelete: 'SET NULL')]
    private ?Link $link = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', onDelete: 'CASCADE')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', fetch: 'EXTRA_LAZY')]
    private Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
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

    public function getType(): ?CategoryKind
    {
        return $this->type;
    }

    #[Serializer\Groups(['category'])]
    public function getTypeCode(): ?string
    {
        if ($this->type == null) {
            return null;
        }

        return $this->type->getCode();
    }

    public function setType(?CategoryKind $type): static
    {
        $this->type = $type;

        foreach ($this->children as $val) {
            $val->setType($type);
        }

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLink(): ?Link
    {
        return $this->link;
    }

    #[Serializer\Groups(['category'])]
    public function getLinkWebsiteHost(): ?string
    {
        if ($this->link == null) {
            return null;
        }

        return $this->link->getWebsiteHost();
    }

    #[Serializer\Groups(['category'])]
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    #[Serializer\Groups(['category'])]
    public function getParentCode(): ?string
    {
        if ($this->parent == null) {
            return null;
        }

        return $this->parent->getCode();
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    #[Serializer\Groups(['category'])]
    public function getChildCount(): ?int
    {
        return $this->children->count();
    }

    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }
}
