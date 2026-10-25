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
            'title' => 'Acerca del portal · AgroDiagnóstico',
            'metaDescription' => 'Conoce el propósito, el comité editorial y la política de revisión de AgroDiagnóstico.',
        ]);
    }

    public function ethics(Request $request): Response
    {
        return $this->view('pages.ethics', [
            'title' => 'Ética y publicación de casos · AgroDiagnóstico',
            'metaDescription' => 'Criterios éticos, consentimiento del productor, anonimización y revisión por pares para publicar casos agronómicos agrónomos.',
        ]);
    }

    public function privacy(Request $request): Response
    {
        return $this->view('pages.privacy', [
            'title' => 'Aviso de privacidad · AgroDiagnóstico',
            'metaDescription' => 'Aviso de privacidad integral conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares.',
        ]);
    }

    public function terms(Request $request): Response
    {
        return $this->view('pages.terms', [
            'title' => 'Términos de uso · AgroDiagnóstico',
            'metaDescription' => 'Términos y condiciones de uso del portal de casos agronómicos AgroDiagnóstico.',
        ]);
    }
}
