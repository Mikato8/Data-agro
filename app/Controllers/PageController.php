<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

final class PageController extends BaseController
{
    public function about(Request $request): Response
    {
        return $this->view('pages.about', [
            'title' => 'Acerca del portal · Diagnóstico Veterinario',
            'metaDescription' => 'Conoce el propósito, el comité editorial y la política de revisión de Diagnóstico Veterinario.',
        ]);
    }

    public function ethics(Request $request): Response
    {
        return $this->view('pages.ethics', [
            'title' => 'Ética y publicación de casos · Diagnóstico Veterinario',
            'metaDescription' => 'Criterios éticos, consentimiento del tutor, anonimización y revisión por pares para publicar casos clínicos veterinarios.',
        ]);
    }

    public function privacy(Request $request): Response
    {
        return $this->view('pages.privacy', [
            'title' => 'Aviso de privacidad · Diagnóstico Veterinario',
            'metaDescription' => 'Aviso de privacidad integral conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares.',
        ]);
    }

    public function terms(Request $request): Response
    {
        return $this->view('pages.terms', [
            'title' => 'Términos de uso · Diagnóstico Veterinario',
            'metaDescription' => 'Términos y condiciones de uso del portal de casos clínicos Diagnóstico Veterinario.',
        ]);
    }
}
