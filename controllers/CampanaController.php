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

    /* ─── PROCESAMIENTO IA (GEMINI) ────────────────────────────────── */

    public function processImageIA(): void {
        header('Content-Type: application/json');
        
        $inputData = json_decode(file_get_contents('php://input'), true);
        $base64Image = $inputData['image'] ?? '';
        
        if (empty($base64Image)) {
            echo json_encode(['ok' => false, 'error' => 'No se recibió imagen']);
            exit;
        }

        $apiKey = "AIzaSyBEk4ziQM0iMmHOA7ssfli65woGyMK1kZ4";
        // Usar la API estable v1 y el alias 'latest' que siempre funciona
        $url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash-latest:generateContent";

        $prompt = "Analiza la imagen adjunta que contiene una tabla de trabajos/ítems. "
                . "Debes extraer exclusivamente la información de dos columnas: Descripción (descripcion) y Cantidad (cantidad). "
                . "Ignora cualquier otra columna. No añadas comentarios ni explicaciones. "
                . "Devuelve los resultados únicamente en un formato de arreglo JSON válido: "
                . "[{\"descripcion\": \"Nombre\", \"cantidad\": 10}]";

        $data = [
            "contents" => [[
                "parts" => [
                    ["text" => $prompt],
                    ["inline_data" => ["mime_type" => "image/jpeg", "data" => $base64Image]]
                ]
            ]]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-goog-api-key: ' . $apiKey
        ]);
        
        // Omitir verificación SSL si el hosting tiene certificados antiguos/desactualizados
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo json_encode(['ok' => false, 'error' => 'Error de API (Código ' . $httpCode . ')', 'debug' => $response]);
            exit;
        }

        $result = json_decode($response, true);
        $rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        // Limpiar markdown del JSON si la IA lo incluyó
        $rawText = trim(str_replace(['```json', '```'], '', $rawText));
        
        echo json_encode(['ok' => true, 'data' => json_decode($rawText, true)]);
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
                    'material_id'  => 0,
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
}
