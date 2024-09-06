<?php
namespace Aequation\LaboBundle\Controller\Account;

use Aequation\LaboBundle\Controller\Base\CommonController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/account')]
class AccountController extends CommonController
{
    #[Route(path: '/', name: 'app_account')]
    public function account(): Response
    {
        return $this->render('account/index.html.twig');
    }

    #[Route('/update', name: 'app_account_update')]
    public function index(
    ): Response
    {
        return $this->render(
            view: 'account/update.html.twig',
            parameters: ['user' => $this->getUser()],
        );
    }

}
