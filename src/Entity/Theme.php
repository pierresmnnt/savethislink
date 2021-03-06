<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\ThemeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Doctrine\ThemeSetOwnerListener;
use App\Doctrine\ThemeSetSlugListener;
use App\Doctrine\ThemeSetWebsiteCountListener;
use App\Doctrine\ThemeSetFollowedByCurrentUserListener;

/**
 * @ApiResource(
 *      collectionOperations={
 *          "get", 
 *          "post"={"security"="is_granted('ROLE_USER')"}
 *      },
 *      itemOperations={
 *          "get", 
 *          "put"={
 *              "security"="is_granted('TOPIC_EDIT', object)",
 *              "security_message"="Seul le créateur du sujet peut l'éditer."
 *          }, 
 *          "delete"={
 *              "security"="is_granted('TOPIC_DELETE', object)",
 *              "security_message"="Seul le créateur du sujet peut le supprimer."
 *          }
 *      },
 *      normalizationContext={"groups"={"theme:read"}},
 *      denormalizationContext={"groups"={"theme:write"}},
 *      attributes={
 *          "order"={"createdAt": "DESC"},
 *          "pagination_items_per_page"=10
 *      },
 * )
 * @ApiFilter(SearchFilter::class, properties={"title": "partial", "description": "partial", "owner": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"websitesCount"})
 * @ApiFilter(PropertyFilter::class)
 * @ORM\Entity(repositoryClass=ThemeRepository::class)
 * @ORM\EntityListeners({ThemeSetSlugListener::class, ThemeSetOwnerListener::class, ThemeSetWebsiteCountListener::class, ThemeSetFollowedByCurrentUserListener::class})
 * @UniqueEntity(fields={"title"}, message="Ce titre existe déjà, choisissez un titre différent.")
 */
class Theme implements UserOwnedInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"theme:read", "website:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"theme:read", "theme:write", "website:read"})
     * @Assert\NotBlank
     * @Assert\Length(
     *      min = 2,
     *      max = 255
     * )
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"theme:read", "theme:write"})
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"theme:read"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"theme:read"})
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="themes")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"theme:read", "website:read"})
     */
    private $owner;

    /**
     * @ORM\OneToMany(targetEntity=Website::class, mappedBy="theme", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private $websites;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"theme:read", "website:read"})
     */
    private $slug;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"theme:read", "theme:write"})
     */
    private $private = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"theme:read", "theme:write"})
     */
    private $open = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"theme:read", "theme:write", "website:read"})
     */
    private $approve = false;

    /**
     * @Groups({"theme:read"})
     */
    private $websitesCount = 0;

    /**
     * @Groups({"theme:read"})
     */
    private $followedByCurrentUser = false;

    /**
     * @ORM\OneToMany(targetEntity=Follow::class, mappedBy="target", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private $followers;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class, inversedBy="themes", cascade={"persist"})
     * @Groups({"theme:read"})
     */
    private $tags;

    public function __construct()
    {
        $this->websites = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->followers = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection|Website[]
     */
    public function getWebsites(): Collection
    {
        return $this->websites;
    }

    public function addWebsite(Website $website): self
    {
        if (!$this->websites->contains($website)) {
            $this->websites[] = $website;
            $website->setTheme($this);
        }

        return $this;
    }

    public function removeWebsite(Website $website): self
    {
        if ($this->websites->removeElement($website)) {
            // set the owning side to null (unless already changed)
            if ($website->getTheme() === $this) {
                $website->setTheme(null);
            }
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getOpen(): ?bool
    {
        return $this->open;
    }

    public function setOpen(?bool $open): self
    {
        $this->open = $open;

        return $this;
    }

    public function getApprove(): ?bool
    {
        return $this->approve;
    }

    public function setApprove(?bool $approve): self
    {
        $this->approve = $approve;

        return $this;
    }

    public function getWebsitesCount(): int
    {
        return $this->websitesCount;
    }

    public function setWebsiteCount(int $websitesCount): self
    {
        $this->websitesCount = $websitesCount;

        return $this;
    }

    public function getPrivate(): ?bool
    {
        return $this->private;
    }

    public function setPrivate(?bool $private): self
    {
        $this->private = $private;

        return $this;
    }

    /**
     * @return Collection|Follow[]
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function addFollower(Follow $follower): self
    {
        if (!$this->followers->contains($follower)) {
            $this->followers[] = $follower;
            $follower->setTarget($this);
        }

        return $this;
    }

    public function removeFollower(Follow $follower): self
    {
        if ($this->followers->removeElement($follower)) {
            // set the owning side to null (unless already changed)
            if ($follower->getTarget() === $this) {
                $follower->setTarget(null);
            }
        }

        return $this;
    }

    public function getFollowedByCurrentUser(): bool
    {
        return $this->followedByCurrentUser;
    }

    public function setFollowedByCurrentUser(bool $followedByCurrentUser): self
    {
        $this->followedByCurrentUser = $followedByCurrentUser;

        return $this;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
