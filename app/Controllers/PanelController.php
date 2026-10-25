<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Str;

final class PanelController extends PanelBaseController
{
    public function showLogin(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/panel');
        }
        return $this->view('panel.login', [
            'title' => 'Entrar al panel de campo · AgroDiagnóstico',
            'metaDescription' => 'Accede al CRM de tu unidad de producción.',
            'robots' => 'noindex,nofollow',
        ], 'plain');
    }

    public function login(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/panel');
        }

        $email = Str::normalizeEmail($request->string('email'));
        $password = (string) $request->post('password', '');
        $remember = $request->bool('remember');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || $password === '') {
            $this->withErrors(['email' => 'Escribe un correo válido y tu contraseña.']);
            $this->flashOld(['email' => $email]);
            return $this->redirect('/panel/entrar');
        }

        $result = Auth::attempt($email, $password, $remember);
        if (!$result['ok']) {
            $this->withErrors(['email' => $result['message']]);
            $this->flashOld(['email' => $email]);
            return $this->redirect('/panel/entrar');
        }

        Flash::success('Bienvenido a tu panel de campo.');
        return $this->redirect('/panel');
    }

    public function register(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/panel');
        }
        return $this->redirect('/registro?next=panel');
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        Flash::info('Cerraste sesión del panel de campo.');
        return $this->redirect('/panel/entrar');
    }

    public function dashboard(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $today = new \DateTimeImmutable('today', new \DateTimeZone((string) config('app.timezone', 'America/Mexico_City')));
        $tomorrow = $today->modify('+1 day');

        $stats = [
            'plots' => (int) Database::value('select count(*) from farm_plots where owner_id = $1', [$uid]),
            'visits_today' => (int) Database::value(
                'select count(*) from farm_visits where owner_id = $1 and starts_at >= $2 and starts_at < $3',
                [$uid, $today->format('c'), $tomorrow->format('c')]
            ),
            'low_stock' => (int) Database::value(
                'select count(*) from farm_inputs where owner_id = $1 and active = true and stock <= min_stock',
                [$uid]
            ),
            'sales_today' => (string) (Database::value(
                'select coalesce(sum(total), 0) from farm_sales where owner_id = $1 and created_at >= $2 and created_at < $3',
                [$uid, $today->format('c'), $tomorrow->format('c')]
            ) ?? '0'),
        ];

        $visits = Database::select(
            "select a.*, p.name as plot_name, p.crop, p.producer_name, p.producer_phone
             from farm_visits a
             join farm_plots p on p.id = a.plot_id
             where a.owner_id = $1 and a.starts_at >= $2 and a.starts_at < $3
             order by a.starts_at
             limit 8",
            [$uid, $today->format('c'), $tomorrow->format('c')]
        );

        $lowStock = Database::select(
            'select * from farm_inputs where owner_id = $1 and active = true and stock <= min_stock
             order by stock, name limit 6',
            [$uid]
        );

        $recentPlots = Database::select(
            'select * from farm_plots where owner_id = $1 order by created_at desc limit 6',
            [$uid]
        );

        return $this->panelView('panel.dashboard', [
            'title' => 'Panel de campo · AgroDiagnóstico',
            'metaDescription' => 'Parcelas, visitas, recomendaciones, inventario y ventas de tu operación agronómica.',
            'user' => $user,
            'stats' => $stats,
            'visits' => $visits,
            'lowStock' => $lowStock,
            'recentPlots' => $recentPlots,
            'today' => $today,
            'bodyClass' => 'page-panel',
        ]);
    }
}
