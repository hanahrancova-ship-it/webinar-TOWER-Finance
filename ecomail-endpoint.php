<?php
/* =====================================================================
   ECOMAIL ENDPOINT  —  bezpečné napojenie registračného formulára
   ---------------------------------------------------------------------
   Tento súbor beží na VAŠOM serveri (PHP hosting). Drží API kľúč
   v bezpečí (nikdy sa nedostane do prehliadača) a odosiela kontakt
   z formulára do vášho zoznamu v Ecomaile.

   POZOR: Ecomail z bezpečnostných dôvodov NEPRIJÍMA priame volania
   z JavaScriptu v prehliadači. Preto formulár volá tento endpoint
   a endpoint volá Ecomail.

   NASADENIE:
   1) Nahrajte tento súbor na hosting, napr. https://vasadomena.sk/ecomail-endpoint.php
   2) Doplňte nižšie ECOMAIL_API_KEY a ECOMAIL_LIST_ID
   3) V index.html nastavte:  CONFIG.endpoint = "https://vasadomena.sk/ecomail-endpoint.php";
   ===================================================================== */

// ---------- NASTAVENIA ----------
$ECOMAIL_API_KEY = getenv('ECOMAIL_API_KEY') ?: 'SEM_VLOZTE_VAS_API_KLUC';
$ECOMAIL_LIST_ID = getenv('ECOMAIL_LIST_ID') ?: '1';          // ID zoznamu v Ecomaile
$ECOMAIL_BASE    = 'https://api2.ecomailapp.cz';              // pre .app účty použite https://api2.ecomailapp.com
$ALLOWED_ORIGIN  = '*';                                       // odporúčané: nahraďte konkrétnou doménou, napr. 'https://vasadomena.sk'

// ---------- CORS / hlavičky ----------
header('Access-Control-Allow-Origin: ' . $ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']); exit;
}

// ---------- načítanie a validácia vstupu ----------
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']); exit;
}

$fullName  = trim($data['name']  ?? '');
$email     = trim($data['email'] ?? '');
$phone     = trim($data['phone'] ?? '');
$gdpr      = !empty($data['gdpr']);
$marketing = !empty($data['marketing']);

$errors = [];
if (mb_strlen($fullName) < 3 || mb_strpos($fullName, ' ') === false) $errors[] = 'name';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))                       $errors[] = 'email';
if ($phone !== '' && !preg_match('/^\+?\d[\d\s()-]{7,}$/', $phone))   $errors[] = 'phone'; // telefón je voliteľný
if (!$gdpr)                                                          $errors[] = 'gdpr';

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'validation', 'fields' => $errors]); exit;
}

// rozdelenie mena na krstné meno + priezvisko
$parts   = preg_split('/\s+/', $fullName, 2);
$name    = $parts[0];
$surname = $parts[1] ?? '';

// ---------- zostavenie požiadavky pre Ecomail ----------
// Dokumentácia: POST /lists/{list_id}/subscribe
$payload = [
    'subscriber_data' => [
        'name'    => $name,
        'surname' => $surname,
        'email'   => $email,
        'phone'   => $phone,
        // tag pre prehľad, z ktorej kampane kontakt prišiel
        'tags'    => array_values(array_filter([
            'webinar-investovanie-tower-finance',
            isset($data['source']) ? preg_replace('/[^a-z0-9\-]/', '', strtolower((string)$data['source'])) : null,
        ])),
    ],
    'trigger_autoresponders' => true,   // spustí automatický uvítací / potvrdzovací e-mail
    'update_existing'        => true,   // ak kontakt už existuje, aktualizuje ho
    'resubscribe'            => false,
];

// marketingový súhlas (ak je neudelený, kontakt slúži len na organizáciu podujatia)
$payload['subscriber_data']['custom_fields'] = [
    'marketingovy_suhlas' => $marketing ? 'ano' : 'nie',
];

$url = rtrim($ECOMAIL_BASE, '/') . '/lists/' . rawurlencode($ECOMAIL_LIST_ID) . '/subscribe';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'key: ' . $ECOMAIL_API_KEY,   // autentifikácia Ecomailu cez hlavičku "key"
    ],
]);

$response = curl_exec($ch);
$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

// ---------- vyhodnotenie odpovede ----------
if ($curlErr) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'connection', 'detail' => $curlErr]); exit;
}

if ($status >= 200 && $status < 300) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'ecomail', 'status' => $status, 'detail' => $response]);
}
