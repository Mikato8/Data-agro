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
        $products = Database::select("select * from clinic_products where {$where} order by active desc, name", $params);

        return $this->panelView('panel.inventory.index', [
            'title' => 'Inventario · Panel clínico',
            'user' => $user,
            'products' => $products,
            'q' => $q,
            'low' => $low,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->panelView('panel.inventory.form', [
            'title' => 'Nuevo producto · Panel clínico',
            'user' => $this->panelUser(),
            'product' => null,
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->panelUser();
        [$data, $errors] = $this->validateProduct($request);
        if ($errors !== []) {
            $this->withErrors($errors);
            $this->flashOld($data);
            return $this->redirect('/panel/inventario/nuevo');
        }
        if (Database::value('select id from clinic_products where owner_id = $1 and sku = $2', [(int) $user['id'], $data['sku']]) !== null) {
            $this->withErrors(['sku' => 'Ya tienes un producto con ese SKU.']);
            $this->flashOld($data);
            return $this->redirect('/panel/inventario/nuevo');
        }
        $row = Database::insert(
            'insert into clinic_products (owner_id, sku, name, category, price, stock, min_stock, active)
             values ($1, $2, $3, $4, $5, $6, $7, $8) returning id',
            [(int) $user['id'], $data['sku'], $data['name'], $data['category'], $data['price'], $data['stock'], $data['min_stock'], $data['active']]
        );
        $this->audit('clinica.producto_creado', 'clinic_product', (string) ($row['id'] ?? 0));
        Flash::success('Producto agregado al inventario.');
        return $this->redirect('/panel/inventario');
    }

    public function edit(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $product = $this->findProduct((int) $user['id'], (int) $id);
        if ($product === null) {
            $this->notFound('No encontramos ese producto.');
        }
        return $this->panelView('panel.inventory.form', [
            'title' => 'Editar ' . $product['name'] . ' · Panel clínico',
            'user' => $user,
            'product' => $product,
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $user = $this->panelUser();
        $product = $this->findProduct((int) $user['id'], (int) $id);
        if ($product === null) {
            $this->notFound('No encontramos ese producto.');
        }
        [$data, $errors] = $this->validateProduct($request);
        if (Database::value('select id from clinic_products where owner_id = $1 and sku = $2 and id <> $3', [(int) $user['id'], $data['sku'], (int) $product['id']]) !== null) {
            $errors['sku'] = 'Ya tienes otro producto con ese SKU.';
        }
        if ($errors !== []) {
            $this->withErrors($errors);
            $this->flashOld($data);
            return $this->redirect('/panel/inventario/' . (int) $product['id'] . '/editar');
        }
        Database::execute(
            'update clinic_products set sku = $1, name = $2, category = $3, price = $4, stock = $5, min_stock = $6, active = $7
             where id = $8 and owner_id = $9',
            [$data['sku'], $data['name'], $data['category'], $data['price'], $data['stock'], $data['min_stock'], $data['active'], (int) $product['id'], (int) $user['id']]
        );
        $this->audit('clinica.producto_actualizado', 'clinic_product', (string) $product['id']);
        Flash::success('Producto actualizado.');
        return $this->redirect('/panel/inventario');
    }

    /** @return array<string, mixed>|null */
    private function findProduct(int $ownerId, int $id): ?array
    {
        return Database::first('select * from clinic_products where id = $1 and owner_id = $2', [$id, $ownerId]);
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>} */
    private function validateProduct(Request $request): array
    {
        $data = [
            'sku' => trim($request->string('sku')),
            'name' => trim($request->string('name')),
            'category' => trim($request->string('category')),
            'price' => $request->string('price', '0'),
            'stock' => $request->int('stock', 0),
            'min_stock' => $request->int('min_stock', 0),
            'active' => $request->bool('active'),
        ];
        $validator = $this->validator($data, ['sku' => 'SKU', 'name' => 'nombre', 'price' => 'precio', 'stock' => 'existencias', 'min_stock' => 'mínimo']);
        $validator->validate([
            'sku' => 'required|min:1|max:40',
            'name' => 'required|min:2|max:120',
            'category' => 'nullable|max:60',
            'price' => 'required|numeric|between:0,999999.99',
            'stock' => 'required|integer|between:0,1000000',
            'min_stock' => 'required|integer|between:0,1000000',
        ]);
        return [[
            'sku' => $data['sku'], 'name' => $data['name'],
            'category' => $data['category'] !== '' ? $data['category'] : null,
            'price' => $data['price'], 'stock' => $data['stock'], 'min_stock' => $data['min_stock'],
            'active' => $data['active'],
        ], $validator->errors()];
    }
}
