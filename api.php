<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $search = $_GET['q'] ?? '';
        if (!empty($search)) {
            $stmt = dbQuery(
                "SELECT * FROM contacts WHERE name ILIKE :q OR company ILIKE :q OR email ILIKE :q ORDER BY id DESC", 
                ['q' => "%$search%"]
            );
        } else {
            $stmt = dbQuery("SELECT * FROM contacts ORDER BY id DESC");
        }
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'O nome é obrigatório.']);
            exit;
        }

        if (!empty($data['id'])) {
            $sql = "UPDATE contacts SET name = :name, company = :company, phone = :phone, email = :email WHERE id = :id";
            dbQuery($sql, [
                'id'      => $data['id'],
                'name'    => $data['name'],
                'company' => $data['company'] ?? null,
                'phone'   => $data['phone'] ?? null,
                'email'   => $data['email'] ?? null,
            ]);
        } else {
            $sql = "INSERT INTO contacts (name, company, phone, email) VALUES (:name, :company, :phone, :email)";
            dbQuery($sql, [
                'name'    => $data['name'],
                'company' => $data['company'] ?? null,
                'phone'   => $data['phone'] ?? null,
                'email'   => $data['email'] ?? null,
            ]);
        }

        echo json_encode(['success' => true]);
        exit;
    }

    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            dbQuery("DELETE FROM contacts WHERE id = :id", ['id' => $id]);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID não fornecido.']);
        }
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}