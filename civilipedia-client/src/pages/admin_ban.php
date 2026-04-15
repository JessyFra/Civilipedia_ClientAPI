<?php
if (!Auth::isAdmin()) {
    header('Location: /?page=home');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId  = (int)($_POST['user_id'] ?? 0);
    $reason  = trim($_POST['reason'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');

    if ($userId && $reason) {
        $api  = new ApiClient();
        $data = ['reason' => $reason];
        if ($endDate) $data['end_date'] = $endDate;

        $response = $api->post('/admin/users/' . $userId . '/ban', $data, Auth::getToken());

        if ($response['status'] === 201) {
            $_SESSION['admin_success'] = 'Utilisateur banni avec succès.';
        } else {
            $_SESSION['admin_error'] = $response['body']['error']['message'] ?? 'Erreur lors du ban.';
        }
    }
}

header('Location: /?page=admin');
exit;
