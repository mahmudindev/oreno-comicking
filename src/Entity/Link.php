<?php

namespace App\Entity;

use App\Repository\LinkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LinkRepository::class)]
#[ORM\Table(name: 'link')]
#[ORM\UniqueConstraint(columns: ['website_id', 'relative_reference'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class Link
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['link'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['link'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'links')]
    #[ORM\JoinColumn(name: 'website_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Website $website = null;

    #[ORM\Column(length: 255, options: ['default' => '/', 'collation' => 'utf8mb4_bin'])]
    #[Assert\Length(min: 1, max: 255)]
    #[Assert\Regex('/^\//')]
    #[Serializer\Groups(['link'])]
    private ?string $relativeReference = null;

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

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    #[Serializer\Groups(['link'])]
    public function getWebsiteHost(): ?string
    {
        if ($this->website == null) {
            return null;
        }

        return $this->website->getHost();
    }

    #[Serializer\Groups(['link'])]
    public function getWebsiteName(): ?string
    {
        if ($this->website == null) {
            return null;
        }

        return $this->website->getName();
    }

    public function setWebsite(?Website $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getRelativeReference(): ?string
    {
        return $this->relativeReference;
    }

    public function setRelativeReference(?string $relativeReference): static
    {
        $this->relativeReference = $relativeReference;

        return $this;
    }
}
