<?php
if (!Auth::isAdmin()) {
    header('Location: /?page=home');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId) {
        $api      = new ApiClient();
        $response = $api->post('/admin/users/' . $userId . '/unban', [], Auth::getToken());

        if ($response['status'] === 200) {
            $_SESSION['admin_success'] = 'Utilisateur débanni avec succès.';
        } else {
            $_SESSION['admin_error'] = $response['body']['error']['message'] ?? 'Erreur lors du déban.';
        }
    }
}

header('Location: /?page=admin');
exit;
