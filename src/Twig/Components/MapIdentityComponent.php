<?php
namespace Aequation\LaboBundle\Twig\Components;

use Aequation\LaboBundle\Model\Final\FinalAddresslinkInterface;
use Aequation\LaboBundle\Model\Interface\MenuInterface;
use Aequation\LaboBundle\Model\Final\FinalWebpageInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Repository\Interface\MenuRepositoryInterface;
// Symfony
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\Component\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

#[AsTwigComponent()]
class MapIdentityComponent extends AbstractController
{

    public object $identity;
    public ?Map $map;

    public function getMap(): ?Map
    {
        if(!isset($this->map)) {
            if($mapinfo = $this->extractMapInfo($this->identity)) {
                $this->map = (new Map('default'))
                    ->center(new Point($mapinfo['lat'], $mapinfo['lng']))
                    ->zoom($mapinfo['zoom'])
                    ->addMarker(new Marker(
                        position: new Point($mapinfo['lat'], $mapinfo['lng']),
                        title: 'Lyon',
                        infoWindow: new InfoWindow(
                            content: '<p>Thank you <a href="https://github.com/Kocal">@Kocal</a> for this component!</p>',
                        )
                    ))
                    ->options((new LeafletOptions())
                        ->tileLayer(new TileLayer(
                            url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                            options: ['maxZoom' => 19]
                        ))
                    );
            } else {
                $this->map = null;
            }
        }
        return $this->map;
    }

    protected function extractMapInfo(object $identity): ?array
    {
        $address = null;
        if(!($identity instanceof FinalAddresslinkInterface)) {
            if(method_exists($identity, 'getAddresses')) {
                $addresses = $identity->getAddresses();
                $address = $addresses->isEmpty() ? null : $addresses->first();
            } else if(method_exists($identity, 'getAddresse')) {
                $address = $identity->getAddresse();
            } else {
                return null;
            }
        }
        if($address instanceof FinalAddresslinkInterface) {
            // dump($address);
            $mapinfo = [];
            $mapinfo['lat'] = floatval($address->getGps()[0] ?? 4.8295061);
            $mapinfo['lng'] = floatval($address->getGps()[1] ?? 45.7534031);
            $mapinfo['zoom'] = intval($address->getGps()[2] ?? 15);
            // dump($mapinfo);
            return $mapinfo;
        }
        return null;
    }

}