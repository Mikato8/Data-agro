<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;

final class PanelInventoryController extends PanelBaseController
{
    public function index(Request $request): Response
    {
        $user = $this->panelUser();
        $uid = (int) $user['id'];
        $q = trim($request->string('q'));
        $low = $request->bool('low');
        $where = 'owner_id = $1';
        $params = [$uid];
        if ($q !== '') {
            $params[] = '%' . $q . '%';
            $where .= ' and (name ilike $2 or sku ilike $2 or category ilike $2)';
        }
        if ($low) {
            $where .= ' and stock <= min_stock';
        }
        $inputs = Database::select("select * from farm_inputs where {$where} order by active desc, name", $params);

        return $this->panelView('panel.inventory.index', [
            'title' => 'Inventario · Panel de campo',
            'user' => $user,
            'inputs' => $inputs,
            'q' => $q,
            'low' => $low,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->panelView('panel.inventory.form', [
            'title' => 'Nuevo insumo · Panel de campo',
            'user' => $this->panelUser(),
            'input' => null,
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->panelUser();
        [$data, $errors] = $this->validateInput($request);
        if ($errors !== []) {
            $this->withErrors($errors);
            $this->flashOld($data);
            return $this->redirect('/panel/inventario/nuevo');
        }
        if (Database::value('select id from farm_inputs where owner_id = $1 and sku = $2', [(int) $user['id'], $data['sku']]) !== null) {
            $this->withErrors(['sku' => 'Ya tienes un insumo con ese SKU.']);
            $this->flashOld($data);
            return $this->redirect('/panel/inventario/nuevo');
        }
        $row = Database::insert(
            'insert into farm_inputs (owner_id, sku, name, category, unit, price, stock, min_stock, active)
             values ($1, $2, $3, $4, $5, $6, $7, $8, $9) returning id',
            [(int) $user['id'], $data['sku'], $data['name'], $data['category'], $data['unit'], $data['price'], $data['stock'], $data['min_stock'], $data['active']]
        );
        $this->audit('campo.producto_creado', 'farm_input', (string) ($row['id'] ?? 0));
        Flash::success('Insumo agregado al inventario.');
        return $this->redirect('/panel/inventario');
    }

    public function edit(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $input = $this->findInput((int) $user['id'], (int) $id);
        if ($input === null) {
            $this->notFound('No encontramos ese insumo.');
        }
        return $this->panelView('panel.inventory.form', [
            'title' => 'Editar ' . $input['name'] . ' · Panel de campo',
            'user' => $user,
            'input' => $input,
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $input = $this->findInput((int) $user['id'], (int) $id);
        if ($input === null) {
            $this->notFound('No encontramos ese insumo.');
        }
        [$data, $errors] = $this->validateInput($request);
        if (Database::value('select id from farm_inputs where owner_id = $1 and sku = $2 and id <> $3', [(int) $user['id'], $data['sku'], (int) $input['id']]) !== null) {
            $errors['sku'] = 'Ya tienes otro insumo con ese SKU.';
        }
        if ($errors !== []) {
            $this->withErrors($errors);
            $this->flashOld($data);
            return $this->redirect('/panel/inventario/' . (int) $input['id'] . '/editar');
        }
        Database::execute(
            'update farm_inputs set sku = $1, name = $2, category = $3, unit = $4, price = $5, stock = $6, min_stock = $7, active = $8
             where id = $9 and owner_id = $10',
            [$data['sku'], $data['name'], $data['category'], $data['unit'], $data['price'], $data['stock'], $data['min_stock'], $data['active'], (int) $input['id'], (int) $user['id']]
        );
        $this->audit('campo.producto_actualizado', 'farm_input', (string) $input['id']);
        Flash::success('Insumo actualizado.');
        return $this->redirect('/panel/inventario');
    }

    /** @return array<string, mixed>|null */
    private function findInput(int $ownerId, int $id): ?array
    {
        return Database::first('select * from farm_inputs where id = $1 and owner_id = $2', [$id, $ownerId]);
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>} */
    private function validateInput(Request $request): array
    {
        $data = [
            'sku' => trim($request->string('sku')),
            'name' => trim($request->string('name')),
            'category' => $request->string('category', 'otro'),
            'unit' => trim($request->string('unit', 'pieza')),
            'price' => $request->string('price', '0'),
            'stock' => $request->int('stock', 0),
            'min_stock' => $request->int('min_stock', 0),
            'active' => $request->bool('active'),
        ];
        $validator = $this->validator($data, ['sku' => 'SKU', 'name' => 'nombre', 'category' => 'categoría', 'unit' => 'unidad', 'price' => 'precio', 'stock' => 'existencias', 'min_stock' => 'mínimo']);
        $validator->validate([
            'sku' => 'required|min:1|max:40',
            'name' => 'required|min:2|max:120',
            'category' => 'required|in:fertilizante,plaguicida,semilla,herramienta,otro',
            'unit' => 'required|min:1|max:20',
            'price' => 'required|numeric|between:0,999999.99',
            'stock' => 'required|integer|between:0,1000000',
            'min_stock' => 'required|integer|between:0,1000000',
        ]);
        return [[
            'sku' => $data['sku'], 'name' => $data['name'],
            'category' => $data['category'],
            'unit' => $data['unit'] !== '' ? $data['unit'] : 'pieza',
            'price' => $data['price'], 'stock' => $data['stock'], 'min_stock' => $data['min_stock'],
            'active' => $data['active'],
        ], $validator->errors()];
    }
}
