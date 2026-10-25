<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CatalogRepository;
use App\Repositories\ProblemRepository;
use App\Repositories\ResourceRepository;

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
                'title' => 'Quiero vigilar plagas y enfermedades',
                'description' => 'Necesito contexto de brotes, alerta cuarentenaria o salud poblacional.',
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
        $cropCode = $request->string('crops');
        $topic = $request->string('topic');
        $recommendations = isset(self::goals()[$goal]) ? $repo->recommend($goal, $cropCode, $topic, 3) : [];
        $problemMatches = $topic !== '' ? (new ProblemRepository())->findForTopic($topic, $cropCode, 3) : [];
        $filters['crops'] = $cropCode;
        $filters['topic'] = $topic;

        return $this->view('resources.index', [
            'title' => 'Bibliotecas y recursos de acceso abierto · AgroDiagnóstico',
            'metaDescription' => 'Directorio curado de bibliotecas, repositorios, revistas y recursos de acceso abierto para ingenieros agrónomos, con énfasis en México.',
            'resources' => $repo->all($filters),
            'filters' => $filters,
            'categories' => $repo->categories(),
            'goals' => self::goals(),
            'goal' => $goal,
            'recommendations' => $recommendations,
            'cropsList' => (new CatalogRepository())->crops(),
            'cropCode' => $cropCode,
            'topic' => $topic,
            'problemMatches' => $problemMatches,
        ]);
    }

    public function journals(Request $request): Response
    {
        $groups = [
            'Directorios y buscadores' => [
                ['DOAJ · Directory of Open Access Journals', 'https://doaj.org/', 'Directorio de revistas científicas de acceso abierto y revisadas por pares. Busca por título, área o país.', 'Internacional', 'Abierto'],
                ['Latindex', 'https://www.latindex.org/', 'Sistema de información sobre revistas académicas de América Latina, el Caribe, España y Portugal.', 'Iberoamérica', 'Abierto'],
                ['Redalyc', 'https://www.redalyc.org/', 'Hemeroteca de artículos científicos a texto completo, con énfasis en la producción iberoamericana.', 'Iberoamérica', 'Abierto'],
                ['SciELO', 'https://scielo.org/', 'Biblioteca electrónica de revistas de América Latina, Portugal y España con acceso abierto.', 'Iberoamérica', 'Abierto'],
                ['AGRIS · FAO', 'https://agris.fao.org/', 'Índice internacional de literatura agrícola, con registros de miles de revistas y documentos técnicos.', 'Global', 'Abierto'],
            ],
            'Revistas de México' => [
                ['Terra Latinoamericana', 'https://www.terralatinoamericana.org.mx/', 'Revista de la Sociedad Mexicana de la Ciencia del Suelo: fertilidad, manejo y conservación de suelos.', 'México', 'Abierto'],
                ['Agrociencia', 'https://agrociencia-colpos.org/', 'Publicación del Colegio de Postgraduados sobre producción agrícola, suelos, fitotecnia y recursos naturales.', 'México', 'Abierto'],
                ['Revista Fitotecnia Mexicana', 'https://www.revfitotecnia.mx/', 'Revista de la Sociedad Mexicana de Fitogenética: mejoramiento, genética y producción de cultivos.', 'México', 'Abierto'],
                ['Revista Mexicana de Ciencias Agrícolas', 'https://cienciasagricolas.inifap.gob.mx/', 'Publicación del INIFAP con investigación aplicada en cultivos, sanidad y tecnología agrícola.', 'México', 'Abierto'],
                ['Revista Chapingo Serie Horticultura', 'https://revistas.chapingo.mx/horticultura/', 'Revista de la Universidad Autónoma Chapingo dedicada a horticultura y fruticultura.', 'México', 'Abierto'],
                ['Revista Mexicana de Fitopatología', 'https://www.rmf.smf.org.mx/', 'Órgano de la Sociedad Mexicana de Fitopatología: enfermedades, epidemiología y manejo.', 'México', 'Abierto'],
            ],
            'Iberoamérica' => [
                ['Agronomía Mesoamericana', 'https://revistas.ucr.ac.cr/index.php/agromeso', 'Revista de la Universidad de Costa Rica sobre producción agrícola y pecuaria en el trópico.', 'Costa Rica', 'Abierto'],
                ['Pesquisa Agropecuária Brasileira', 'https://www.scielo.br/j/pab/', 'Revista de la Embrapa con investigación agronómica y pecuaria de Brasil.', 'Brasil', 'Abierto'],
                ['Bragantia', 'https://www.scielo.br/j/brag/', 'Revista del Instituto Agronômico de Campinas sobre cultivos, suelos y fitotecnia.', 'Brasil', 'Abierto'],
                ['Chilean Journal of Agricultural Research', 'https://www.scielo.cl/scielo.php?script=sci_serial&pid=0718-5839', 'Revista chilena de investigación agrícola, con artículos en inglés y acceso abierto.', 'Chile', 'Abierto'],
                ['Revista de la Facultad de Ciencias Agrarias UNCuyo', 'https://revistas.uncu.edu.ar/ojs3/index.php/revistafca', 'Publicación argentina sobre producción vegetal, suelos, riego y agroindustria.', 'Argentina', 'Abierto'],
                ['Scientia Agropecuaria', 'https://revistas.unitru.edu.pe/index.php/scientiaagrop', 'Revista peruana de investigación en ciencias agrarias y agroindustriales.', 'Perú', 'Abierto'],
                ['Revista Colombiana de Ciencias Hortícolas', 'https://revistas.uptc.edu.co/index.php/ciencias_horticolas', 'Revista especializada en horticultura tropical y fruticultura.', 'Colombia', 'Abierto'],
            ],
            'Internacionales' => [
                ['Frontiers in Plant Science', 'https://www.frontiersin.org/journals/plant-science', 'Revista de acceso abierto sobre fisiología, genética y protección de cultivos.', 'Internacional', 'Abierto'],
                ['Agriculture · MDPI', 'https://www.mdpi.com/journal/agriculture', 'Revista de acceso abierto sobre sistemas agrícolas, manejo y sostenibilidad.', 'Internacional', 'Abierto'],
                ['Agronomy · MDPI', 'https://www.mdpi.com/journal/agronomy', 'Publicación de acceso abierto en agronomía, suelos y protección de cultivos.', 'Internacional', 'Abierto'],
                ['Journal of Integrative Agriculture', 'https://www.sciencedirect.com/journal/journal-of-integrative-agriculture', 'Revista de la Academia China de Ciencias Agrícolas con acceso abierto.', 'China', 'Abierto'],
                ['Plant Disease', 'https://apsjournals.apsnet.org/journal/pdis', 'Revista de la American Phytopathological Society; algunos artículos son de acceso abierto.', 'Estados Unidos', 'Mixto'],
            ],
            'Para mantenerte al día' => [
                ['SciELO México', 'https://www.scielo.org.mx/', 'Colección mexicana de SciELO con revistas arbitradas de acceso abierto.', 'México', 'Abierto'],
                ['CONAHCYT · Repositorios', 'https://conahcyt.mx/', 'Acceso a repositorios institucionales y producción científica nacional.', 'México', 'Abierto'],
                ['CIMMYT · Publicaciones', 'https://www.cimmyt.org/es/publicaciones/', 'Informes, guías y artículos del Centro Internacional de Mejoramiento de Maíz y Trigo.', 'México', 'Abierto'],
                ['INIFAP · Publicaciones', 'https://www.gob.mx/inifap', 'Guías técnicas, fichas y publicaciones del Instituto Nacional de Investigaciones Forestales, Agrícolas y Pecuarias.', 'México', 'Abierto'],
            ],
        ];

        return $this->view('resources.journals', [
            'title' => 'Revistas y directorios de acceso abierto · AgroDiagnóstico',
            'metaDescription' => 'Directorios, hemerotecas y revistas de acceso abierto para ingenieros agrónomos, con énfasis en México e Iberoamérica.',
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
            'title' => $resource['title'] . ' · Recursos · AgroDiagnóstico',
            'metaDescription' => excerpt($resource['what_for'] ?: $resource['description'], 160),
            'resource' => $resource,
            'related' => $repo->related($resource, 3),
            'isFavorite' => $user !== null && $repo->isFavorite((int) $user['id'], (int) $resource['id']),
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
            'title' => 'Mis recursos guardados · AgroDiagnóstico',
            'metaDescription' => 'Recursos de datos agrónomos que guardaste para consultar después.',
            'resources' => $repo->favoritesForUser((int) $user['id']),
            'user' => $user,
        ]);
    }

    public function kit(Request $request): Response
    {
    $downloads = [
        [
            'title' => 'Plantilla de caso agronómico',
            'description' => 'Estructura lista para documentar un caso: identificación del cultivo, síntomas, diagnósticos diferenciales, manejo y resultado.',
            'file' => '/descargas/plantilla-caso-agronomico.docx',
            'type' => 'DOCX',
            'icon' => 'file-text',
        ],
        [
            'title' => 'Checklist de consentimiento y anonimización',
            'description' => 'Lista imprimible para confirmar el consentimiento del productor y eliminar datos identificables antes de publicar.',
            'file' => '/descargas/checklist-consentimiento-productor.docx',
            'type' => 'DOCX',
            'icon' => 'shield',
        ],
        [
            'title' => 'Hoja de recomendación técnica',
            'description' => 'Formato para entregar al productor: diagnóstico, insumos con ingrediente activo y dosis, prácticas culturales, seguimiento y firma con cédula.',
            'file' => '/descargas/hoja-de-recomendacion.docx',
            'type' => 'DOCX',
            'icon' => 'printer',
        ],
        [
            'title' => 'Consentimiento informado del productor',
            'description' => 'Formato imprimible para autorizar la publicación del caso y el uso de imágenes con datos anonimizados.',
            'file' => '/descargas/consentimiento-informado.docx',
            'type' => 'DOCX',
            'icon' => 'shield',
        ],
        [
            'title' => 'Solicitud de acceso a datos agrícolas',
            'description' => 'Carta modelo para pedir información a SIAP, INIFAP, SENASICA u otra institución, con objetivos, compromisos y anexo de verificación ética.',
            'file' => '/descargas/solicitud-acceso-datos.docx',
            'type' => 'DOCX',
            'icon' => 'mail',
        ],
        [
            'title' => 'Guía rápida: datos agrícolas',
            'description' => 'Qué es un dataset, una API o un repositorio, cómo elegir un recurso y solicitar acceso sin ser programador.',
            'file' => '/descargas/guia-recursos-para-agronomos.docx',
            'type' => 'DOCX',
            'icon' => 'book-open',
        ],
        [
            'title' => 'Referencias bibliográficas (APA 7)',
            'description' => 'Ejemplos para citar artículos, informes técnicos, conjuntos de datos y normas oficiales en casos y publicaciones.',
            'file' => '/descargas/formato-referencia-bibliografica.docx',
            'type' => 'DOCX',
            'icon' => 'book-open',
        ],
        [
            'title' => 'Kit completo en un solo archivo',
            'description' => 'Los siete documentos en un ZIP para compartirlos con tu equipo o imprimirlos en campo.',
            'file' => '/descargas/kit-recursos-agronomicos.zip',
            'type' => 'ZIP',
            'icon' => 'download',
        ],
    ];

        return $this->view('resources.kit', [
            'title' => 'Kit de recursos para agrónomos · AgroDiagnóstico',
            'metaDescription' => 'Plantillas y guías descargables para usar datos, publicar casos y solicitar acceso a repositorios agrónomos sin conocimientos técnicos.',
            'downloads' => $downloads,
        ]);
    }

    public function glossary(Request $request): Response
    {
    $terms = [
        [
            'término' => 'Dataset',
            'definición' => 'Conjunto de datos organizado en filas y columnas o en archivos. Ejemplo: la superficie sembrada y el rendimiento por cultivo y municipio que publica el SIAP.',
        ],
        [
            'término' => 'Repositorio',
            'definición' => 'Sitio donde se guardan y comparten datos, documentos o código, con versión y licencia. Ejemplo: el repositorio institucional del INIFAP.',
        ],
        [
            'término' => 'API',
            'definición' => 'Forma en que dos sistemas intercambian datos. Puedes leer su documentación sin programar; para integrarla en un programa hace falta apoyo técnico. Ejemplo: una API que devuelve precios agrícolas por fecha.',
        ],
        [
            'término' => 'CSV',
            'definición' => 'Archivo de texto con datos separados por comas. Se abre con Excel, Numbers o Google Sheets y conserva las columnas originales.',
        ],
        [
            'término' => 'Metadatos',
            'definición' => 'Información que describe un dato: quién lo generó, cuándo, dónde, con qué método y en qué unidad. Ejemplo: rendimiento expresado en t/ha.',
        ],
        [
            'término' => 'Diccionario de variables',
            'definición' => 'Documento que explica el significado y la unidad de cada columna. Ejemplo: “sup_sembrada” se mide en hectáreas y “produccion” en toneladas.',
        ],
        [
            'término' => 'Licencia',
            'definición' => 'Reglas para usar un contenido: leerlo, copiarlo, modificarlo o usarlo comercialmente. Ejemplo: CC BY 4.0 exige atribución; CC BY-NC-SA 4.0 no permite uso comercial.',
        ],
        [
            'término' => 'Acceso abierto',
            'definición' => 'El recurso se consulta sin pagar ni pedir permiso, aunque puede exigir atribución y conservar la misma licencia.',
        ],
        [
            'término' => 'Acceso por solicitud',
            'definición' => 'Requiere llenar un formulario o enviar una carta al área responsable; puede tardar días o semanas y condicionarse a datos agregados.',
        ],
        [
            'término' => 'Anonimización',
            'definición' => 'Eliminar o modificar datos que permitan identificar a una persona, un predio o una empresa. Ejemplo: publicar el municipio en lugar de las coordenadas GPS exactas de la parcela.',
        ],
        [
            'término' => 'Rendimiento (t/ha)',
            'definición' => 'Producción obtenida por hectárea. No es lo mismo que la producción total: 10 t/ha en 5 ha son 50 t. Se expresa en toneladas por hectárea o en kilogramos por hectárea.',
        ],
        [
            'término' => 'Superficie sembrada, cosechada y siniestrada',
            'definición' => 'Sembrada: hectáreas plantadas. Cosechada: las que llegaron a cosecha. Siniestrada: las pérdidas por sequía, helada, plaga u otro evento.',
        ],
        [
            'término' => 'Ciclo agrícola',
            'definición' => 'Periodo de producción. En México suele hablarse de Primavera-Verano (PV) y Otoño-Invierno (OI), con siembras y cosechas en fechas distintas.',
        ],
        [
            'término' => 'Etapa fenológica',
            'definición' => 'Fase de desarrollo del cultivo: emergencia, vegetativo, floración, llenado de fruto o grano, madurez y cosecha. Condiciona el manejo y la interpretación de los síntomas.',
        ],
        [
            'término' => 'Norma Oficial Mexicana (NOM)',
            'definición' => 'Regulación técnica obligatoria emitida por una dependencia federal. Ejemplo: NOM-021-RECNAT-2000, sobre fertilidad, salinidad y clasificación de suelos.',
        ],
        [
            'término' => 'DOI',
            'definición' => 'Identificador permanente de un documento o conjunto de datos. Sirve para citar la fuente aunque cambie la dirección del sitio.',
        ],
    ];

        return $this->view('resources.glossary', [
            'title' => 'Glosario de datos y tecnología · AgroDiagnóstico',
            'metaDescription' => 'Glosario en español para entender datasets, APIs, repositorios, licencias y términos de datos agrónomos.',
            'terms' => $terms,
        ]);
    }
}
