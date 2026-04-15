<?php
if (!Auth::isLoggedIn()) {
    header('Location: /?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['avatar'])) {
    $file = $_FILES['avatar'];

    // Messages lisibles pour chaque code d'erreur PHP
    $phpUploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'Image trop lourde. Essayez une image plus petite ou moins de 2 Mo.',
        UPLOAD_ERR_FORM_SIZE  => 'Image trop lourde. Essayez une image plus petite ou moins de 2 Mo.',
        UPLOAD_ERR_PARTIAL    => 'Upload incomplet, veuillez réessayer.',
        UPLOAD_ERR_NO_FILE    => 'Aucun fichier reçu.',
        UPLOAD_ERR_NO_TMP_DIR => 'Erreur serveur : dossier temporaire manquant.',
        UPLOAD_ERR_CANT_WRITE => 'Erreur serveur : impossible d\'écrire sur le disque.',
        UPLOAD_ERR_EXTENSION  => 'Upload bloqué par une extension PHP.',
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['avatar_error'] = $phpUploadErrors[$file['error']]
            ?? 'Erreur lors de l\'upload (code ' . $file['error'] . ').';
        header('Location: /?page=profile');
        exit;
    }

    $curl = curl_init(rtrim($_ENV['API_URL'] ?? 'http://localhost:3000/api', '/') . '/users/me/avatar');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . Auth::getToken(),
    ]);
    curl_setopt($curl, CURLOPT_POSTFIELDS, [
        'avatar' => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
    ]);

    $raw      = curl_exec($curl);
    $status   = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $response = json_decode($raw, true);
    curl_close($curl);

    if ($status === 200 && !empty($response['avatar'])) {
        $user           = Auth::getUser();
        $user['avatar'] = $response['avatar'];
        Auth::setUser($user);
        $_SESSION['avatar_success'] = 'Avatar mis à jour.';
    } else {
        $apiMessage = $response['error']['message'] ?? null;
        $_SESSION['avatar_error'] = $apiMessage ?? 'Erreur lors de la mise à jour de l\'avatar.';
    }
}

header('Location: /?page=profile');
exit;
