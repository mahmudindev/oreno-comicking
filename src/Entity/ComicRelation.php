<?php

namespace App\Entity;

use App\Repository\ComicRelationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ComicRelationRepository::class)]
#[ORM\Table(name: 'comic_relation')]
#[ORM\UniqueConstraint(columns: ['parent_id', 'type_id', 'child_id'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class ComicRelation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['comic', 'comicRelation'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic', 'comicChapter'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'relations')]
    #[ORM\JoinColumn(name: 'parent_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Comic $parent = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'type_id', nullable: false)]
    #[Assert\NotNull]
    private ?ComicRelationKind $type = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'child_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Comic $child = null;

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

    public function getParent(): ?Comic
    {
        return $this->parent;
    }

    #[Serializer\Groups(['comicRelation'])]
    public function getParentCode(): ?string
    {
        if ($this->parent == null) {
            return null;
        }

        return $this->parent->getCode();
    }

    public function setParent(?Comic $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getType(): ?ComicRelationKind
    {
        return $this->type;
    }

    #[Serializer\Groups(['comic', 'comicRelation'])]
    public function getTypeCode(): ?string
    {
        if ($this->type == null) {
            return null;
        }

        return $this->type->getCode();
    }

    public function setType(?ComicRelationKind $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getChild(): ?Comic
    {
        return $this->child;
    }

    #[Serializer\Groups(['comic', 'comicRelation'])]
    public function getChildCode(): ?string
    {
        if ($this->child == null) {
            return null;
        }

        return $this->child->getCode();
    }

    public function setChild(?Comic $child): static
    {
        $this->child = $child;

        return $this;
    }
}
