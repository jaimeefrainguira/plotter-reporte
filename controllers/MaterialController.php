<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Material.php';

class MaterialController
{
    private Material $model;

    public function __construct()
    {
        $db          = (new Database())->getConnection();
        $this->model = new Material($db);
    }

    /* ─── LIST ──────────────────────────────────────────────────────────── */

    public function list(): void
    {
        $materiales = $this->model->getAll();
        $stats      = $this->model->getStats();
        $tipos      = $this->model->getTipos();
        include __DIR__ . '/../views/materiales/lista.php';
    }

    /* ─── CREATE ────────────────────────────────────────────────────────── */

    public function showCreateForm(): void
    {
        $tipos = $this->model->getTipos();
        include __DIR__ . '/../views/materiales/form.php';
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('materiales_list');
        }

        $errors = $this->validate($_POST);
        if ($errors) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => implode('<br>', $errors)];
            $this->redirect('material_create');
            return;
        }

        $this->model->create($_POST);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Material creado correctamente.'];
        $this->redirect('materiales_list');
    }

    /* ─── EDIT ──────────────────────────────────────────────────────────── */

    public function showEditForm(int $id): void
    {
        $material = $this->model->getById($id);
        if (!$material) {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Material no encontrado.'];
            $this->redirect('materiales_list');
            return;
        }
        $tipos = $this->model->getTipos();
        include __DIR__ . '/../views/materiales/form.php';
    }

    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('materiales_list');
        }

        $errors = $this->validate($_POST);
        if ($errors) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => implode('<br>', $errors)];
            $this->redirect('material_edit', ['id' => $id]);
            return;
        }

        $this->model->update($id, $_POST);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Material actualizado correctamente.'];
        $this->redirect('materiales_list');
    }

    /* ─── DELETE ────────────────────────────────────────────────────────── */

    public function destroy(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('materiales_list');
        }

        $this->model->delete($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Material eliminado.'];
        $this->redirect('materiales_list');
    }

    /* ─── STOCK ADJUSTMENT ──────────────────────────────────────────────── */

    public function adjustStock(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('materiales_list');
        }

        $id    = (int)   ($_POST['id']    ?? 0);
        $delta = (float) ($_POST['delta'] ?? 0);

        if ($id > 0) {
            $this->model->adjustStock($id, $delta);
            $accion = $delta >= 0 ? 'añadido' : 'reducido';
            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => "Stock {$accion} correctamente.",
            ];
        }

        $this->redirect('materiales_list');
    }

    /* ─── HELPERS ───────────────────────────────────────────────────────── */

    private function validate(array $data): array
    {
        $errors = [];

        if (empty(trim($data['nombre'] ?? ''))) {
            $errors[] = 'El nombre del material es obligatorio.';
        }
        if (empty(trim($data['tipo'] ?? ''))) {
            $errors[] = 'El tipo/categoría es obligatorio.';
        }
        if (!is_numeric($data['ancho_cm'] ?? '') || (float)$data['ancho_cm'] <= 0) {
            $errors[] = 'El ancho del rollo debe ser un número mayor a 0.';
        }
        if (!is_numeric($data['largo_rollo_m'] ?? '') || (float)$data['largo_rollo_m'] <= 0) {
            $errors[] = 'El largo del rollo debe ser un número mayor a 0.';
        }

        return $errors;
    }

    private function redirect(string $action, array $params = []): void
    {
        $query = http_build_query(array_merge(['action' => $action], $params));
        header("Location: index.php?{$query}");
        exit;
    }
}
