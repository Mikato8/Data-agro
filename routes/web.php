<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\CaseController;
use App\Controllers\DiagnosticController;
use App\Controllers\HealthController;
use App\Controllers\HomeController;
use App\Controllers\PageController;
use App\Controllers\PanelController;
use App\Controllers\PanelAppointmentController;
use App\Controllers\PanelInventoryController;
use App\Controllers\PanelPatientController;
use App\Controllers\PanelSaleController;
use App\Controllers\PanelPrescriptionController;
use App\Controllers\ProfileController;
use App\Controllers\ResourceController;
use App\Controllers\SitemapController;
use App\Controllers\PetEvalController;
use App\Controllers\VetDataHubController;
use App\Core\Router;

return static function (Router $router): void {
    // Panel clínico (CRM)
    $router->get('/panel/entrar', [PanelController::class, 'showLogin']);
    $router->get('/panel/registro', [PanelController::class, 'register']);
    $router->post('/panel/entrar', [PanelController::class, 'login']);
    $router->post('/panel/salir', [PanelController::class, 'logout']);
    $router->get('/panel', [PanelController::class, 'dashboard']);
    $router->get('/panel/pacientes', [PanelPatientController::class, 'index']);
    $router->get('/panel/pacientes/nuevo', [PanelPatientController::class, 'create']);
    $router->post('/panel/pacientes', [PanelPatientController::class, 'store']);
    $router->get('/panel/pacientes/{id}', [PanelPatientController::class, 'show']);
    $router->get('/panel/pacientes/{id}/editar', [PanelPatientController::class, 'edit']);
    $router->post('/panel/pacientes/{id}', [PanelPatientController::class, 'update']);
    $router->get('/panel/citas', [PanelAppointmentController::class, 'index']);
    $router->get('/panel/citas/nueva', [PanelAppointmentController::class, 'create']);
    $router->post('/panel/citas', [PanelAppointmentController::class, 'store']);
    $router->post('/panel/citas/{id}/estado', [PanelAppointmentController::class, 'updateStatus']);
    $router->get('/panel/recetas', [PanelPrescriptionController::class, 'index']);
    $router->get('/panel/recetas/nueva', [PanelPrescriptionController::class, 'create']);
    $router->post('/panel/recetas', [PanelPrescriptionController::class, 'store']);
    $router->get('/panel/recetas/{id}', [PanelPrescriptionController::class, 'show']);
    $router->get('/panel/inventario', [PanelInventoryController::class, 'index']);
    $router->get('/panel/inventario/nuevo', [PanelInventoryController::class, 'create']);
    $router->post('/panel/inventario', [PanelInventoryController::class, 'store']);
    $router->get('/panel/inventario/{id}/editar', [PanelInventoryController::class, 'edit']);
    $router->post('/panel/inventario/{id}', [PanelInventoryController::class, 'update']);
    $router->get('/panel/ventas', [PanelSaleController::class, 'index']);
    $router->get('/panel/ventas/nueva', [PanelSaleController::class, 'create']);
    $router->post('/panel/ventas', [PanelSaleController::class, 'store']);
    $router->get('/panel/ventas/{id}', [PanelSaleController::class, 'show']);

    // Público
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/casos', [CaseController::class, 'index']);
    $router->get('/casos/nuevo', [CaseController::class, 'create']);
    $router->post('/casos', [CaseController::class, 'store']);
    $router->get('/casos/{slug}/ia-urgencia', [CaseController::class, 'aiForm']);
    $router->post('/casos/{slug}/ia-urgencia', [CaseController::class, 'aiClassify']);
    $router->get('/casos/{slug}/pdf', [CaseController::class, 'pdf']);
    $router->get('/casos/{slug}/docx', [CaseController::class, 'docx']);
    $router->get('/casos/{slug}', [CaseController::class, 'show']);
    $router->get('/casos/{slug}/editar', [CaseController::class, 'edit']);
    $router->post('/casos/{slug}', [CaseController::class, 'update']);
    $router->post('/casos/{slug}/enviar', [CaseController::class, 'submit']);
    $router->post('/casos/{slug}/comentarios', [CaseController::class, 'comment']);
    $router->post('/casos/{slug}/util', [CaseController::class, 'react']);
    $router->post('/casos/{slug}/imagenes/{imageId}/eliminar', [CaseController::class, 'deleteImage']);

    // Apoyo diagnóstico y fichas
    $router->get('/apoyo-diagnostico/ia', [DiagnosticController::class, 'aiForm']);
    $router->post('/apoyo-diagnostico/ia', [DiagnosticController::class, 'aiClassify']);
    $router->get('/apoyo-diagnostico', [DiagnosticController::class, 'index']);
    $router->get('/apoyo-diagnostico/signos', [DiagnosticController::class, 'signs']);
    $router->post('/apoyo-diagnostico', [DiagnosticController::class, 'analyze']);
    $router->get('/enfermedades', [DiagnosticController::class, 'diseases']);
    $router->get('/enfermedades/{slug}', [DiagnosticController::class, 'disease']);

    // Recursos y páginas
    $router->get('/recursos', [ResourceController::class, 'index']);
    $router->get('/recursos/kit', [ResourceController::class, 'kit']);
    $router->get('/recursos/glosario', [ResourceController::class, 'glossary']);
    $router->get('/recursos/revistas', [ResourceController::class, 'journals']);
    $router->get('/recursos/favoritos', [ResourceController::class, 'favorites']);
    $router->post('/recursos/{slug}/favorito', [ResourceController::class, 'favorite']);
    $router->get('/recursos/{slug}', [ResourceController::class, 'show']);
    $router->get('/vetdatahub', [VetDataHubController::class, 'index']);
    $router->get('/peteval', [PetEvalController::class, 'index']);
    $router->get('/peteval/{id}', [PetEvalController::class, 'show']);
    $router->get('/nosotros', [PageController::class, 'about']);
    $router->get('/etica', [PageController::class, 'ethics']);
    $router->get('/privacidad', [PageController::class, 'privacy']);
    $router->get('/terminos', [PageController::class, 'terms']);

    // Cuenta
    $router->get('/acceder', [AuthController::class, 'showLogin']);
    $router->post('/acceder', [AuthController::class, 'login']);
    $router->get('/registro', [AuthController::class, 'showRegister']);
    $router->post('/registro', [AuthController::class, 'register']);
    $router->post('/salir', [AuthController::class, 'logout']);
    $router->get('/recuperar', [AuthController::class, 'showForgot']);
    $router->post('/recuperar', [AuthController::class, 'sendReset']);
    $router->get('/recuperar/{token}', [AuthController::class, 'showReset']);
    $router->post('/recuperar/{token}', [AuthController::class, 'reset']);
    $router->get('/perfil', [ProfileController::class, 'show']);
    $router->post('/perfil', [ProfileController::class, 'update']);
    $router->post('/perfil/avatar', [ProfileController::class, 'avatar']);
    $router->post('/perfil/avatar/eliminar', [ProfileController::class, 'removeAvatar']);
    $router->post('/perfil/password', [ProfileController::class, 'password']);
    $router->post('/perfil/eliminar', [ProfileController::class, 'deleteAccount']);
    $router->get('/mis-casos', [CaseController::class, 'mine']);

    // Panel editorial
    $router->get('/admin', [AdminController::class, 'dashboard']);
    $router->get('/admin/casos', [AdminController::class, 'cases']);
    $router->get('/admin/casos/{id}', [AdminController::class, 'reviewCase']);
    $router->post('/admin/casos/{id}/revision', [AdminController::class, 'review']);
    $router->get('/admin/usuarios', [AdminController::class, 'users']);
    $router->post('/admin/usuarios/{id}/estado', [AdminController::class, 'updateUserStatus']);
    $router->post('/admin/usuarios/{id}/rol', [AdminController::class, 'updateUserRole']);
    $router->get('/admin/recursos', [AdminController::class, 'resources']);
    $router->get('/admin/recursos/nuevo', [AdminController::class, 'resourceForm']);
    $router->post('/admin/recursos', [AdminController::class, 'resourceStore']);
    $router->get('/admin/recursos/{id}/editar', [AdminController::class, 'resourceForm']);
    $router->post('/admin/recursos/{id}', [AdminController::class, 'resourceUpdate']);
    $router->post('/admin/recursos/{id}/eliminar', [AdminController::class, 'resourceDelete']);
    $router->post('/admin/comentarios/{id}/ocultar', [AdminController::class, 'hideComment']);

    // Infraestructura
    $router->get('/salud', [HealthController::class, 'index']);
    $router->get('/sitemap.xml', [SitemapController::class, 'index']);
    $router->get('/robots.txt', [SitemapController::class, 'robots']);
};
