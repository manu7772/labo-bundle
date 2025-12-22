<?php
namespace Aequation\LaboBundle\Model\Trait;

// Aequation
use Exception;
use Doctrine\ORM\Mapping as ORM;
// Symfony
use Symfony\Component\Serializer\Attribute as Serializer;
// PHP
use Aequation\LaboBundle\Model\Final\FinalWebpageInterface;
use Aequation\LaboBundle\Model\Interface\ScreenableInterface;

trait Screenable
{

    public function __construct_screenable(): void
    {
        if(!($this instanceof ScreenableInterface)) {
            throw new Exception('This trait must be used with the ScreenableInterface');
        }
    }

    #[ORM\ManyToOne(targetEntity: FinalWebpageInterface::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Serializer\Ignore]
    protected ?FinalWebpageInterface $webpage;

    public function getWebpage(): ?FinalWebpageInterface
    {
        return $this->webpage;
    }

    public function setWebpage(?FinalWebpageInterface $webpage): static
    {
        $this->webpage = $webpage;
        return $this;
    }

}