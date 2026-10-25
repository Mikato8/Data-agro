<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

abstract class PanelBaseController extends BaseController
{
    /** @return array<string, mixed> */
    protected function panelUser(): array
    {
        $user = Auth::user();
        if ($user === null) {
            $_SESSION['intended_url'] = Request::capture()->path();
            header('Location: ' . url('/panel/entrar'));
            exit;
        }
        return $user;
    }

    protected function ownerId(): int
    {
        return (int) $this->panelUser()['id'];
    }

    /** @param array<string, mixed> $data */
    protected function panelView(string $view, array $data = []): Response
    {
        return $this->view($view, $data, 'panel');
    }
}
