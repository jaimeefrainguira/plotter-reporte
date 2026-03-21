<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Campana.php';
require_once __DIR__ . '/../models/Trabajo.php';
require_once __DIR__ . '/../models/Material.php';

class CampanaController {
    private Campana $campanaModel;
    private Trabajo $trabajoModel;
    private Material $materialModel;

    public function __construct() {
        $database = new Database();
        $conn = $database->getConnection();
        $this->campanaModel = new Campana($conn);
        $this->trabajoModel = new Trabajo($conn);
        $this->materialModel = new Material($conn);
    }

    public function list(): void {
        $campanas = $this->campanaModel->getAll();
        include __DIR__ . '/../views/campanas/lista.php';
    }

    public function show(int $id): void {
        $campana = $this->campanaModel->getById($id);
        if (!$campana) {
            header('Location: index.php?action=campanas_list');
            exit;
        }
        $trabajos = $this->campanaModel->getTrabajos($id);
        $materiales = $this->materialModel->getAll(soloActivos: true);
        include __DIR__ . '/../views/campanas/detalle.php';
    }

    public function store(): void {
        $data = [
            'nombre' => (string)($_POST['nombre'] ?? ''),
            'requerimiento_nro' => (string)($_POST['requerimiento_nro'] ?? ''),
            'estado' => 'PENDIENTE'
        ];
        if ($data['nombre'] !== '') {
            $id = $this->campanaModel->create($data);
            header('Location: index.php?action=campana_detail&id=' . $id);
            exit;
        }
        header('Location: index.php?action=campanas_list');
    }

    public function saveTrabajo(): void {
        $campanaId = (int)$_POST['campana_id'];
        $trabajoId = (int)($_POST['trabajo_id'] ?? 0);
        
        $data = [
            'campana_id' => $campanaId,
            'descripcion' => (string)($_POST['descripcion'] ?? ''),
            'cantidad' => (int)($_POST['cantidad'] ?? 0),
            'ancho_panel' => (float)($_POST['ancho_panel'] ?? 0),
            'alto_panel' => (float)($_POST['alto_panel'] ?? 0),
            'material_id' => (int)($_POST['material_id'] ?? 0),
            'separacion_h' => (float)($_POST['separacion_h'] ?? 0),
            'separacion_v' => (float)($_POST['separacion_v'] ?? 0),
            // Nuevos campos
            'orientacion' => (string)($_POST['orientacion'] ?? 'auto'),
            'usar_panelado' => isset($_POST['usar_panelado']) ? 1 : 0,
            'panel_ancho' => (float)($_POST['panel_ancho'] ?? 0),
            'panel_gap' => (float)($_POST['panel_gap'] ?? 0),
            'usar_sintra' => isset($_POST['usar_sintra']) ? 1 : 0,
            'consumo' => [
                'total_metros' => (float)($_POST['total_metros'] ?? 0),
                'total_planchas' => (float)($_POST['total_planchas'] ?? 0),
                'distribucion_texto' => (string)($_POST['distribucion_texto'] ?? ''),
                'unidades_por_unidad_venta' => (int)($_POST['unidades_por_rollo'] ?? 0)
            ]
        ];

        if ($trabajoId > 0) {
            $this->trabajoModel->update($trabajoId, $data);
        } else {
            $this->trabajoModel->create($data);
        }

        header('Location: index.php?action=campana_detail&id=' . $campanaId);
    }
    
    public function deleteTrabajo(): void {
        $id = (int)$_POST['id'];
        $campanaId = (int)$_POST['campana_id'];
        $this->trabajoModel->delete($id);
        header('Location: index.php?action=campana_detail&id=' . $campanaId);
        exit;
    }

    /* ─── EDITAR CAMPAÑA ────────────────────────────────────────────── */

