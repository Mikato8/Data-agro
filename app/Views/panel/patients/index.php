<?php
/** @var array<int, array<string, mixed>> $patients */
/** @var int $total */
/** @var string $q */
/** @var \App\Core\Paginator $paginator */
?>
<div class="panel-page-head">
    <div>
        <span class="panel-eyebrow">Directorio clínico</span>
        <h1>Pacientes</h1>
        <p><?= number_format($total) ?> paciente<?= $total === 1 ? '' : 's' ?> registrado<?= $total === 1 ? '' : 's' ?>.</p>
    </div>
    <div class="panel-head-actions">
        <a class="btn btn-primary btn-sm" href="<?= e(url('/panel/pacientes/nuevo')) ?>"><?= icon('plus', 'icon icon-sm') ?> Nuevo paciente</a>
    </div>
</div>

<div class="panel-toolbar">
    <form class="panel-search" method="get" action="<?= e(url('/panel/pacientes')) ?>">
        <?= icon('search', 'icon icon-sm') ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por paciente, tutor o teléfono…" aria-label="Buscar pacientes">
    </form>
</div>

<?php if ($patients === []): ?>
    <div class="panel-card">
        <div class="panel-empty">
            <?= icon('paw', 'icon') ?>
            <p><?= $q !== '' ? 'No encontramos pacientes con esa búsqueda.' : 'Aún no has registrado pacientes.' ?></p>
            <a class="btn btn-primary btn-sm" href="<?= e(url('/panel/pacientes/nuevo')) ?>">Registrar paciente</a>
        </div>
    </div>
<?php else: ?>
    <div class="panel-table-wrap">
        <table class="panel-table">
            <thead><tr><th>Paciente</th><th>Tutor</th><th>Especie</th><th>Contacto</th><th>Registro</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($patients as $patient): ?>
                <tr>
                    <td><a href="<?= e(url('/panel/pacientes/' . $patient['id'])) ?>"><?= e($patient['name']) ?></a><small><?= e($patient['breed'] ?? 'Sin raza') ?> · <?= e(species_label((string) $patient['species'])) ?></small></td>
                    <td><?= e($patient['tutor_name']) ?></td>
                    <td><?= e(species_label((string) $patient['species'])) ?></td>
                    <td><?= e($patient['tutor_phone'] ?? '—') ?></td>
                    <td><?= e(format_date($patient['created_at'], 'd/m/Y')) ?></td>
                    <td><a class="panel-text-link" href="<?= e(url('/panel/pacientes/' . $patient['id'])) ?>">Abrir <?= icon('arrow-right', 'icon icon-xs') ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php \App\Core\View::partial('partials/pagination', ['paginator' => $paginator]); ?>
<?php endif; ?>
