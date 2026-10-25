<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;

final class PanelSaleController extends PanelBaseController
{
    private const PAYMENTS = ['efectivo', 'tarjeta', 'transferencia', 'otro'];

    public function index(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $sales = Database::select(
            'select s.*, p.name as patient_name,
                    (select count(*) from clinic_sale_items i where i.sale_id = s.id) as items_count
             from clinic_sales s left join clinic_patients p on p.id = s.patient_id
             where s.owner_id = $1 order by s.created_at desc limit 80',
            [$uid]
        );
        return $this->panelView('panel.sales.index', [
            'title' => 'Ventas · Panel clínico',
            'user' => $user,
            'sales' => $sales,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        return $this->panelView('panel.sales.form', [
            'title' => 'Nueva venta · Panel clínico',
            'user' => $user,
            'patients' => Database::select('select id, name, tutor_name from clinic_patients where owner_id = $1 order by name', [$uid]),
            'products' => Database::select('select id, name, sku, price, stock from clinic_products where owner_id = $1 and active = true order by name', [$uid]),
            'patientId' => $request->int('patient_id'),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $patientId = $request->int('patient_id');
        $payment = $request->string('payment_method', 'efectivo');
        $notes = trim($request->string('notes'));
        $productIds = $request->post('product_id', []);
        $quantities = $request->post('quantity', []);

        $items = [];
        if (is_array($productIds) && is_array($quantities)) {
            foreach ($productIds as $index => $productId) {
                $pid = (int) $productId;
                $qty = (int) ($quantities[$index] ?? 0);
                if ($pid <= 0 || $qty <= 0) {
                    continue;
                }
                $items[$pid] = ($items[$pid] ?? 0) + $qty;
            }
        }
        if ($items === []) {
            $this->withErrors(['items' => 'Agrega al menos un producto con cantidad.']);
            return $this->redirect('/panel/ventas/nueva');
        }
        if (!in_array($payment, self::PAYMENTS, true)) {
            $this->withErrors(['payment_method' => 'Selecciona una forma de pago válida.']);
            return $this->redirect('/panel/ventas/nueva');
        }

        $payload = json_encode(array_map(
            static fn (int $pid, int $qty): array => ['product_id' => $pid, 'quantity' => $qty],
            array_keys($items),
            array_values($items)
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $saleId = (int) Database::value(
                'select clinic_create_sale($1, $2, $3, $4::jsonb)',
                [$uid, $patientId > 0 ? $patientId : null, $payment, $payload]
            );
        } catch (\Throwable $e) {
            \App\Core\Logger::warning('Error al crear venta de clínica', ['message' => $e->getMessage()]);
            Flash::error('No se pudo registrar la venta. Revisa que los productos tengan existencias suficientes.');
            return $this->redirect('/panel/ventas/nueva');
        }

        if ($notes !== '') {
            Database::execute('update clinic_sales set notes = $1 where id = $2 and owner_id = $3', [$notes, $saleId, $uid]);
        }
        $this->audit('clinica.venta_creada', 'clinic_sale', (string) $saleId);
        Flash::success('Venta registrada y existencias actualizadas.');
        return $this->redirect('/panel/ventas/' . $saleId);
    }

    public function show(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $sale = Database::first(
            'select s.*, p.name as patient_name, p.tutor_name
             from clinic_sales s left join clinic_patients p on p.id = s.patient_id
             where s.id = $1 and s.owner_id = $2',
            [(int) $id, (int) $user['id']]
        );
        if ($sale === null) {
            $this->notFound('No encontramos esa venta.');
        }
        $items = Database::select('select * from clinic_sale_items where sale_id = $1 order by id', [(int) $sale['id']]);
        return $this->panelView('panel.sales.show', [
            'title' => 'Venta #' . $sale['id'] . ' · Panel clínico',
            'user' => $user,
            'sale' => $sale,
            'items' => $items,
        ]);
    }
}
