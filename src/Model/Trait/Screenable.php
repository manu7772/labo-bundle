<?php
namespace Aequation\LaboBundle\Model\Trait;

// Aequation
use Aequation\LaboBundle\Model\Interface\WebpageInterface;
use Aequation\LaboBundle\Model\Interface\ScreenableInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute as Serializer;
// PHP
use Exception;

trait Screenable
{

    public function __construct_screenable(): void
    {
        if(!($this instanceof ScreenableInterface)) {
            throw new Exception('This trait must be used with the ScreenableInterface');
        }
    }

    #[ORM\ManyToOne(targetEntity: WebpageInterface::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Serializer\Ignore]
    protected ?WebpageInterface $webpage;

    public function getWebpage(): ?WebpageInterface
    {
        return $this->webpage;
    }

    public function setWebpage(?WebpageInterface $webpage): static
    {
        $this->webpage = $webpage;
        return $this;
    }

}