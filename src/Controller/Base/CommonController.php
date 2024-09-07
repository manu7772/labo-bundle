<?php
namespace Aequation\LaboBundle\Controller\Base;

use Aequation\LaboBundle\Service\AppService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Turbo\TurboBundle;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class CommonController extends AbstractController
{

    // public function __construct(
    //     private AppService $appService,
    // ) {
    //     if($this->appService->isDev()) {
    //         dd($this->appService->getAppContext(), json_decode(json_encode($this->appService->getAppContext()), true));
    //     }
    // }


    // /**
    //  * Is Turbo-Frame request
    //  * @param Request|null $request
    //  * @return boolean
    //  */
    // protected function isTurboFrameRequest(
    //     ?Request $request = null,
    // ): bool
    // {
    //     // return !empty($request->headers->get('Turbo-Frame'));
    //     return $this->appService->isTurboFrameRequest($request);
    // }

    // /**
    //  * Is Turbo-Stream request
    //  * @param Request|null $request
    //  * @param boolean $prepareRequest
    //  * @return boolean
    //  */
    // public function isTurboStreamRequest(
    //     ?Request $request = null,
    //     bool $prepareRequest = true,
    // ): bool
    // {
    //     return $this->appService->isTurboStreamRequest($request, $prepareRequest);
    // }

    protected function notInstalled(): Response
    {
        $this->addFlash('warning', 'Le site n\'est pas installé, veuillez procéder à sa configuration svp.');
        return $this->redirectToRoute('aequation_labo_home');
    }

    // /**
    //  * Is Live request
    //  * @param Request $request
    //  * @return boolean
    //  */
    // public function isLiveRequest(
    //     Request $request,
    // ): bool
    // {
    //     return $request->getMethod() === 'POST' && !$this->isTurboFrameRequest($request, false);
    // }

}
