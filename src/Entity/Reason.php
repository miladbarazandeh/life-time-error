<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ReasonRepository")
 */
class Reason
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $title;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_positive;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Mood", mappedBy="reason")
     */
    private $moods;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="reasons")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    public function __construct()
    {
        $this->moods = new ArrayCollection();
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

    public function getIsPositive(): ?bool
    {
        return $this->is_positive;
    }

    public function setIsPositive(bool $is_positive): self
    {
        $this->is_positive = $is_positive;

        return $this;
    }

    /**
     * @return Collection|Mood[]
     */
    public function getMoods(): Collection
    {
        return $this->moods;
    }

    public function addMood(Mood $mood): self
    {
        if (!$this->moods->contains($mood)) {
            $this->moods[] = $mood;
            $mood->addReason($this);
        }

        return $this;
    }

    public function removeMood(Mood $mood): self
    {
        if ($this->moods->contains($mood)) {
            $this->moods->removeElement($mood);
            $mood->removeReason($this);
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
