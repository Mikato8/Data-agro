<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CatalogRepository;
use App\Repositories\DiseaseRepository;
use App\Repositories\PetEvalRepository;
use App\Repositories\ResourceRepository;
use App\Repositories\VetDataHubRepository;

final class ResourceController extends BaseController
{
    /** @return array<string, array{title: string, description: string, icon: string}> */
    public static function goals(): array
    {
        return [
            'datos' => [
                'title' => 'Necesito bibliografía',
                'description' => 'Busco libros, tesis o artículos para una revisión.',
                'icon' => 'book-open',
            ],
            'investigar' => [
                'title' => 'Voy a investigar',
                'description' => 'Preparo una tesis, un artículo o una revisión de literatura.',
                'icon' => 'book-open',
            ],
            'vigilancia' => [
                'title' => 'Quiero vigilar enfermedades',
                'description' => 'Necesito contexto de brotes, zoonosis o salud poblacional.',
                'icon' => 'globe',
            ],
            'aprender' => [
                'title' => 'Quiero aprender',
                'description' => 'Busco guías, manuales y materiales para actualizarme.',
                'icon' => 'award',
            ],
            'revistas' => [
                'title' => 'Busco una revista',
                'description' => 'Quiero una publicación periódica para seguir un tema.',
                'icon' => 'file-text',
            ],
        ];
    }

    public function index(Request $request): Response
    {
        $repo = new ResourceRepository();
        $filters = [
            'q' => $request->string('q'),
            'category' => $request->string('category'),
            'access' => $request->string('access'),
        ];
        $goal = $request->string('goal');
        $speciesCode = $request->string('species');
        $topic = $request->string('topic');
        $recommendations = isset(self::goals()[$goal]) ? $repo->recommend($goal, $speciesCode, $topic, 3) : [];
        $diseaseMatches = $topic !== '' ? (new DiseaseRepository())->findForTopic($topic, $speciesCode, 3) : [];
        $filters['species'] = $speciesCode;
        $filters['topic'] = $topic;

        return $this->view('resources.index', [
            'title' => 'Bibliotecas y recursos de acceso abierto · Diagnóstico Veterinario',
            'metaDescription' => 'Directorio curado de bibliotecas, repositorios, revistas y recursos de acceso abierto para médicos veterinarios, con énfasis en México.',
            'resources' => $repo->all($filters),
            'filters' => $filters,
            'categories' => $repo->categories(),
            'goals' => self::goals(),
            'goal' => $goal,
            'recommendations' => $recommendations,
            'speciesList' => (new CatalogRepository())->species(),
            'speciesCode' => $speciesCode,
            'topic' => $topic,
            'diseaseMatches' => $diseaseMatches,
        ]);
    }