    public function editCampana(int $id): void {
        $campana = $this->campanaModel->getById($id);
        if (!$campana) {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Campaña no encontrada.'];
            header('Location: index.php?action=campanas_list');
            exit;
        }
        // Se renderiza inline en el listado vía modal, no necesita vista propia.
        // Este método no se usa directamente — la edición ocurre por modal en lista.php
    }

    public function updateCampana(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=campanas_list');
            exit;
        }

        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $req    = trim((string)($_POST['requerimiento_nro'] ?? ''));
        $estado = trim((string)($_POST['estado'] ?? 'PENDIENTE'));

        if ($nombre === '') {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'El nombre no puede estar vacío.'];
            header('Location: index.php?action=campanas_list');
            exit;
        }

        $this->campanaModel->update($id, [
            'nombre'           => $nombre,
            'requerimiento_nro'=> $req,
            'estado'           => $estado,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => "Campaña <strong>" . htmlspecialchars($nombre) . "</strong> actualizada."];
        header('Location: index.php?action=campanas_list');
        exit;
    }

    /* ─── OTROS MÉTODOS ─────────────────────────────────────────────── */

    public function deleteCampana(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=campanas_list');
            exit;
        }

        $campana = $this->campanaModel->getById($id);
        $nombre  = $campana ? $campana['nombre'] : "#{$id}";
        $this->campanaModel->delete($id);

        $_SESSION['flash'] = ['type' => 'success', 'message' => "Campaña <strong>" . htmlspecialchars($nombre) . "</strong> eliminada."];
        header('Location: index.php?action=campanas_list');
        exit;
    }

    public function bulkSaveTrabajos(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
            exit;
        }

        $inputData = json_decode(file_get_contents('php://input'), true);
        $campanaId = (int)($inputData['campana_id'] ?? 0);
        $items     = $inputData['items'] ?? [];

        if ($campanaId <= 0 || empty($items)) {
            echo json_encode(['ok' => false, 'error' => 'Datos invalidos']);
            exit;
        }

        try {
            foreach ($items as $item) {
                $this->trabajoModel->create([
                    'campana_id'   => $campanaId,
                    'descripcion'  => (string)($item['descripcion'] ?? 'Sin nombre'),
                    'cantidad'     => (int)($item['cantidad'] ?? 1),
                    'ancho_panel'  => 0,
                    'alto_panel'   => 0,
                    'material_id'  => null,
                    'separacion_h' => 0,
                    'separacion_v' => 0,
                    'orientacion'  => 'auto',
                    'usar_panelado'=> 0,
                    'panel_ancho'  => 0,
                    'panel_gap'    => 0,
                    'usar_sintra'  => 0,
                    'consumo' => [
                        'total_metros'   => 0,
                        'total_planchas' => 0,
                        'distribucion_texto' => '',
                        'unidades_por_unidad_venta' => 0
                    ]
                ]);
            }
            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function uploadImagen(): void {
        header('Content-Type: application/json');

        if (isset($_FILES['imagen_adjunta']) && $_FILES['imagen_adjunta']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['imagen_adjunta']['tmp_name'];
            $fileName = $_FILES['imagen_adjunta']['name'];
            $fileType = $_FILES['imagen_adjunta']['type'];

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($fileType, $allowedMimeTypes)) {
                echo json_encode(['success' => false, 'error' => 'El archivo no es una imagen válida.']);
                exit;
            }

            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid('img_') . '.' . $fileExtension;

            $uploadFileDir = __DIR__ . '/../uploads/campanas/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }

            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $relativePath = 'uploads/campanas/' . $newFileName;
                echo json_encode(['success' => true, 'ruta_imagen' => $relativePath]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al mover el archivo subido al directorio.']);
            }
        } else {
            $errorMsg = 'No se recibió ninguna imagen o hubo un error en la subida.';
            if (isset($_FILES['imagen_adjunta'])) {
                $errorMsg .= ' Código error PHP: ' . $_FILES['imagen_adjunta']['error'];
            }
            echo json_encode(['success' => false, 'error' => $errorMsg]);
        }
        exit;
    }
}