    public function journals(Request $request): Response
    {
        $groups = [
            'Directorios y buscadores' => [
                ['DOAJ · Directory of Open Access Journals', 'https://doaj.org/', 'Directorio de revistas científicas de acceso abierto y revisadas por pares. Ideal para buscar publicaciones veterinarias gratuitas.', 'Internacional', 'Abierto'],
                ['REVIVEC', 'https://revivec.org/', 'Red y portal iberoamericano de revistas científicas veterinarias de libre acceso, con publicaciones de España, Portugal y Latinoamérica.', 'Iberoamérica', 'Abierto'],
                ['Latindex · Portal de Portales', 'https://www.latindex.org/', 'Sistema de información sobre revistas académicas de América Latina, el Caribe, España y Portugal; incluye enlaces a textos completos.', 'Iberoamérica', 'Abierto'],
                ['Redalyc', 'https://www.redalyc.org/', 'Hemeroteca de artículos científicos a texto completo, con énfasis en la producción académica iberoamericana.', 'Iberoamérica', 'Abierto'],
            ],
            'Revistas de América Latina y España' => [
                ['Veterinaria México OA', 'https://veterinariamexico.fmvz.unam.mx/', 'Revista bilingüe de la UNAM, arbitrada y de acceso abierto, sin costo para lectores ni autores.', 'México', 'Abierto'],
                ['Revista de la Facultad de Medicina Veterinaria y de Zootecnia', 'https://revistas.unal.edu.co/index.php/remevez', 'Publicación de la Universidad Nacional de Colombia sobre medicina veterinaria y zootecnia.', 'Colombia', 'Abierto'],
                ['Revista de Salud Animal', 'https://revistas.censa.edu.cu/index.php/RSA', 'Revista cubana con acceso libre e inmediato a sus contenidos sobre salud y producción animal.', 'Cuba', 'Abierto'],
                ['Acta Scientiae Veterinariae', 'https://seer.ufrgs.br/index.php/ActaScientiaeVeterinariae', 'Revista brasileña con texto completo de acceso gratuito en medicina veterinaria.', 'Brasil', 'Abierto'],
                ['Analecta Veterinaria', 'https://www.fcv.unlp.edu.ar/index.php/investigacion/analecta-veterinaria/', 'Publicación de la Universidad Nacional de La Plata; sus volúmenes anteriores están disponibles en PDF.', 'Argentina', 'Abierto'],
                ['CES Medicina Veterinaria y Zootecnia', 'https://revistas.ces.edu.co/index.php/mvz', 'Revista electrónica colombiana de acceso gratuito en medicina veterinaria y zootecnia.', 'Colombia', 'Abierto'],
                ['FAVE · Ciencias Veterinarias', 'https://bibliotecavirtual.unl.edu.ar/publicaciones/index.php/FAVEveterinaria', 'Revista de la Universidad Nacional del Litoral, Argentina, con acceso libre.', 'Argentina', 'Abierto'],
            ],
            'Revistas internacionales' => [
                ['Iraqi Journal of Veterinary Sciences', 'https://www.vetmedmosul.com/', 'Revista de la Universidad de Mosul con artículos completos de acceso gratuito.', 'Irak', 'Abierto'],
                ['Ethiopian Journal of Veterinary Sciences and Animal Production', 'https://journal.mu.edu.et/index.php/ejvsap', 'Revista etíope de acceso abierto, sin costo para lectores ni autores.', 'Etiopía', 'Abierto'],
                ['Open Veterinary Journal', 'https://www.openveterinaryjournal.com/', 'Revista arbitrada de acceso abierto que cubre todas las áreas de la veterinaria.', 'Internacional', 'Abierto'],
                ['BMC Veterinary Research', 'https://bmcvetres.biomedcentral.com/', 'Revista de Springer Nature que abarca todas las especialidades veterinarias.', 'Internacional', 'Abierto'],
                ['Veterinary Medicine and Science', 'https://onlinelibrary.wiley.com/journal/20531095', 'Revista de Wiley de acceso abierto en medicina veterinaria fundamental y clínica.', 'Internacional', 'Abierto'],
                ['Veterinary Sciences · MDPI', 'https://www.mdpi.com/journal/vetsci', 'Revista internacional mensual de acceso abierto publicada por MDPI.', 'Internacional', 'Abierto'],
                ["World's Veterinary Journal", 'https://www.wvj.science-line.com/', 'Revista arbitrada de acceso abierto en medicina veterinaria y ciencia animal.', 'Internacional', 'Abierto'],
                ['Veterinary Quarterly', 'https://www.tandfonline.com/journals/tveq20', 'Revista internacional de Taylor & Francis, arbitrada y de acceso abierto.', 'Internacional', 'Abierto'],
            ],
            'Plataformas editoriales' => [
                ['MDPI', 'https://www.mdpi.com/', 'Editorial con numerosas revistas de acceso abierto, incluyendo varias del área veterinaria.', 'Internacional', 'Abierto'],
                ['BMC · BioMed Central', 'https://www.biomedcentral.com/', 'Parte de Springer Nature; publica revistas de acceso abierto como BMC Veterinary Research.', 'Internacional', 'Abierto'],
                ['Wiley Online Library', 'https://onlinelibrary.wiley.com/', 'Plataforma con revistas de acceso abierto como Veterinary Medicine and Science.', 'Internacional', 'Mixto'],
                ['Frontiers in Veterinary Science', 'https://www.frontiersin.org/journals/veterinary-science', 'Revista de acceso abierto de Frontiers dedicada a la ciencia veterinaria.', 'Internacional', 'Abierto'],
            ],
            'Para mantenerte al día' => [
                ['VetLit', 'https://vetlit.org/', 'Recurso gratuito que reúne publicaciones recientes de más de 40 revistas veterinarias.', 'Internacional', 'Abierto'],
                ['NAVC · Publicaciones', 'https://navc.com/publications/', 'Publicaciones revisadas por pares de la North American Veterinary Community.', 'Estados Unidos', 'Registro'],
                ["Today's Veterinary Practice", 'https://todaysveterinarypractice.com/', 'Publicaciones clínicas con suscripción gratuita para profesionales veterinarios.', 'Estados Unidos', 'Registro'],
            ],
        ];

        return $this->view('resources.journals', [
            'title' => 'Revistas y directorios de acceso abierto · Diagnóstico Veterinario',
            'metaDescription' => 'Directorio de revistas veterinarias, hemerotecas y buscadores de acceso abierto en México, Iberoamérica y el mundo.',
            'groups' => $groups,
            'bodyClass' => 'page-resources',
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $repo = new ResourceRepository();
        $resource = $repo->findBySlug($slug);
        if ($resource === null) {
            $this->notFound('No encontramos esta ficha de recurso.');
        }

        $user = Auth::user();
        return $this->view('resources.show', [
            'title' => $resource['title'] . ' · Recursos · Diagnóstico Veterinario',
            'metaDescription' => excerpt($resource['what_for'] ?: $resource['description'], 160),
            'resource' => $resource,
            'related' => $repo->related($resource, 3),
            'isFavorite' => $user !== null && $repo->isFavorite((int) $user['id'], (int) $resource['id']),
            'vethubTotal' => $resource['slug'] === 'vetdatahub' ? (new VetDataHubRepository())->total() : 0,
            'petevalTotal' => $resource['slug'] === 'peteval' ? (new PetEvalRepository())->total() : 0,
            'goals' => self::goals(),
            'canonical' => url('/recursos/' . $resource['slug']),
        ]);
    }

    public function favorite(Request $request, string $slug): Response
    {
        $user = Auth::requireLogin();
        $repo = new ResourceRepository();
        $resource = $repo->findBySlug($slug);
        if ($resource === null) {
            $this->notFound('No encontramos esta ficha de recurso.');
        }
        $active = $repo->toggleFavorite((int) $user['id'], (int) $resource['id']);
        Flash::success($active ? 'Recurso guardado en tus favoritos.' : 'Recurso eliminado de tus favoritos.');
        return $this->redirect('/recursos/' . $resource['slug'] . '#favorito');
    }

    public function favorites(Request $request): Response
    {
        $user = Auth::requireLogin();
        $repo = new ResourceRepository();
        return $this->view('resources.favorites', [
            'title' => 'Mis recursos guardados · Diagnóstico Veterinario',
            'metaDescription' => 'Recursos de datos veterinarios que guardaste para consultar después.',
            'resources' => $repo->favoritesForUser((int) $user['id']),
            'user' => $user,
        ]);
    }

    public function kit(Request $request): Response
    {
        $downloads = [
            [
                'title' => 'Plantilla de caso clínico',
                'description' => 'Estructura lista para escribir un caso con anamnesis, exploración, estudios, diagnóstico y discusión.',
                'file' => '/descargas/plantilla-caso-clinico.docx',
                'type' => 'DOCX',
                'icon' => 'file-text',
            ],
            [
                'title' => 'Checklist de consentimiento y anonimización',
                'description' => 'Lista imprimible para confirmar consentimiento del tutor y eliminar datos identificables antes de publicar.',
                'file' => '/descargas/checklist-consentimiento.docx',
                'type' => 'DOCX',
                'icon' => 'shield',
            ],
            [
                'title' => 'Solicitud de acceso a datos',
                'description' => 'Carta modelo para pedir acceso a un dataset o repositorio de investigación.',
                'file' => '/descargas/solicitud-acceso-datos.docx',
                'type' => 'DOCX',
                'icon' => 'mail',
            ],
            [
                'title' => 'Guía rápida: recursos para veterinarios',
                'description' => 'Explicación breve de qué es un dataset, una API, un repositorio y cómo pedir acceso sin ser programador.',
                'file' => '/descargas/guia-recursos-para-veterinarios.docx',
                'type' => 'DOCX',
                'icon' => 'book-open',
            ],
            [
                'title' => 'Hoja de alta y recomendaciones',
                'description' => 'Formato para entregar al tutor: diagnóstico, medicamentos, cuidados en casa, signos de alarma y seguimiento.',
                'file' => '/descargas/hoja-de-alta.docx',
                'type' => 'DOCX',
                'icon' => 'file-text',
            ],
            [
                'title' => 'Consentimiento informado imprimible',
                'description' => 'Formato para autorizar la publicación educativa del caso con datos anonimizados y firma del tutor.',
                'file' => '/descargas/consentimiento-informado.docx',
                'type' => 'DOCX',
                'icon' => 'shield',
            ],
            [
                'title' => 'Cómo citar casos, datos y recursos',
                'description' => 'Ejemplos en estilo APA 7 para citar casos del portal, artículos, libros, datasets, software y este kit.',
                'file' => '/descargas/formato-referencia-bibliografica.docx',
                'type' => 'DOCX',
                'icon' => 'book-open',
            ],
            [
                'title' => 'Kit completo en un solo archivo',
                'description' => 'Descarga todas las plantillas juntas en un archivo ZIP para compartirlas con tu equipo.',
                'file' => '/descargas/kit-recursos-veterinarios.zip',
                'type' => 'ZIP',
                'icon' => 'download',
            ],
        ];

        return $this->view('resources.kit', [
            'title' => 'Kit de recursos para veterinarios · Diagnóstico Veterinario',
            'metaDescription' => 'Plantillas y guías descargables para usar datos, publicar casos y solicitar acceso a repositorios veterinarios sin conocimientos técnicos.',
            'downloads' => $downloads,
        ]);
    }

    public function glossary(Request $request): Response
    {
        $terms = [
            ['término' => 'Dataset', 'definición' => 'Un conjunto de datos organizado en filas y columnas, o en archivos, que puede analizarse con una hoja de cálculo o un programa.'],
            ['término' => 'Repositorio', 'definición' => 'Un sitio donde se guardan y comparten archivos, datos o código. GitHub es un repositorio de código; Dryad es un repositorio de datos de investigación.'],
            ['término' => 'API', 'definición' => 'Una forma de que dos sistemas se comuniquen. No necesitas programar para beneficiarte de una API, pero sí para integrarla en tu software.'],
            ['término' => 'CSV', 'definición' => 'Archivo de texto con datos separados por comas. Se abre con Excel, Numbers o Google Sheets.'],
            ['término' => 'Licencia', 'definición' => 'Reglas que indican qué puedes hacer con un contenido: leerlo, copiarlo, modificarlo o usarlo comercialmente.'],
            ['término' => 'Acceso abierto', 'definición' => 'El recurso puede consultarse sin pagar ni pedir permiso, aunque puede tener condiciones de atribución.'],
            ['término' => 'Acceso por solicitud', 'definición' => 'Debes llenar un formulario o escribir al equipo responsable. Puede tardar días o semanas.'],
            ['término' => 'Metadatos', 'definición' => 'Información que describe un dato: quién lo generó, cuándo, cómo y con qué método.'],
            ['término' => 'Anonimización', 'definición' => 'Eliminar o modificar datos que permiten identificar a una persona o animal.'],
            ['término' => 'ICD-11', 'definición' => 'Clasificación internacional de enfermedades. En veterinaria se usa para estandarizar diagnósticos y facilitar la comparación.'],
        ];

        return $this->view('resources.glossary', [
            'title' => 'Glosario de datos y tecnología · Diagnóstico Veterinario',
            'metaDescription' => 'Glosario en español para entender datasets, APIs, repositorios, licencias y términos de datos veterinarios.',
            'terms' => $terms,
        ]);
    }
}
